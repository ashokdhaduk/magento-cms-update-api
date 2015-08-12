<?php

class Wage_Transfer_Model_Api2_Cblocks_Rest_Admin_V1 extends Wage_Transfer_Model_Api2_Cblocks
{
	protected function _create(array $data)
    {
        try{ 
            if(!empty($data))
            {    
                foreach($data['items'] as $cmsblocks) {

                    $storeArray = array();
                    $storeArray = explode(',', $cmsblocks['store_ids']);
                    $identifier = trim($cmsblocks['identifier']);

                    $cmsBlock = Mage::getModel('cms/block');

                    $collection = Mage::getModel('cms/block')->getCollection()->addStoreFilter($storeArray, false)
                                ->addFieldToFilter('identifier', $identifier); 
                                //->toArray();
                                
                    $collectionData = $collection->getData();     

                    if(isset($collectionData[0]['block_id'])){
                        $cmsBlock->load($collectionData[0]['block_id']);
                    }

                    if($cmsBlock->isObjectNew()) {

                        $cmsBlock->setIdentifier($identifier)
                                ->setCreationTime(Varien_Date::now());    
                    } 
                    
                    $cmsBlock->setUpdateTime(Varien_Date::now())
                             ->setStores(array($cmsblocks['store_ids']))
                             ->setIsActive($cmsblocks['is_active'])
                             ->setTitle($cmsblocks['title'])
                             ->setContent($cmsblocks['content'])
                            ->save();
     
                }
            }else{
               $this->_critical('Empty data found'); 
            }
        } catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
            $this->_critical(sprintf('Invalid attribute "%s": %s', $e->getAttributeCode(), $e->getMessage()),
                Mage_Api2_Model_Server::HTTP_BAD_REQUEST);
        } catch (Mage_Core_Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_INTERNAL_ERROR);
        } catch (Exception $e) {
            $this->_critical(self::RESOURCE_UNKNOWN_ERROR);
        }

    }

    protected function _retrieveCollection()
    {
        $collection = Mage::getModel('cms/block')->getCollection();
        $collection->getSelect()->columns("GROUP_CONCAT(block_store.store_id SEPARATOR ',') AS store_ids")
                                ->join(
                                        array('block_store' => $collection->getTable('cms/block_store')),
                                        'main_table.block_id = block_store.block_id',
                                        array()
                                    )
                                ->group('main_table.block_id');
        return $collection->getData();
    }

}