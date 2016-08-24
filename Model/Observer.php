<?php
/* This file is Copyright Altolabs 2016.
 * Author: Pierre MARTIN hickscorp at gmail dot com.
*/

class Altolabs_Snappic_Model_Observer {
  public function detectOrderFulfillment(Varien_Event_Observer $observer) {
    Mage::Log('---------------->>>> Order Fulfillment.');
  }

  public function detectProductAttributeChanges(Varien_Event_Observer $observer) {
    Mage::Log('---------------->>>> Product Mass Changes.');
    $productIds = $observer->getEvent()->getProductIds();
    foreach ($productIds as $id) {
    }
    return $this;
  }

  public function detectProductChanges(Varien_Event_Observer $observer) {
    Mage::Log('---------------->>>> Single Product Changes.');
    $product = $observer->getEvent()->getProduct();
    if ($product->hasDataChanges()) {
    }
    return $this;
  }

  public function detectProductDeletion(Varien_Event_Observer $observer) {
    Mage::Log('---------------->>>> Product Deletion.');
    $product = $observer->getEvent()->getProduct();
    return $this;
  }

  private function notifySnappicApi($products) {
    $client = new Varien_Http_Client('https://api.snappic.com/webhooks/magento/product');
    $client->setMethod(Varien_Http_Client::POST);
    $client->setParameterPost('products', $products);
    try{
      $response = $client->request();
      if ($response->isSuccessful()) {
        // ...
      }
    } catch (Exception $e) {
    }
  }
}
