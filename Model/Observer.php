<?php
/* This file is Copyright Altolabs 2016.
 * Author: Pierre MARTIN hickscorp at gmail dot com.
*/

const SHARED_SECRET = 'abc123456';

class Altolabs_Snappic_Model_Observer {
  public function onAfterOrderPlace(Varien_Event_Observer $observer) {
    $order = $observer->getEvent()->getOrder();
    $sendable = [self::sendableOrderData($order)];
    self::notifySnappicApi('orders/paid', $sendable);
  }

  public function onProductAfterSave(Varien_Event_Observer $observer) {
    $product = $observer->getEvent()->getProduct();
    if ($product->hasDataChanges()) {
      $sendable = [self::sendableProductData($product)];
      self::notifySnappicApi('products/update', $sendable);
    }
    return $this;
  }

  public function onProductAfterAttributeUpdate(Varien_Event_Observer $observer) {
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
    $product = $observer->getEvent()->getProduct();
    $sendable = [$product->getId()];
    $this->notifySnappicApi('products/delete', $sendable);
    return $this;
  }

  /* This method is in charge of sending data to the Snappic API.
   * @param $topic string is the type of event to be sent.
   * @param $sendable object is the actual payload to be serialized and sent. */
  private function notifySnappicApi($topic, $sendable) {
    Mage::Log(self::getStoreDomain());
    $sendable = json_encode($sendable);
    $client = new Zend_Http_Client('https://api.snappic.io/webhooks/magento/product');
    $client->setMethod(Zend_Http_Client::POST);
    $client->setHeaders([
      'Content-type'                => 'application/json',
      'X-Magento-Shop-Domain'       => self::getStoreDomain(),
      'X-Magento-Topic'             => $topic,
      'X-Magento-Webhook-Signature' => self::signPayload($sendable)
    ]);
    $client->setParameterPost('data', $sendable);
    try{
      $response = $client->request();
      if (!$response->isSuccessful()) return false;
    } catch (Exception $e) {
      return false;
    }
    return true;
  }

  static private function getStoreDomain() {
    $baseUrl = Mage::getBaseUrl();
    return substr($baseUrl, strpos($baseUrl, '://') + 3);
  }

  static private function sendableProductData(Mage_Catalog_Model_Product $product) {
    return [
      'title'           => $product->getName(),
      'description'     => $product->getDescription(),
      'handle'          => $product->getUrlKey(),
      'updated_at'      => $product->getUpdatedAt(),
      'image_url'       => $product->getImageUrl()
    ];
  }

  static private function sendableOrderData(Mage_Sales_Model_Order $order) {
    return [
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
      ],
      'line_items'              => self::sendableOrderItems($order)
    ];
  }

  static private function sendableOrderItems(Mage_Sales_Order_Item_Collection $items) {
    $items = [];
    foreach ($items as $item) {
      $items[] = [
        'quantity'        => $item->getQtyToShip(),
        'price'           => $item->getPrice(),
        'taxable'         => true,
        'total_discount'  => '0.00',
        'product_id'      => 0,
        'variant_id'      => 0
      ];
    }
    return $items;
  }

  /* Signs given data.
   * @param $data string is the data to be signed.
   * @return string as the computed signature. */
  static private function signPayload($data) {
    return md5(SHARED_SECRET . $data);
  }
}
