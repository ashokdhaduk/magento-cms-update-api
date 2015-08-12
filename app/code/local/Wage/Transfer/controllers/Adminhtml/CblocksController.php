<?php
class Wage_Transfer_Adminhtml_CblocksController extends Mage_Adminhtml_Controller_action
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
            Mage::getSingleton('core/session')->setCmsBlockSession($postData['cms']);
        }

        $callback = $this->getRequest()->getParam('callback');

        $this->callbackUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cblocks/".$callback);    
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
            $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_block");
            Mage::app()->getResponse()->setRedirect($redirectUrl);
            return;
        }

        if ($state == Wage_Transfer_Model_OAuth_Client::OAUTH_STATE_ACCESS_TOKEN) {
            $acessToken = $oAuthClient->getAuthorizedToken();
        }
 
        $restClient = $acessToken->getHttpClient($params);
        $restClient->setUri($this->livehost.'/api/rest/cblocks');
        $restClient->setHeaders('Accept', 'application/json');
        $restClient->setMethod(Zend_Http_Client::GET);
        $response = $restClient->request();

        $responseContent = json_decode($response->getBody(), true);

        try{ 
            if(!empty($responseContent))
            {    
                foreach($responseContent as $cmsblocks) {

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
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e);
            $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_block");
            Mage::app()->getResponse()->setRedirect($redirectUrl);
            return;
        }

        Mage::getSingleton('adminhtml/session')->addSuccess('CMS Static Blocks Synchronize has been successfully.');
        $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_block");
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
            $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_block");
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
            $resourceUrl = $this->livehost.'/api/rest/cblocks/';

            $cmsBlockSession = Mage::getSingleton('core/session');
            $getCmsBlockSession = $cmsBlockSession->getCmsBlockSession();
            $cblocksData = array();

            if(Mage::getStoreConfig('transfer/general/log')){
                Mage::log(print_r($getCmsBlockSession, true), null, 'TransferLog.log');
            }
                
            $collection = Mage::getModel('cms/block')->getCollection()
                        ->addFieldToFilter('main_table.block_id', array('in' => $getCmsBlockSession));
            $collection->getSelect()->columns("GROUP_CONCAT(block_store.store_id SEPARATOR ',') AS store_ids")
                                    ->join(
                                            array('block_store' => $collection->getTable('cms/block_store')),
                                            'main_table.block_id = block_store.block_id',
                                            array()
                                        )
                                    ->group('main_table.block_id');
            foreach ($collection as $key => $value) {
                $cblocksData['items'][] = $value->getData();
            }

            if(Mage::getStoreConfig('transfer/general/log')){
                Mage::log(print_r($cblocksData, true), null, 'TransferLog.log');
            }

            $oauthClient->fetch($resourceUrl, json_encode($cblocksData), OAUTH_HTTP_METHOD_POST, array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ));

            foreach ($getCmsBlockSession as $keyarray => $values) {
                $pushBlockDate = Mage::getModel('cms/block')->load($values);
                $pushBlockDate->setPushToLive(Varien_Date::now())->save();
            }
            
            Mage::getSingleton('core/session')->unsCmsBlockSession();

        }

        Mage::getSingleton('adminhtml/session')->addSuccess('CMS Static Blocks updated successfully in live.');
        $redirectUrl = Mage::helper("adminhtml")->getUrl("adminhtml/cms_block");
        Mage::app()->getResponse()->setRedirect($redirectUrl);
        
    }
    
}