<?php

class Wage_Transfer_Model_Api2_Cpages_Rest_Admin_V1 extends Wage_Transfer_Model_Api2_Cpages
{
    protected function _retrieveCollection()
    {
        $collection = Mage::getModel('cms/page')->getCollection();
        return $collection->getData();
    }
}