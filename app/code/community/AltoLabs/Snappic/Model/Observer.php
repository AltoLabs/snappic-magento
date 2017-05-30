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
         ->notifySnappicApi('orders/paid', false);
    return $this;
  }

  public function onAdminPageDisplayed(Varien_Event_Observer $observer) {
    if (!Mage::getSingleton('admin/session')->isLoggedIn()) { return; }

    $helper = $this->getHelper();

    if ($helper->getIsSandboxed()) {
      $messages = Mage::getSingleton('adminhtml/session')
                    ->getMessages()
                    ->getItemsByType(Mage_Core_Model_Message::SUCCESS);
      foreach ($messages as $message) {
        if (strpos($message->getText(), 'snappic_sandbox_notification') !== false) {
          return $this;
        }
      }
      Mage::getSingleton('adminhtml/session')->addSuccess(
        '<img src="http://snappic.io/static/img/general/logo.svg" style="padding:10px;background-color:#E85B52;">'.
        '<div style="font-size:16px;font-weight:400;letter-spacing:1.2px;line-height: 1.2;border:0;padding:0;'.
        'margin:24px 4px" class="snappic_sandbox_notification">The Snappic extension is running in a sandbox.'.
        ' If you\'ve installed it in your production environment, make sure to disable the sandboxing by going'.
        ' to System &rarr; Configuration &rarr; AltoLabs &rarr; Snappic &rarr; Environment &rarr; Sandboxed: No.</div>'
      );
    }

    else {
      $connect = $this->getConnect();
      $fbId = $connect->getStoredFacebookPixelId();
      $fakeFbId = empty($fbId) || $fbId == AltoLabs_Snappic_Model_Connect::SANDBOX_PIXEL_ID;
      if (!$fakeFbId) { return $this; }

      $messages = Mage::getSingleton('adminhtml/session')
                    ->getMessages()
                    ->getItemsByType(Mage_Core_Model_Message::SUCCESS);
      foreach ($messages as $message) {
        if (strpos($message->getText(), 'snappic_setup_notification') !== false) {
          return $this;
        }
      }

      $link = $helper->getSnappicAdminUrl().
        '/?login&pricing&provider=magento'.
        '&domain='.urlencode($helper->getDomain()).
        '&access_token='.urlencode($helper->getToken().':'.$helper->getSecret());
      Mage::getSingleton('adminhtml/session')->addSuccess(
        '<img src="http://snappic.io/static/img/general/logo.svg" style="padding:10px;background-color:#E85B52;">'.
        '<div style="font-size:16px;font-weight:400;letter-spacing:1.2px;line-height: 1.2;border:0;padding:0;'.
        'margin:24px 4px" class="snappic_setup_notification">Almost done!</div>'.
        '<script>window.Snappic={};window.Snappic.signup=function(){window.location=\''.$link.'\';};</script>'.
        '<img src="http://store.snappic.io/images/magento_continue_signup.png" style="width:100%;'.
        'max-width:460px;cursor:pointer;" onclick="Snappic.signup()">'
      );
    }
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
        // If the product gets disabled, directly delete it.
        if ((int)$product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
          $this->getConnect()
               ->setSendable(array($helper->getSendableProductData($product)))
               ->notifySnappicApi('products/delete', false);
        }
        // Schedule an update for this product.
        else {
          $data[] = $helper->getSendableProductData($product);
        }
      }
      // Product is simple. It might be part of a configurable or not...
      else {
        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
        // No parent IDs, product can be sent directly.
        if (count($parentIds) == 0) {
          // If the product gets disabled, directly delete it.
          if ((int)$product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
            $this->getConnect()
                 ->setSendable(array($helper->getSendableProductData($product)))
                 ->notifySnappicApi('products/delete', false);
          }
          // Schedule an update for this product.
          else {
            $data[] = $helper->getSendableProductData($product);
          }
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
           ->notifySnappicApi('products/update', false);
    }
  }

  public function onProductBeforeDelete(Varien_Event_Observer $observer) {
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
      $productId = (int)$product->getId();
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
          // We want to change the variants on this configurable, so it does
          // not include the deleted child.
          $parentData = $helper->getSendableProductData($parent);
          $variants = [];
          foreach ($parentData['variants'] as $variant) {
            if ((int)$variant['id'] == $productId) { continue; }
            $variants[] = $variant;
          }
          $parentData['variants'] = $variants;
          $data[] = $parentData;
        }
      }
    }

    if (count($data) != 0) {
      $this->getConnect()
           ->setSendable($data)
           ->notifySnappicApi($action, false);
    }
  }

  public function getConnect() {
    return Mage::getSingleton('altolabs_snappic/connect');
  }

  public function getHelper() {
    return Mage::helper('altolabs_snappic');
  }
}
