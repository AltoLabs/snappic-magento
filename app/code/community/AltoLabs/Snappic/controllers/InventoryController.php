<?php
/* This file is Copyright AltoLabs 2016. */

class AltoLabs_Snappic_InventoryController extends Mage_Core_Controller_Front_Action {
  public function indexAction() {
    $core = Mage::helper('core');
    $helper = Mage::helper('altolabs_snappic');
    $payload = $core->jsonDecode($this->getRequest()->getRawBody());
    $ids = $payload['ids'];
    $quantities = array();
    foreach ($ids as $id) {
      $product = Mage::getModel('catalog/product')->load($id);
      $quantities[$id] = $helper->getProductStock($product);
    }
    $this->getResponse()->setHeader('Content-type', 'application/json');
    $this->getResponse()->setBody(json_encode(array('status' => 'success', 'quantities' => $quantities)));
  }
}
