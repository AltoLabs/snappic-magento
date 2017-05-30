<?php
/* This file is Copyright AltoLabs 2016. */

class AltoLabs_Snappic_Model_Connect extends Mage_Core_Model_Abstract {
  const STORE_DEFAULTS = array('facebook_pixel_id' => null);
  const SANDBOX_PIXEL_ID = '123123123';

  protected $_sendable;

  public function notifySnappicApi($topic, $bypassSandbox) {
    $helper = $this->getHelper();

    $host = $helper->getApiHost($bypassSandbox);
    Mage::log('Snappic: notifySnappicApi ' . $host . '/magento/webhooks', null, 'snappic.log');

    $client = new Zend_Http_Client($host . '/magento/webhooks');
    $client->setMethod(Zend_Http_Client::POST);
    $sendable = $this->seal($this->getSendable());
    $client->setRawData($sendable);
    $headers = array(
      'Content-type'                => 'application/json',
      'X-Magento-Shop-Domain'       => $helper->getDomain(),
      'X-Magento-Topic'             => $topic,
      'X-Magento-Webhook-Signature' => $this->signPayload($sendable),
    );
    $client->setHeaders($headers);

    try {
      $response = $client->request();
      if (!$response->isSuccessful()) {
        return false;
      }
    } catch (Exception $e) {
        return false;
    }
    return true;
  }

  public function getSnappicStore() {
    $helper = $this->getHelper();
    $domain = $helper->getDomain();
    $client = new Zend_Http_Client($helper->getApiHost() . '/stores/current?domain=' . $domain);
    $client->setMethod(Zend_Http_Client::GET);
    try {
      $body = $client->request()->getBody();
      return array_merge(self::STORE_DEFAULTS, Mage::helper('core')->jsonDecode($body));
    } catch (Exception $e) {
      return self::STORE_DEFAULTS;
    }
  }

  public function getStoredFacebookPixelId() {
    $helper = $this->getHelper();
    $configPath = $helper->getConfigPath('facebook/pixel_id');
    return Mage::getStoreConfig($configPath);
  }

  public function getFacebookId($fetchWhenNone) {
    $fbId = $this->getStoredFacebookPixelId();
    if (!$fetchWhenNone) { return $fbId; }

    $helper = $this->getHelper();
    $configPath = $helper->getConfigPath('facebook/pixel_id');
    if ((empty($fbId) || ($fbId == self::SANDBOX_PIXEL_ID && $helper->getIsProduction()))) {
      Mage::log('Fetching a Facebook ID from Snappic API...', null, 'snappic.log');
      $snappicStore = $this->getSnappicStore();
      $fbId = $snappicStore['facebook_pixel_id'];
      if (!empty($fbId)) {
        Mage::log('Got a Facebook ID from Snappic API: ' . $fbId, null, 'snappic.log');
        Mage::app()->getConfig()->saveConfig($configPath, $fbId);
      }
    }
    return $fbId;
  }

  public function setSendable($sendable) {
    $this->_sendable = $sendable;
    return $this;
  }

  public function getSendable() {
    return $this->_sendable;
  }

  protected function seal($input) {
    return Mage::helper('core')->jsonEncode(array('data' => $input));
  }

  protected function signPayload($data) {
    return md5($this->getHelper()->getSecret() . $data);
  }

  protected function getHelper() {
    return Mage::helper('altolabs_snappic');
  }
}
