<?php

class Wage_Transfer_Model_Observer{
    
    public function addButtonTransfer($observer)
    {
        $container = $observer->getBlock();
        if(null !== $container && $container->getType() == 'adminhtml/cms_block') {
            $message = Mage::helper('transfer')->__('Are you sure you want to do this?');
            $url = Mage::helper("adminhtml")->getUrl("adminhtml/cblocks");
            $data = array(
                'label'     => 'Sync Blocks',
                'onclick'   => "confirmSetLocation('{$message}', '{$url}')",
                'class'     => 'go'
            );
            $container->addButton('add_button_cblocks', $data);
        }

        if(null !== $container && $container->getType() == 'adminhtml/cms_page') {
            $message = Mage::helper('transfer')->__('Are you sure you want to do this?');
            $url = Mage::helper("adminhtml")->getUrl("adminhtml/cpages");
            $data = array(
                'label'     => 'Sync Pages',
                'onclick'   => "confirmSetLocation('{$message}', '{$url}')",
                'class'     => 'go'
            );
            $container->addButton('add_button_cpages', $data);
        }
     
        return $this;
    }
}