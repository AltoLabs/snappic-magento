<?php
/* This file is Copyright AltoLabs 2016. */

class AltoLabs_Snappic_Helper_Data extends Mage_Core_Helper_Abstract {
  const CONFIG_PREFIX = 'snappic/';
  const API_HOST_DEFAULT = 'https://api.snappic.io';
  const STORE_ASSETS_HOST_DEFAULT = 'http://store.snappic.io';
  const SNAPPIC_ADMIN_URL_DEFAULT = 'http://www.snappic.io';

  public function getApiHost() {
    return $this->getEnvOrDefault('SNAPPIC_API_HOST', self::API_HOST_DEFAULT);
  }

  public function getConfigPath($suffix) {
    return self::CONFIG_PREFIX . $suffix;
  }

  public function getStoreAssetsHost() {
    return $this->getEnvOrDefault('SNAPPIC_STORE_ASSETS_HOST', self::STORE_ASSETS_HOST_DEFAULT);
  }

  public function getSnappicAdminUrl() {
    return $this->getEnvOrDefault('SNAPPIC_ADMIN_URL', self::SNAPPIC_ADMIN_URL_DEFAULT);
  }

  protected function getEnvOrDefault($key, $default=NULL) {
    $val = getenv($key);
    return empty($val) ? $default : $val;
  }

  public function getAdminHtmlPath() {
    return (string)Mage::app()->getConfig()->getNode('admin/routers/adminhtml/args/frontName') ?: 'admin';
  }

  public function getToken() {
    return $this->_generateTokenAndSecret('token');
  }

  public function getSecret() {
    return $this->_generateTokenAndSecret('secret');
  }

  protected function _generateTokenAndSecret($what) {
    $ret = Mage::getStoreConfig($this->getConfigPath('security/'.$what));
    if (!empty($ret)) { return $ret; }

    $token = Mage::helper('oauth')->generateToken();
    $secret = Mage::helper('oauth')->generateTokenSecret();
    Mage::app()->getConfig()->saveConfig($this->getConfigPath('security/token'), $token);
    Mage::app()->getConfig()->saveConfig($this->getConfigPath('security/secret'), $secret);
    Mage::app()->getConfig()->reinit();
    $data = array('token' => $token, 'secret' => $secret);
    return $data[$what];
  }

  public function getProductBySku($sku) {
    return Mage::getModel('catalog/product')->load(
      Mage::getModel('catalog/product')->getIdBySku($sku)
    );
  }

  public function getProductStockBySku($sku) {
    $product = $model->loadByProduct($this->getProductBySku($sku));
    return $this->getProductStock($product);
  }

  protected function getProductStock(Mage_Catalog_Model_Product $product) {
    $model = Mage::getModel('cataloginventory/stock_item');
    try {
      $stockItem = $model->loadByProduct($product);
      return $stockItem->getManageStock() ? (int)$stockItem->getQty() : 99;
    } catch (Exception $e) {
      return 99;
    }
  }

  public function getSendableOrderData(Mage_Sales_Model_Order $order) {
    $session = Mage::getSingleton('core/session');
    return array(
      'id'                      => $order->getId(),
      'number'                  => $order->getId(),
      'order_number'            => $order->getId(),
      'email'                   => $order->getCustomerEmail(),
      'contact_email'           => $order->getCustomerEmail(),
      'total_price'             => $order->getTotalDue(),
      'total_price_usd'         => $order->getTotalDue(),
      'total_tax'               => '0.00',
      'taxes_included'          => true,
      'subtotal_price'          => $order->getTotalDue(),
      'total_line_items_price'  => $order->getTotalDue(),
      'total_discounts'         => '0.00',
      'currency'                => $order->getBaseCurrencyCode(),
      'financial_status'        => 'paid',
      'confirmed'               => true,
      'landing_site'            => $session->getLandingPage(),
      'referring_site'          => $session->getLandingPage(),
      'billing_address'         => array(
        'first_name'              => $order->getCustomerFirstname(),
        'last_name'               => $order->getCustomerLastname(),
      )
    );
  }

  public function getSendableProductData(Mage_Catalog_Model_Product $product) {
    return array(
      'id'            => $product->getId(),
      'title'         => $product->getName(),
      'body_html'     => $product->getDescription(),
      'price'         => $product->getPrice(),
      'quantity'      => $this->getProductStock($product),
      'handle'        => $product->getUrlKey(),
      'variants'      => $this->getSendableVariantsData($product),
      'images'        => $this->getSendableImagesData($product),
      'options'       => $this->getSendableOptionsData($product),
      'updated_at'    => $product->getUpdatedAt(),
      'published_at'  => $product->getUpdatedAt()
    );
  }

  public function getSendableVariantsData(Mage_Catalog_Model_Product $product) {
    $sendable = array();
    if ($product->isConfigurable()) {
      $model = Mage::getModel('catalog/product_type_configurable');
      $subProducts = $model->getUsedProducts(null, $product);
      foreach ($subProducts as $subProduct) {
        $subProduct->setStoreId($product->getStoreId())
                   ->load($subProduct->getId());
        $sendable[] = array(
          'id'          => $subProduct->getId(),
          'title'       => $subProduct->getName(),
          'sku'         => $subProduct->getSku(),
          'price'       => $subProduct->getPrice(),
          'quantity'    => $this->getProductStock($subProduct),
          'updated_at'  => $subProduct->getUpdatedAt()
        );
      }
    }
    return $sendable;
  }

  public function getSendableImagesData(Mage_Catalog_Model_Product $product) {
    $images = $product->getMediaGalleryImages();
    $imagesData = array();
    foreach ($images as $image) {
      $imagesData[] = array(
        'id'          => $image->getId(),
        'src'         => $image->getUrl(),
        'position'    => $image->getPosition(),
        'updated_at'  => $product->getUpdatedAt()
      );
    }
    return $imagesData;
  }

  public function getSendableOptionsData(Mage_Catalog_Model_Product $product) {
    $options = $product->getProductOptionsCollection();
    $sendable = array();
    foreach ($options as $option) {
      $optionValues = array();
      foreach ($option->getValuesCollection() as $optionValue) {
        $optionValues[] = (string) $optionValue->getTitle();
      }
      $sendable[] = array(
        'id'        => $option->getId(),
        'name'      => $option->getTitle(),
        'position'  => $option->getSortOrder(),
        'values'    => $optionValues,
      );
    }
    return $sendable;
  }

  public function getDomain() {
    $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
    $components = parse_url($url);
    return $components['host'];
  }
}
