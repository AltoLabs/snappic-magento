<?php
/* This file is Copyright AltoLabs 2016. */

class AltoLabs_Snappic_Model_Connect extends Mage_Core_Model_Abstract {
  const STORE_DEFAULTS = array('facebook_pixel_id' => null);

  protected $_sendable;

  public function notifySnappicApi($topic) {
    $helper = $this->getHelper();
    Mage::log('Snappic: notifySnappicApi ' . $helper->getApiHost() . '/magento/webhooks', null, 'snappic.log');
    $client = new Zend_Http_Client($helper->getApiHost() . '/magento/webhooks');
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
    Mage::log('Snappic: getSnappicStore', null, 'snappic.log');
    if ($this->get('snappicStore')) {
      return $this->get('snappicStore');
    }
    $helper = $this->getHelper();
    $domain = $helper->getDomain();
    $client = new Zend_Http_Client($helper->getApiHost() . '/stores/current?domain=' . $domain);
    $client->setMethod(Zend_Http_Client::GET);
    try {
      $body = $client->request()->getBody();
      $snappicStore = array_merge(
        self::STORE_DEFAULTS,
        Mage::helper('core')->jsonDecode($body)
      );
      $this->setData('snappicStore', $snappicStore);
      return $snappicStore;
    } catch (Exception $e) {
      return self::STORE_DEFAULTS;
    }
  }

  public function getFacebookId($fetchWhenNone) {
    $helper = $this->getHelper();
    $configPath = $helper->getConfigPath('facebook/pixel_id');
    $fbId = Mage::getStoreConfig($configPath);
    if (empty($fbId) && $fetchWhenNone) {
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
