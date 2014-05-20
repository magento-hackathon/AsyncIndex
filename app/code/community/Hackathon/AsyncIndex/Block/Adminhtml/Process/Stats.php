<?php

class Hackathon_AsyncIndex_Block_Adminhtml_Process_Stats extends Mage_Adminhtml_Block_Template
{


    public function getPendingEventsPerProcess()
    {
        $pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();

        $result = array();

        /** @var Mage_Index_Model_Process $process */
        foreach ($pCollection as $process) {
            $result[ $process->getIndexerCode() ] = $process->getUnprocessedEventsCollection()->count();
        }

        return $result;
    }
}
