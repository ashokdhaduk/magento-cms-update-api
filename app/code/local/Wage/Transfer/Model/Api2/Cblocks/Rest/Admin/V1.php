<?php

class Wage_Transfer_Model_Api2_Cblocks_Rest_Admin_V1 extends Wage_Transfer_Model_Api2_Cblocks
{
    protected function _retrieveCollection()
    {
        
        $collection = Mage::getModel('cms/block')->getCollection();
        return $collection->getData();
    }

}