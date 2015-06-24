<?php
class Wage_Transfer_Adminhtml_CpagesController extends Mage_Adminhtml_Controller_action
{
    public function indexAction()
    {
		$gethost = Mage::getStoreConfig('transfer/general/gethost');
        $sethost = Mage::getStoreConfig('transfer/general/sethost');
		$adminName = 'admin';
		$callbackUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cpages/callback");
		$temporaryCredentialsRequestUrl = $gethost."/oauth/initiate";
		$adminAuthorizationUrl = $gethost.'/'.$adminName.'/oauth_authorize'; 
		$accessTokenRequestUrl = $gethost.'/oauth/token'; 
		$apiUrl = $gethost.'/api/rest'; 
		$consumerKey = Mage::getStoreConfig('transfer/general/consumerKey');
		$consumerSecret = Mage::getStoreConfig('transfer/general/consumerSecret');

    	$params = array(
            'siteUrl' => $gethost.'/oauth',
            'requestTokenUrl' => $temporaryCredentialsRequestUrl,
            'accessTokenUrl' => $accessTokenRequestUrl,
            'authorizeUrl' => $adminAuthorizationUrl,
            'consumerKey' => $consumerKey,
            'consumerSecret' => $consumerSecret,
            'callbackUrl' => $callbackUrl
        );
   	
        $oAuthClient = Mage::getModel('transfer/oauth_client');
        $oAuthClient->reset();
 
        $oAuthClient->init($params);
        $oAuthClient->authenticate();

        return;
    }

    public function callbackAction() 
    {
 
 		$gethost = Mage::getStoreConfig('transfer/general/gethost');
        $sethost = Mage::getStoreConfig('transfer/general/sethost');
		$temporaryCredentialsRequestUrl = $gethost."/oauth/initiate";
		$accessTokenRequestUrl = $gethost.'/oauth/token'; 
		$consumerKey = Mage::getStoreConfig('transfer/general/consumerKey');
		$consumerSecret = Mage::getStoreConfig('transfer/general/consumerSecret');

		
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $table = $resource->getTableName('cms/page');
        $writeConnection = $resource->getConnection('core_write');
        $writeConnection->beginTransaction();

        $oAuthClient = Mage::getModel('transfer/oauth_client');
        $params = $oAuthClient->getConfigFromSession();
        $oAuthClient->init($params);
 
        $state = $oAuthClient->authenticate();

        $acessToken = $oAuthClient->getAuthorizedToken();

        if ($state == Wage_Transfer_Model_OAuth_Client::OAUTH_STATE_NO_TOKEN) {
            Mage::getSingleton('adminhtml/session')->addError('Authorization has been rejected.');
            $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_page");
            Mage::app()->getResponse()->setRedirect($redirectUrl);
            return;
        }

        if ($state == Wage_Transfer_Model_OAuth_Client::OAUTH_STATE_ACCESS_TOKEN) {
            $acessToken = $oAuthClient->getAuthorizedToken();
        }
 
        $restClient = $acessToken->getHttpClient($params);
        // Set REST resource URL
        $restClient->setUri($gethost.'/api/rest/cpages');
        // In Magento it is neccesary to set json or xml headers in order to work
        $restClient->setHeaders('Accept', 'application/json');
        // Get method
        $restClient->setMethod(Zend_Http_Client::GET);
        //Make REST request
        $response = $restClient->request();
        // Here we can see that response body contains json list of products

        $responseContent = json_decode($response->getBody(), true);

        $stores = array(Mage_Core_Model_App::ADMIN_STORE_ID);
        foreach($responseContent as $cmspages) {

            $identifier = trim($cmspages['identifier']);
            $Record = $readConnection->fetchCol('SELECT identifier FROM '.$table.' WHERE identifier = "'.$identifier.'" ');
            if($Record[0]) {

                unset($cmspages['page_id']);
                unset($cmspages['identifier']);
                $cmspages['update_time'] = Varien_Date::now();
                //$existCmsPage = Mage::getModel('cms/page')->load($identifier, 'identifier');
                //$existCmsPage->setData($cmspages);
                //$existCmsPage->save();
                //$adsd[] = $existCmsPage->getData();
                
                $__fields = array();
                $__fields = $cmspages;
                $__where = $writeConnection->quoteInto('identifier =?', $identifier);
                $writeConnection->update($table, $cmspages, $__where);   

            }else{

                unset($cmspages['page_id']);
                $cmspages['creation_time'] = Varien_Date::now();
                $cmspages['update_time'] = Varien_Date::now();
                //$newCms = Mage::getModel('cms/page');
                //$newCms->setData($cmspages);
                //$newCms->save();               

                $fields = array();
                $fields = $cmspages;
                $writeConnection->insert($table, $fields);                
            }
            $writeConnection->commit();
        }
 
        Mage::getSingleton('adminhtml/session')->addSuccess('CMS Static Pages Synchronize has been successfully.');
        $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_page");
        Mage::app()->getResponse()->setRedirect($redirectUrl);
    }
}