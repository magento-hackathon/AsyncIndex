<?php

class Hackathon_AsyncIndex_AsyncindexController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        $process   = $this->getRequest()->getParam('process_code');
        $processId = $this->getRequest()->getParam('process');
        if ($processId) {
            $processModel = Mage::getModel('index/process');
            $processModel->load($processId);
            $process = $processModel->getIndexerCode();
        }

        /**
         * @var Mage_Adminhtml_Model_Session $session
         */
        $session = Mage::getSingleton('adminhtml/session');
        $helper  = Mage::helper('core');
        try {
            /**
             * @var Mage_Cron_Model_Schedule $schedule
             */
            $schedule = Mage::getModel('cron/schedule');
            $schedule->setJobCode('hackathon_asyncindex_cron');
            $schedule->setCreatedAt(date('Y-m-d H:i:s'));
            $schedule->setMessages($process);
            $schedule->setScheduledAt(date('Y-m-d H:i:s'));
            $schedule->save();

            $session->addSuccess($helper->__('Reindex successfully scheduled for process ') . $process);
        } catch (Exception $e) {
            $session->addError($helper->__('Reindex schedule not successful, message: %s', $e->getMessage()));
        }

        $this->_redirectUrl($this->_getRefererUrl());
    }

}
