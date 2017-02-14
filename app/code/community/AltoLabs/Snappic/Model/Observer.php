<?php
/* This file is Copyright AltoLabs 2016. */

class Altolabs_Snappic_Model_Observer {
  public function createCustomerSession(Varien_Event_Observer $observer) {
    return $this;
  }

  public function onControllerActionPredispatch(Varien_Event_Observer $observer) {
    $session = Mage::getSingleton('core/session');
    $url = Mage::helper('core/url')->getCurrentUrl();
    $landingPage = $session->getLandingPage();
    if (!$landingPage || strpos($url, '/shopinsta') !== false) {
      $session->setLandingPage($url);
    }
    return $this;
  }

  public function onAfterProductAddToCart(Varien_Event_Observer $observer) {
    $product = Mage::getModel('catalog/product')->load(Mage::app()->getRequest()->getParam('product', 0));
    if ($product->getId()) {
      Mage::getSingleton('core/session')->setCartProductJustAdded(
        new Varien_Object(array(
            'id'            => $product->getId(),
            'qty'           => Mage::app()->getRequest()->getParam('qty', 1),
            'name'          => $product->getName(),
            'price'         => $product->getPrice(),
            'category_name' => Mage::getModel('catalog/category')->load($categories[0])->getName(),
        ))
      );
    }
    return $this;
  }

  public function onAfterOrderPlace(Varien_Event_Observer $observer) {
    $order = $observer->getEvent()->getOrder();
    $sendable = $this->getHelper()->getSendableOrderData($order);
    $this->getConnect()
         ->setSendable($sendable)
         ->notifySnappicApi('orders/paid');
    return $this;
  }

  public function onAdminPageDisplayed(Varien_Event_Observer $observer) {
    if (!Mage::getSingleton('admin/session')->isLoggedIn()) { return; }

    $helper = $this->getHelper();
    $flagPath = $helper->getConfigPath('system/completion_message');
    $flag = Mage::getStoreConfig($flagPath);

    if ($flag == 'displayed') { return $this; }

    Mage::app()->getConfig()->saveConfig($flagPath, 'displayed');
    Mage::app()->getConfig()->reinit();

    $domain = $helper->getDomain();
    $token = $helper->getToken();
    $secret = $helper->getSecret();
    $link = $helper->getSnappicAdminUrl().'/?login&provider=magento&domain='.urlencode($domain).'&access_token='.urlencode($token.':'.$secret);

    $html = <<<HTML
<img src="http://snappic.io/static/img/general/logo.svg" style="padding:10px;background-color:#E85B52;">
<div style="font-size:16px;font-weight:400;letter-spacing:1.2px;line-height: 1.2;border:0;padding:0;margin:24px 4px">Almost done!</div>
<script>window.Snappic={};window.Snappic.signup=function(){window.location='$link';};</script>
<img src="http://store.snappic.io/images/magento_continue_signup.png" style="width:100%;max-width:460px;cursor:pointer;" onclick="Snappic.signup()">
HTML;
    Mage::getSingleton('adminhtml/session')->addSuccess($html);
    return $this;
  }

  public function onProductAfterSave(Varien_Event_Observer $observer) {
    $productId = $observer->getEvent()->getProduct()->getId();
    $this->_handleProductsChanges(array($productId));
    return $this;
  }

  public function onProductAfterAttributeUpdate(Varien_Event_Observer $observer) {
    $productIds = $observer->getEvent()->getProductIds();
    $this->_handleProductsChanges($productIds);
    return $this;
  }

  protected function _handleProductsChanges($productIds) {
    $data = array();
    $helper = $this->getHelper();
    foreach ($productIds as $productId) {
      $product = Mage::getModel('catalog/product')->load($productId);
      // Product is configurable, send it directly.
      if ($product->isConfigurable()) {
        $data[] = $helper->getSendableProductData($product);
      }
      // Product is simple. It might be part of a configurable or not...
      else {
        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
        // No parent IDs, product can be sent directly.
        if (count($parentIds) == 0) {
          $data[] = $helper->getSendableProductData($product);
        }
        // Got parent IDs, send them instead.
        else {
          foreach ($parentIds as $parentId) {
            $parent = Mage::getModel('catalog/product')->load($parentId);
            // Save the parent to force the updated_at column to have changed.
            $parent->save();
            $data[] = $helper->getSendableProductData($parent);
          }
        }
      }
    }
    if (count($data) != 0) {
      $this->getConnect()
           ->setSendable($data)
           ->notifySnappicApi('products/update');
    }
  }

  public function onProductAfterDelete(Varien_Event_Observer $observer) {
    $data = [];
    $action = 'products/';
    $helper = $this->getHelper();
    $product = $observer->getEvent()->getProduct();
    // Product is configurable, delete it directly.
    if ($product->isConfigurable()) {
      $action .= 'delete';
      $data[] = $helper->getSendableProductData($product);
    }
    // Product is simple, it might be part of a configurable or not...
    else {
      $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
      // No parent IDs, product can be sent directly.
      if (count($parentIds) == 0) {
        $action .= 'delete';
        $data[] = $helper->getSendableProductData($product);
      }
      // Got parent IDs, send them instead.
      else {
        $action .= 'update';
        foreach ($parentIds as $parentId) {
          $parent = Mage::getModel('catalog/product')->load($parentId);
          // Save the parent to force the updated_at column to have changed.
          $parent->save();
          $data[] = $helper->getSendableProductData($parent);
        }
      }
    }

    if (count($data) != 0) {
      $this->getConnect()
           ->setSendable($data)
           ->notifySnappicApi($action);
    }
  }

  public function getConnect() {
    return Mage::getSingleton('altolabs_snappic/connect');
  }

  public function getHelper() {
    return Mage::helper('altolabs_snappic');
  }
}
