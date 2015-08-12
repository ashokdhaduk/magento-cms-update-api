<?php

class Wage_Transfer_Model_Api2_Cpages_Rest_Admin_V1 extends Wage_Transfer_Model_Api2_Cpages
{
	protected function _create(array $data)
    {
        try{ 
            if(!empty($data))
            {    
                foreach($data['items'] as $cmspages) {
                    $identifier = trim($cmspages['identifier']);
                    $cmsPage = Mage::getModel('cms/page');

                    $cmsCheck = $cmsPage->getResource()->checkIdentifier($identifier, $cmspages['store_ids']);
                    if($cmsCheck){
                        $cmsPage->load($cmsCheck);
                    }

                    if($cmsPage->isObjectNew()) {

                        $cmsPage->setIdentifier($identifier)
                                ->setCreationTime(Varien_Date::now());    
                    } 
                                        
                    $cmsPage->setUpdateTime(Varien_Date::now())   
                             ->setStores(array($cmspages['store_ids']))                             
                             ->setIsActive($cmspages['is_active'])
                             ->setTitle($cmspages['title'])
                             ->setContent($cmspages['content'])
                             ->setRootTemplate($cmspages['root_template'])
                             ->setMetaKeywords($cmspages['meta_keywords'])
                             ->setMetaDescription($cmspages['meta_description'])
                             ->setContentHeading($cmspages['content_heading'])
                             ->setSortOrder($cmspages['sort_order'])
                             ->setLayoutUpdateXml($cmspages['layout_update_xml'])
                             ->setCustomTheme($cmspages['custom_theme'])
                             ->setCustomRootTemplate($cmspages['custom_root_template'])
                             ->setCustomLayoutUpdateXml($cmspages['custom_layout_update_xml'])
                             ->setCustomThemeFrom($cmspages['custom_theme_from'])
                             ->setCustomThemeTo($cmspages['custom_theme_to'])
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
        $collection = Mage::getModel('cms/page')->getCollection();
        $collection->getSelect()->columns("GROUP_CONCAT(page_store.store_id SEPARATOR ',') AS store_ids")
                                ->join(
                                        array('page_store' => $collection->getTable('cms/page_store')),
                                        'main_table.page_id = page_store.page_id',
                                        array()
                                    )
                                ->group('main_table.page_id');
        return $collection->getData();
    }
}