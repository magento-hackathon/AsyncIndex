<?php

class Hackathon_AsyncIndex_Model_Adminhtml_System_Config_Source_Index
{
    /**
     * @var null|array
     */
    protected $_options;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->_options == null) {
            $this->_options = array();
            $processCollection = Mage::getResourceModel('index/process_collection');

            foreach ($processCollection as $process) {
                $indexer = $process->getIndexer();

                if ($indexer->isVisible()) {
                    $this->_options[] = array(
                        'label' => $indexer->getName(),
                        'value' => $process->getIndexerCode()
                    );
                }
            }
        }

        return $this->_options;
    }
}