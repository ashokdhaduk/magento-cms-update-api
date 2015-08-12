<?php

class Wage_Transfer_Model_Observer{
    
    public function addButtonTransfer($observer)
    {
        if(Mage::getStoreConfig('transfer/general/site') == 'staging')
        {
            $container = $observer->getBlock();
            if(null !== $container && $container->getType() == 'adminhtml/cms_block') {
                $message = Mage::helper('transfer')->__('Are you sure you want to do this?');

                $syncUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cblocks/index", array('callback'=>'syncback'));
                $syncButton = array(
                    'label'     => 'Sync',
                    'onclick'   => "confirmSetLocation('{$message}', '{$syncUrl}')",
                    'class'     => 'go'
                );
                $container->addButton('add_button_cblocks_sync', $syncButton);

                /*$pushUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cblocks/index", array('callback'=>'pushback'));
                $pushButton = array(
                    'label'     => 'Push To Live',
                    'onclick'   => "confirmSetLocation('{$message}', '{$pushUrl}')",
                    'class'     => 'go'
                );
                $container->addButton('add_button_cblocks_push', $pushButton);*/

            }

            if(null !== $container && $container->getType() == 'adminhtml/cms_page') {
                $message = Mage::helper('transfer')->__('Are you sure you want to do this?');

                $syncUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cpages/index", array('callback'=>'syncback'));
                $syncButton = array(
                    'label'     => 'Sync',
                    'onclick'   => "confirmSetLocation('{$message}', '{$syncUrl}')",
                    'class'     => 'go'
                );
                $container->addButton('add_button_cpages_sync', $syncButton);

                /*$pushUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cpages/index", array('callback'=>'pushback'));
                $pushButton = array(
                    'label'     => 'Push To Live',
                    'onclick'   => "confirmSetLocation('{$message}', '{$pushUrl}')",
                    'class'     => 'go'
                );
                $container->addButton('add_button_cpages_push', $pushButton);*/
            }
        }

        return $this;
    }
}