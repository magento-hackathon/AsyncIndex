<?php

class Hackathon_AsyncIndex_AsyncindexController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        $process   = $this->getProcessCodeFromRequestParams();

        $this->tryScheduleIndex($process, true);

        $this->_redirectUrl($this->_getRefererUrl());
    }
    
    public function schedulePartialAction()
    {
        $process   = $this->getProcessCodeFromRequestParams();
        
        $this->tryScheduleIndex($process);
        
        $this->_redirectUrl($this->_getRefererUrl());
    }
    
    protected function getProcessCodeFromRequestParams()
    {
        $process   = $this->getRequest()->getParam('process_code');
        $processId = $this->getRequest()->getParam('process');
        if ($processId) {
            $processModel = Mage::getModel('index/process');
            $processModel->load($processId);
            $process = $processModel->getIndexerCode();
        }
        return $process;
    }
    
    protected function tryScheduleIndex( $indexerCode, $fullReindex = false )
    {
        /**
         * @var Mage_Adminhtml_Model_Session $session
         */
        $session = Mage::getSingleton('adminhtml/session');
        $helper  = Mage::helper('core');
        $message = array(
            "indexerCode" => $indexerCode,
            "fullReindex" => $fullReindex,
        );
        
        $taskName = $fullReindex ? 'Reindex' : 'partial Index';

        try {
            /**
             * @var Mage_Cron_Model_Schedule $schedule
             */
            $schedule = Mage::getModel('cron/schedule');
            $schedule->setJobCode('hackathon_asyncindex_cron');
            $schedule->setCreatedAt(date('Y-m-d H:i:s'));
            $schedule->setMessages(json_encode($message));
            $schedule->setScheduledAt(date('Y-m-d H:i:s'));
            $schedule->save();

            $session->addSuccess($helper->__($taskName.' successfully scheduled for process ') . $indexerCode);
        } catch (Exception $e) {
            $session->addError($helper->__($taskName.' schedule not successful, message: %s', $e->getMessage()));
        }
    }

}
