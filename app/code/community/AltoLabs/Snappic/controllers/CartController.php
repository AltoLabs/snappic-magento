<?php
/* This file is Copyright AltoLabs 2016. */

class AltoLabs_Snappic_CartController extends Mage_Core_Controller_Front_Action {
  public function preDispatch() {
    parent::preDispatch();
    $quote = $this->_getCart()->getQuote();
    if ($quote->getIsMultiShipping()) {
      $quote->setIsMultiShipping(false);
    }
    return $this;
  }

  public function totalAction() {
    $this->_output(array(
      'status' => 'success',
      'total' => ($this->_getCart()->getQuote()->getSubtotal() ?: '0.00')
    ));
  }

  public function addAction() {
    $cart = $this->_getCart();
    $payload = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
    $product = Mage::helper('altolabs_snappic')
                  ->getProductBySku($payload['sku'])
                  ->setStoreId(Mage::app()->getStore()->getId());
    if ($product->getId()) {
      try {
        $cart->addProduct($product);
        if (!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
          $cart->getQuote()->setCustomerId(null);
        }
        $cart->save();
        $this->_getSession()->setCartWasUpdated(true);
        $this->_output(array(
          'status' => 'success',
          'total' => ($cart->getQuote()->getSubtotal() ?: '0.00')
        ));
      } catch (Exception $e) {
        $this->_output(array(
          'error' => $e->getMessage(),
          'total' => ($cart->getQuote()->getSubtotal() ?: '0.00')
        ));
      }
    } else {
      $this->_output(array(
        'error' => 'The product was not found.',
        'total' => ($cart->getQuote()->getSubtotal() ?: '0.00')
      ));
    }
  }

  public function clearAction() {
    $this->_getCart()->truncate()->save();
    $this->_getSession()->setCartWasUpdated(true);
    $this->_output(array(
      'status' => 'success',
      'total' => ($this->_getCart()->getQuote()->getSubtotal() ?: '0.00')
    ));
  }

  protected function _getSession() {
    return Mage::getSingleton('checkout/session');
  }

  protected function _getCart() {
    return Mage::getSingleton('checkout/cart');
  }

  protected function _output($data) {
    $this->getResponse()->setHeader('Content-type', 'application/json');
    $this->getResponse()->setBody(json_encode($data));
  }
}
