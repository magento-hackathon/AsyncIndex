<?php

class Hackathon_AsyncIndex_Model_Observer extends Mage_Core_Model_Abstract
{
    public function schedule_index()
    {
        $scheduledJob = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('job_code', 'hackathon_asyncindex_cron')
            ->getLastItem();

        $indexer = 'tag_aggregation'; //fallback - if not set this should be the fastest on every shop

        if ( $scheduledJob->getStatus() != 'success' )
        {
            $indexer = intval($scheduledJob->getMessages());
        }

        $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode($indexer);

        if ($indexProcess)
        {
            $indexProcess->reindexEverything();
        }

    }
}
