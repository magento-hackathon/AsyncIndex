<?php

class Hackathon_AsyncIndex_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Executes manually scheduled reindex
     */
    public function schedule_index()
    {
        $scheduledJob = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('job_code', 'hackathon_asyncindex_cron')
            ->getLastItem();

        $indexer = 'tag_aggregation'; //fallback - if not set this should be the fastest on every shop

        if ($scheduledJob->getStatus() != 'success') {
            $indexer = intval($scheduledJob->getMessages());
        }

        $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode($indexer);

        if ($indexProcess) {
            $indexProcess->reindexEverything();
        }

    }

    /**
     * Indexes a specific number of events
     *
     * @throws Exception
     */
    public function unprocessed_events_index()
    {

        if (!Mage::getStoreConfig('system/asyncindex/auto_index')) {
            return null;
        }

        /** @var $resourceModel Mage_Index_Model_Resource_Process */
        $resourceModel = Mage::getResourceSingleton('index/process');

        $resourceModel->beginTransaction();

        try {
            $pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();
            /** @var Mage_Index_Model_Process $process */
            foreach ($pCollection as $process) {
                $process->setMode(Mage_Index_Model_Process::MODE_SCHEDULE);
                $eventLimit            = (int)Mage::getStoreConfig('system/asyncindex/event_limit');
                $unprocessedCollection = $process->getUnprocessedEventsCollection()->setPageSize($eventLimit);

                /** @var Mage_Index_Model_Event $unprocessedEvent */
                foreach ($unprocessedCollection as $unprocessedEvent) {
                    $process->processEvent($unprocessedEvent);
                    $unprocessedEvent->save();
                }
                if (count(Mage::getResourceSingleton('index/event')->getUnprocessedEvents($process)) === 0) {
                    $process->changeStatus(Mage_Index_Model_Process::STATUS_PENDING);
                }
            }
            $resourceModel->commit();
        } catch (Exception $e) {
            $resourceModel->rollBack();
            throw $e;
        }

    }
}
