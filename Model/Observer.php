<?php
/* This file is Copyright Altolabs 2016.
 * Author: Pierre MARTIN hickscorp at gmail dot com.
*/

const SHARED_SECRET = 'abc123456';
const SNAPPIC_HOST = 'http://dockerhost:3000';
// const SNAPPIC_HOST = 'https://api.snappic.io';

class Altolabs_Snappic_Model_Observer {
  public function onBeforeFrontendInit(Varien_Event_Observer $observer) {
    Mage::Log('THIS IS BEINBG OBSERVED!<!KM!<!KJNL');
    die('-------------------------------->>>>>>> OBSERVED');
  }


  public function onAfterOrderPlace(Varien_Event_Observer $observer) {
    self::developerLoggedReturn('-------------------->>> onAfterOrderPlace');
    $order = $observer->getEvent()->getOrder();
    $sendable = self::sendableOrderData($order);
    self::notifySnappicApi('orders/paid', $sendable);
  }

  public function onProductAfterSave(Varien_Event_Observer $observer) {
    self::developerLoggedReturn('-------------------->>> onProductAfterSave');
    $product = $observer->getEvent()->getProduct();
    if ($product->hasDataChanges()) {
      $sendable = self::sendableProductData($product);
      self::notifySnappicApi('products/update', [$sendable]);
    }
    return $this;
  }

  public function onProductAfterAttributeUpdate(Varien_Event_Observer $observer) {
    self::developerLoggedReturn('-------------------->>> onProductAfterAttributeUpdate');
    $productIds = $observer->getEvent()->getProductIds();
    $productModel = Mage::getModel('catalog/product');
    $sendable = array();
    foreach ($productIds as $id) {
      $product = $productModel->load($id);
      $sendable[] = self::sendableProductData($product);
    }
    self::notifySnappicApi('products/update', $sendable);

    return $this;
  }

  public function onProductAfterDelete(Varien_Event_Observer $observer) {
    self::developerLoggedReturn('-------------------->>> onProductAfterDelete');
    $product = $observer->getEvent()->getProduct();
    $sendable = $product->getId();
    $this->notifySnappicApi('products/delete', $sendable);
    return $this;
  }

  /* This method is in charge of sending data to the Snappic API.
   * @param $topic string is the type of event to be sent.
   * @param $sendable object is the actual payload to be serialized and sent. */
  private function notifySnappicApi($topic, $sendable) {
    $client = new Zend_Http_Client(SNAPPIC_HOST . '/magento/webhooks');
    $client->setMethod(Zend_Http_Client::POST);
    //$client->setParameterPost('data', $sendable);
    $sendable = json_encode(['data' => $sendable ]);
    $client->setRawData($sendable);
    $client->setHeaders([
      'Content-type'                => 'application/json',
      'X-Magento-Shop-Domain'       => Mage::getBaseUrl(),
      'X-Magento-Topic'             => $topic,
      'X-Magento-Webhook-Signature' => self::signPayload($sendable)
    ]);
    try{
      $response = $client->request();
      if (!$response->isSuccessful()) return false;
    } catch (Exception $e) {
      return false;
    }
    return true;
  }

  static private function sendableProductData(Mage_Catalog_Model_Product $product) {
    $sendable = [
      'id'              => $product->getId(),
      'title'           => $product->getName(),
      'description'     => $product->getDescription(),
      'handle'          => $product->getUKey(),
      'updated_at'      => $product->getUpdatedAt(),
      'variants'        => self::sendableVariantsData($product),
      'images'          => self::sendableImagesData($product),
      'options'         => self::sendableOptionsData($product)
    ];
    return self::developerLoggedReturn($sendable);
  }

  static private function sendableVariantsData(Mage_Catalog_Model_Product $product) {
    $sendables = [];
    if ($product->isConfigurable()) {
      $subProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);
      foreach ($subProducts as $subProduct) {
        $sendable[] = [ 'id' => $subProduct->getId() ];
      }
    }
    return $sendables;
  }

  static private function sendableImagesData(Mage_Catalog_Model_Product $product) {
    $images = $product->getMediaGalleryImages();
    $images_data = [];
    foreach ($images as $image) {
      $images_data[] = [
        'id'            => $image->getId(),
        'src'           => $image->getUrl(),
        'position'      => $image->getPosition(),
        'updated_at'    => $product->getUpdatedAt()
      ];
    }
    return $images_data;
  }

  static private function sendableOptionsData(Mage_Catalog_Model_Product $product) {
    $options = $product->getOptions();
    $sendable = [];
    foreach ($options as $option) {
      $sendable[] = [
        'id'            => $option->getId(),
        'name'          => $option->name(),
        'position'      => $option->getPosition(),
        'values'        => $option->getValues()
      ];
    }
    return $sendable;
  }

  static private function sendableOrderData(Mage_Sales_Model_Order $order) {
    return [
      'id'                      => $order->getId(),
      'number'                  => $order->getId(),
      'order_number'            => $order->getId(),
      'email'                   => $order->getEmail(),
      'contact_email'           => $order->getEmail(),
      'total_price'             => $order->getTotalDue(),
      'total_price_usd'         => $order->getTotalDue(),
      'total_tax'               => '0.00',
      'taxes_included'          => true,
      'subtotal_price'          => $order->getTotalDue(),
      'total_line_items_price'  => $order->getTotalDue(),
      'total_discounts'         => '0.00',
      'currency'                => $order->getBaseCurrency(),
      'financial_status'        => 'paid',
      'confirmed'               => true,
      'billing_address'         => [
        'first_name'              => '',
        'last_name'               => $order->getCustomerName(),
      ]
    ];
  }

  /* Signs given data.
   * @param $data string is the data to be signed.
   * @return string as the computed signature. */
  static private function signPayload($data) {
    return md5(SHARED_SECRET . $data);
  }

  static private function developerLoggedReturn($what) {
    Mage::Log($what); return $what;
  }
}
