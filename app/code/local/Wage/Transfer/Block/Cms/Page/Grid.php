<?php

class Wage_Transfer_Block_Cms_Page_Grid extends Mage_Adminhtml_Block_Cms_Page_Grid
{
    protected function _prepareColumns()
    {
        $baseUrl = $this->getUrl();

        $this->addColumn('title', array(
            'header'    => Mage::helper('cms')->__('Title'),
            'align'     => 'left',
            'index'     => 'title',
        ));

        $this->addColumn('identifier', array(
            'header'    => Mage::helper('cms')->__('URL Key'),
            'align'     => 'left',
            'index'     => 'identifier'
        ));



        $this->addColumn('root_template', array(
            'header'    => Mage::helper('cms')->__('Layout'),
            'index'     => 'root_template',
            'type'      => 'options',
            'options'   => Mage::getSingleton('page/source_layout')->getOptions(),
        ));

        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'        => Mage::helper('cms')->__('Store View'),
                'index'         => 'store_id',
                'type'          => 'store',
                'store_all'     => true,
                'store_view'    => true,
                'sortable'      => false,
                'filter_condition_callback'
                                => array($this, '_filterStoreCondition'),
            ));
        }

        $this->addColumn('is_active', array(
            'header'    => Mage::helper('cms')->__('Status'),
            'index'     => 'is_active',
            'type'      => 'options',
            'options'   => Mage::getSingleton('cms/page')->getAvailableStatuses()
        ));

        $this->addColumn('creation_time', array(
            'header'    => Mage::helper('cms')->__('Date Created'),
            'index'     => 'creation_time',
            'type'      => 'datetime',
        ));

        $this->addColumn('update_time', array(
            'header'    => Mage::helper('cms')->__('Last Modified'),
            'index'     => 'update_time',
            'type'      => 'datetime',
        ));

        if(Mage::getStoreConfig('transfer/general/site') == 'staging')
        {
            $this->addColumn('push_to_live', array(
                'header'    => Mage::helper('cms')->__('Push To Live'),
                'index'     => 'push_to_live',
                'type'      => 'datetime',
            ));
        }

        $this->addColumn('page_actions', array(
            'header'    => Mage::helper('cms')->__('Action'),
            'width'     => 10,
            'sortable'  => false,
            'filter'    => false,
            'renderer'  => 'adminhtml/cms_page_grid_renderer_action',
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        if(Mage::getStoreConfig('transfer/general/site') == 'staging')
        {
            $this->setMassactionIdField('identifier');
            $this->getMassactionBlock()->setFormFieldName('cms');

            $pushUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cpages/index", array('callback'=>'pushback'));

            $this->getMassactionBlock()->addItem('push', array(
                 'label'    => Mage::helper('cms')->__('Push To Live'),
                 'url'      => $pushUrl, //$this->getUrl('*/*/massDelete'),
                 'confirm'  => Mage::helper('cms')->__('Are you sure?')
            ));

            return $this;
        }
    }

}
