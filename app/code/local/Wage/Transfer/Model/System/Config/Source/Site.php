<?php
class Wage_Transfer_Model_System_Config_Source_Site
{
    public function toOptionArray($isMultiselect=false)
    {
        $options = array();
        
        $options[] = array('value' => 'live', 'label' => 'Live');
        $options[] = array('value' => 'staging', 'label' => 'Staging');
        
        return $options;
    }
}
