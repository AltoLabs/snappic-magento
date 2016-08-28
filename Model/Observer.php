<?php
/* This file is Copyright Altolabs 2016.
 * Author: Pierre MARTIN hickscorp at gmail dot com.
*/

const SHARED_SECRET = 'abc123456';
const SNAPPIC_HOST = 'http://dockerhost:3000';
// const SNAPPIC_HOST = 'https://api.snappic.io';

class Altolabs_Snappic_Model_Observer {
  public function onAfterOrderPlace(Varien_Event_Observer $observer) {
    Mage::Log('-------------------->>> onAfterOrderPlace');
    $order = $observer->getEvent()->getOrder();
    $sendable = self::sendableOrderData($order);
    self::notifySnappicApi('orders/paid', $sendable);
  }

  public function onProductAfterSave(Varien_Event_Observer $observer) {
    Mage::Log('-------------------->>> onProductAfterSave');
    $product = $observer->getEvent()->getProduct();
    if ($product->hasDataChanges()) {
      $sendable = self::sendableProductData($product);
      self::notifySnappicApi('products/update', [$sendable]);
    }
    return $this;
  }

  public function onProductAfterAttributeUpdate(Varien_Event_Observer $observer) {
    Mage::Log('-------------------->>> onProductAfterAttributeUpdate');
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
    Mage::Log('-------------------->>> onProductAfterDelete');
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
    return [
      'id'              => $product->getId(),
      'title'           => $product->getName(),
      'description'     => $product->getDescription(),
      'handle'          => $product->getUrlKey(),
      'updated_at'      => $product->getUpdatedAt(),
      'images'          => self::sendableImagesData($product->getMediaGalleryImages()),
      'options'         => self::sendableOptionsData($product->getOptions())
    ];
  }

  static private function sendableImagesData(Varien_Data_Collection $images) {
    $sendable = [];
    foreach ($images as $image) {
      $sendable[] = self::sendableImageData($image);
    }
    return $sendable;
  }
  static private function sendableImageData($image) {
    return [
      'id'            => $image->getId(),
      'src'           => $image->getUrl(),
      'position'      => $image->getPosition(),
      'updated_at'    => $image->getUpdatedAt()
    ];
  }

  static private function sendableOptionsData(Varien_Data_Collection $options) {
    $sendable = [];
    foreach ($options as $option) {
      $sendable[] = self::sendableImageData($option);
    }
    return $sendable;
  }
  static private function sendableOptionData($option) {
    return [
      'id'            => $option->getId(),
      'name'          => $option->name(),
      'position'      => $option->getPosition(),
      'values'        => $option->getValues()
    ];
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
}
