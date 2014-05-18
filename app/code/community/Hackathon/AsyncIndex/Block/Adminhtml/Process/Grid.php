<?php

class Hackathon_AsyncIndex_Block_Adminhtml_Process_Grid extends Mage_Index_Block_Adminhtml_Process_Grid
{

    protected function _afterLoadCollection()
    {
        parent::_afterLoadCollection();
        /** @var $process Mage_Index_Model_Process */
        foreach ($this->_collection as $process) {
            $process->setEventCount($process->getUnprocessedEventsCollection()->count());
        }

        return $this;
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->removeColumn('action');
        $this->addColumn('event_count', array(
                'header'   => Mage::helper('index')->__('Event Count'),
                'width'    => '80',
                'index'    => 'event_count',
                'sortable' => false
            )
        );
        $this->addColumn('action', array(
                'header'    => Mage::helper('index')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('index')->__('Reindex Data'),
                        'url'     => array('base' => '*/*/reindexProcess'),
                        'field'   => 'process'
                    ),
                    array(
                        'caption' => Mage::helper('index')->__('Schedule Reindex'),
                        'url'     => array('base' => 'asyncindex/asyncindex/index'),
                        'params'  => array('_current' => true, '_secure' => false),
                        'field'   => 'process'
                    ),
                    array(
                        'caption' => Mage::helper('index')->__('Schedule partial index'),
                        'url'     => array('base' => 'asyncindex/asyncindex/schedulePartial'),
                        'params'  => array('_current' => true, '_secure' => false),
                        'field'   => 'process'
                    ),
                ),
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true,
            )
        );
    }
}
