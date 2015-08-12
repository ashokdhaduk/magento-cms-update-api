<?php
class Wage_Transfer_Adminhtml_CpagesController extends Mage_Adminhtml_Controller_action
{
    private $staginghost;
    private $livehost;
    private $adminName;
    private $callbackUrl;
    private $temporaryCredentialsRequestUrl;
    private $adminAuthorizationUrl;
    private $accessTokenRequestUrl;
    //private $apiUrl;
    private $consumerKey;
    private $consumerSecret;

    protected function _construct()
    {
        $this->staginghost = Mage::getStoreConfig('transfer/general/staginghost');
        $this->livehost = Mage::getStoreConfig('transfer/general/livehost');
        $this->adminName = 'admin';
        
        $this->temporaryCredentialsRequestUrl = $this->livehost."/oauth/initiate";
        $this->adminAuthorizationUrl = $this->livehost.'/'.$this->adminName.'/oauth_authorize'; 
        $this->accessTokenRequestUrl = $this->livehost.'/oauth/token'; 
        //$this->apiUrl = $this->livehost.'/api/rest'; 
        $this->consumerKey = Mage::getStoreConfig('transfer/general/consumerKey');
        $this->consumerSecret = Mage::getStoreConfig('transfer/general/consumerSecret');

    }

    public function indexAction()
    {
        $postData = $this->getRequest()->getPost();
        if(isset($postData['cms'])){
            Mage::getSingleton('core/session')->setPushcms($postData['cms']);
        }

		$callback = $this->getRequest()->getParam('callback');

        $this->callbackUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cpages/".$callback);    
        $params = array(
            'siteUrl' => $this->livehost.'/oauth',
            'requestTokenUrl' => $this->temporaryCredentialsRequestUrl,
            'accessTokenUrl' => $this->accessTokenRequestUrl,
            'authorizeUrl' => $this->adminAuthorizationUrl,
            'consumerKey' => $this->consumerKey,
            'consumerSecret' => $this->consumerSecret,
            'callbackUrl' => $this->callbackUrl
        );

        $oAuthClient = Mage::getModel('transfer/oauth_client');
        $oAuthClient->reset();
 
        $oAuthClient->init($params);
        $oAuthClient->authenticate();

        return;
    }

    public function syncbackAction() 
    {

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
        $restClient->setUri($this->livehost.'/api/rest/cpages');
        $restClient->setHeaders('Accept', 'application/json');
        $restClient->setMethod(Zend_Http_Client::GET);
        $response = $restClient->request();

        $responseContent = json_decode($response->getBody(), true);

        try{ 
            if(!empty($responseContent))
            {    
                foreach($responseContent as $cmspages) {
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
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e);
            Mage::log(print_r($e, true));
            $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_page");
            Mage::app()->getResponse()->setRedirect($redirectUrl);
            return;
        }

        Mage::getSingleton('adminhtml/session')->addSuccess('CMS Pages Synchronize has been successfully.');
        $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_page");
        Mage::app()->getResponse()->setRedirect($redirectUrl);
    }

    public function pushbackAction() 
    {
       
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
            $accessToken = $oAuthClient->getAuthorizedToken();

            $tokenArray = explode('&', $accessToken);
            
            $oauth_token = explode('=', $tokenArray[0])[1];
            $oauth_token_secret = explode('=', $tokenArray[1])[1];
            $consumerKey =  $params['consumerKey'];
            $consumerSecret = $params['consumerSecret'];
        }
        
        if(isset($oauth_token) && isset($oauth_token_secret) && isset($consumerKey) && isset($consumerSecret))
        {
            $oauthClient = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_AUTHORIZATION);
            $oauthClient->setToken($oauth_token, $oauth_token_secret);
            $resourceUrl = $this->livehost.'/api/rest/cpages/';

            $cmsSession = Mage::getSingleton('core/session');
            $getCmsSession = $cmsSession->getPushcms();
            $cpagesData = array();

            if(Mage::getStoreConfig('transfer/general/log')){
                Mage::log(print_r($getCmsSession, true), null, 'TransferLog.log');
            }
                
            $collection = Mage::getModel('cms/page')->getCollection()
                        ->addFieldToFilter('main_table.page_id', array('in' => $getCmsSession));
            $collection->getSelect()->columns("GROUP_CONCAT(page_store.store_id SEPARATOR ',') AS store_ids")
                                ->join(
                                        array('page_store' => $collection->getTable('cms/page_store')),
                                        'main_table.page_id = page_store.page_id',
                                        array()
                                    )
                                ->group('main_table.page_id');
            foreach ($collection as $key => $value) {
                $cpagesData['items'][] = $value->getData();
            }

            if(Mage::getStoreConfig('transfer/general/log')){
                Mage::log(print_r($cpagesData, true), null, 'TransferLog.log');
            }
            
            $oauthClient->fetch($resourceUrl, json_encode($cpagesData), OAUTH_HTTP_METHOD_POST, array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ));

            $getCmsSessionData = implode(',', $getCmsSession);
            $nowTimestamp = Varien_Date::now();
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->update(
                    "cms_page",
                    array("push_to_live" => $nowTimestamp),
                    "page_id IN ($getCmsSessionData)"
            );

            Mage::getSingleton('core/session')->unsPushcms();
        }
        //$pushpageData = json_decode($oauthClient->getLastResponse(), true);
        Mage::getSingleton('adminhtml/session')->addSuccess('CMS Pages updated successfully in live.');
        $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_page");
        Mage::app()->getResponse()->setRedirect($redirectUrl);
        
    }
}