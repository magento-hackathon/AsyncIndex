<?php

class Hackathon_AsyncIndex_AsyncindexController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        echo "It works!";
    }

}
