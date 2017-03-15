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
    $storeId = Mage::app()->getStore()->getId();
    $payload = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
    $product = Mage::getModel('catalog/product')->load($payload['id'])->setStoreId($storeId);

    if (!$product->getId()) {
      Mage::log('Product with ID '.$payload['id'].' was not found.', null, 'snappic.log');
      $this->_output(array(
        'error' => 'The product was not found.',
        'total' => ($cart->getQuote()->getSubtotal() ?: '0.00')
      ));
      return;
    }

    try {
      # If product is part of configurables.
      $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
      if (count($parentIds) != 0) {
        foreach ($parentIds as $parentId) {
          $parent = Mage::getModel('catalog/product')->load($parentId);
          $attrOpts = $parent->getTypeInstance(true)->getConfigurableAttributesAsArray($parent);
          $attrs = array();
          foreach ($attrOpts as $attr) {
            $vals = array_column($attr['values'], 'value_index');
            $curVal = $product->getData($attr['attribute_code']);
            if (in_array($curVal, $vals)) {
              $attrs[$attr['attribute_id']] = $curVal;
            }
          }
          $req = new Varien_Object();
          $req->setData(array('product' => $parentId, 'qty' => 1, 'super_attribute' => $attrs));
          $cart->addProduct($parent, $req);
          break;
        }
      }
      # No parent ID just add the product.
      else {
        $cart->addProduct($product);
      }

      if (!$cart->getCustomerSession()->getCustomer()->getId() &&
          $cart->getQuote()->getCustomerId()) {
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
