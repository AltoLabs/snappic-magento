<?php
/* This file is Copyright AltoLabs 2016. */

class AltoLabs_Snappic_DataController extends Mage_Core_Controller_Front_Action {
  public function storeAction() {
    $this->loadLayout(false);
    if (!$this->_verifyToken()) { return $this->_renderUnauthorized(); }
    $this->getResponse()->setHeader('Content-type', 'application/json; charset=UTF-8');

    $helper = Mage::helper('altolabs_snappic');
    $store = Mage::app()->getDefaultStoreView();
    $storeName = $store->getGroup()->getName();
    $data = array(
      'id'                          => (int)$store->getId(),
      'name'                        => $storeName,
      'domain'                      => $helper->getDomain(),
      'iana_timezone'               => $this->_getIanaTimezone($store),
      'currency'                    => $store->getBaseCurrencyCode($store),
      'money_with_currency_format'  => $this->_getMoneyWithCurrencyFormat($store)
    );
    $this->getResponse()->setBody(json_encode($data));
  }

  public function productsAction() {
    $this->loadLayout(false);
    if (!$this->_verifyToken()) { return $this->_renderUnauthorized(); }
    $this->getResponse()->setHeader('Content-type', 'application/json; charset=UTF-8');

    $_productCollection = Mage::getResourceModel('catalog/product_collection')
                              ->addFieldToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
                              ->addAttributeToSelect('*')
                              ->addAttributeToFilter('status', array('eq' => 1))
                              ->setOrder('entity_id', 'desc')
                              ->setCurPage($this->_getPage())
                              ->setPageSize($this->_getPerPage());

    $data = array();
    $helper = Mage::helper('altolabs_snappic');
    if ($_productCollection->getSize()>0) {
      foreach($_productCollection as $_product) {
        $product = Mage::getModel('catalog/product')->load($_product['entity_id']);
        $data[] = $helper->getSendableProductData($product);
      }
    }
    $this->getResponse()->setBody(json_encode($data));
  }

  protected function _verifyToken() {
    $helper = Mage::helper('altolabs_snappic');
    $token = $this->getRequest()->getParam('token');
    return $helper->getToken() == $token;
  }

  protected function _renderUnauthorized() {
    $this->getResponse()
         ->clearHeaders()
         ->setHeader('HTTP/1.0', 401, true)
         ->setHeader('Content-Type', 'application/json; charset=UTF-8')
         ->setBody('Unauthorized');
  }

  protected function _getPage() {
    $page = (int)$this->getRequest()->getParam('page');
    return $page == null ? 1 : $page;
  }

  protected function _getPerPage() {
    $perPage = (int)$this->getRequest()->getParam('per_page');
    return empty($perPage) ? 50 : $perPage;
  }

  protected function _getIanaTimezone(Mage_Core_Model_Store $store) {
    return Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE, $store->getId());
  }

  protected function _getMoneyWithCurrencyFormat(Mage_Core_Model_Store $store) {
    $localeCode = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $store->getId());
    $currency = new Zend_Currency(null, $localeCode);
    $currency->setLocale($localeCode);
    $formatted = $currency->toCurrency(0.50);
    $unformatted = $currency->toCurrency(0.50, array('display' => Zend_Currency::NO_SYMBOL));
    return str_replace($unformatted, '{{amount}}', $formatted);
  }
}
