<?php

class Hackathon_AsyncIndex_Model_Observer
{
    /**
     * @var bool
     */
    protected $_shouldLoadUnprocessedEventCount = false;

    /**
     * @see event adminhtml_block_html_before
     * @param Varien_Event_Observer $observer
     * @return Hackathon_AsyncIndex_Model_Observer
     */
    public function extendIndexProcessGrid(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Block_Template $block */
        $block = $observer->getBlock();
        if ($block instanceof Mage_Index_Block_Adminhtml_Process_Grid) {
            $this->_addEventCountColumnTo($block);
            $this->_changeActions($block);
        }
        return $this;
    }
    /**
     * Add count of indexed data to grid
     *
     * @param Mage_Index_Block_Adminhtml_Process_Grid $grid
     * @return Hackathon_AsyncIndex_Model_Observer
     */
    protected function _addEventCountColumnTo(Mage_Index_Block_Adminhtml_Process_Grid $grid)
    {
        $grid->addColumnAfter('event_count', array(
                'header'   => Mage::helper('index')->__('Event Count'),
                'width'    => '80',
                'index'    => 'event_count',
                'sortable' => false
            ), 'ended_at'
        );
        return $this;
    }
    /**
     * Change action column to dropdown with additional actions
     * - schedule reindex
     * - schedule partial reindex
     * 
     * @param Mage_Index_Block_Adminhtml_Process_Grid $grid
     * @return Hackathon_AsyncIndex_Model_Observer
     */
    protected function _changeActions(Mage_Index_Block_Adminhtml_Process_Grid $grid)
    {
        $grid->getColumn('action')->setActions(array(
                array(
                    'caption' => Mage::helper('index')->__('Reindex Data'),
                    'url'     => array('base' => '*/*/reindexProcess'),
                    'field'   => 'process'
                ),
                array(
                    'caption' => Mage::helper('index')->__('Schedule Reindex'),
                    'url'     => array('base' => 'adminhtml/asyncindex/index'),
                    'params'  => array('_current' => true, '_secure' => false),
                    'field'   => 'process'
                ),
                array(
                    'caption' => Mage::helper('index')->__('Schedule partial index'),
                    'url'     => array('base' => 'adminhtml/asyncindex/schedulePartial'),
                    'params'  => array('_current' => true, '_secure' => false),
                    'field'   => 'process'
                ),
            ));
        return $this;
    }
    /**
     * Set flag to add unprocessed event count after load.
     * Uses core_block_abstract_to_html_before because this is the only event fired before
     * grid is prepared and collection loaded. Cannot use collection flag because collection
     * is not instantiated yet.
     * 
     * @see event core_block_abstract_to_html_before
     * @param Varien_Event_Observer $observer
     * @return Hackathon_AsyncIndex_Model_Observer
     */
    public function prepareCollectionForGrid(Varien_Event_Observer $observer)
    {
        /* @var $block Mage_Core_Block_Abstract $block */
        $block = $observer->getBlock();
        if ($block instanceof Mage_Index_Block_Adminhtml_Process_Grid) {
            $this->_shouldLoadUnprocessedEventCount = true;
        }
        return $this;
    }
    /**
     * Add event count to process collection if flag has been set
     * 
     * @see event process_collection_load_after
     * @param Varien_Event_Observer $observer
     * @return Hackathon_AsyncIndex_Model_Observer
     */
    public function addEventCountToCollection(Varien_Event_Observer $observer)
    {
        /* @var $processCollection Mage_Index_Model_Resource_Process_Collection */
        $processCollection = $observer->getProcessCollection();
        if ($this->_shouldLoadUnprocessedEventCount) {
            /* @var $process Mage_Index_Model_Process */
            foreach ($processCollection as $process) {
                $process->setEventCount($process->getUnprocessedEventsCollection()->count());
            }
        }
        
        return $this;
    }

    /**
     * Executes manually scheduled reindex
     */
    public function schedule_index()
    {
        // Only one job should be running.
        $scheduledJob = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('job_code', 'hackathon_asyncindex_cron')
            ->addFieldToFilter('status', 'running')
            ->getFirstItem();

        $message = json_decode($scheduledJob->getMessages(), true);
        if (isset($message['indexerCode'])) {
            $indexer = $message['indexerCode'];
            /** @var Hackathon_AsyncIndex_Model_Manager $indexManager */
            $indexManager = Mage::getModel('hackathon_asyncindex/manager');

            $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode($indexer);

            if ($indexProcess) {
                if ($message['fullReindex'] === true) {
                    $indexProcess->reindexEverything();
                } else {
                    $indexManager->executePartialIndex($indexProcess);
                }
            }
        }
    }

    /**
     * Indexes a specific number of events
     *
     * @throws Exception
     */
    public function unprocessed_events_index()
    {

        if ( !Mage::getStoreConfig('system/asyncindex/auto_index') ) {
            return null;
        }

        /** @var $resourceModel Mage_Index_Model_Resource_Process */
        $resourceModel = Mage::getResourceSingleton('index/process');

        $resourceModel->beginTransaction();

        try
        {
            $pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();
            /** @var Mage_Index_Model_Process $process */
            foreach ($pCollection as $process) {
                $process->setMode(Mage_Index_Model_Process::MODE_SCHEDULE);
                $eventLimit            = (int)Mage::getStoreConfig('system/asyncindex/event_limit');
                $unprocessedColl = $process->getUnprocessedEventsCollection()->setPageSize($eventLimit);

                /** @var Mage_Index_Model_Event $unprocessedEvent */
                foreach ($unprocessedColl as $unprocessedEvent) {
                    $process->processEvent($unprocessedEvent);
                    $unprocessedEvent->save();
                }
                if ( count(Mage::getResourceSingleton('index/event')->getUnprocessedEvents($process) ) === 0) {
                    $process->changeStatus(Mage_Index_Model_Process::STATUS_PENDING);
                }
            }
            $resourceModel->commit();
        }
        catch (Exception $e)
        {
            $resourceModel->rollBack();
            throw $e;
        }
    }

    public function runIndex() {

        if ( !Mage::getStoreConfig('system/asyncindex/auto_index') ) {
            return null;
        }

        $blacklistCfg = Mage::getStoreConfig('system/asyncindex/blacklist_indexes');
        $blacklist = explode(',', $blacklistCfg);

        $partialIndex = Mage::getStoreConfig('system/asyncindex/partial_cron_index');

        if($partialIndex) {

            $indexManager = Mage::getModel('hackathon_asyncindex/manager');
            $pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();

            /** @var Mage_Index_Model_Process $process */
            foreach ($pCollection as $process) {
                if ( in_array($process->getIndexerCode(), $blacklist) )
                {
                  continue;
                }
                $indexManager->executePartialIndex($process);
            }

        } else {

            // run the normal indexer method
            $this->unprocessed_events_index();

        }

    }
}
