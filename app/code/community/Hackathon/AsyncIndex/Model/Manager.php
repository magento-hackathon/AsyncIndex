<?php
/**
 *
 *
 *
 *
 */

class Hackathon_AsyncIndex_Model_Manager
{

    /**
     * Tells the core indexer to do a partial reindex
     * @param Mage_Index_Model_Process $process
     * @throws Exception
     */
    public function executePartialIndex( Mage_Index_Model_Process $process )
    {
        /** @var $resourceModel Mage_Index_Model_Resource_Process */
        $resourceModel = Mage::getResourceSingleton('index/process');
        $resourceModel->beginTransaction();

        $indexMode = 'schedule';
        $pendingMode = 'pending';

        //Fallback for 1.6.2 installations > Undefined class constant 'MODE_SCHEDULE'
        if ( true === defined('Mage_Index_Model_Process::MODE_SCHEDULE') ) {
            $indexMode = Mage_Index_Model_Process::MODE_SCHEDULE;
            $pendingMode = Mage_Index_Model_Process::STATUS_PENDING;
        }

        try
        {
            $process->setMode($indexMode);
            $process->indexEvents();
            if ( count(Mage::getResourceSingleton('index/event')->getUnprocessedEvents($process)) === 0 ) {
                $process->changeStatus($pendingMode);
            }
            $resourceModel->commit();
        }
        catch (Exception $e)
        {
            $resourceModel->rollBack();
            throw $e;
        }

    }

}
