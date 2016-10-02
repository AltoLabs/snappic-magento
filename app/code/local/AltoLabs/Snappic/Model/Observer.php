<?php
/**
 * This file is Copyright Altolabs 2016.
 *
 * @category Mage
 *
 * @author   Pierre Martin <hickscorp@gmail.com>
 */
class Altolabs_Snappic_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     *
     * @return self
     */
    public function onControllerActionPredispatch(Varien_Event_Observer $observer)
    {
        Mage::Log('Snappic: onControllerActionPredispatch');
        $this->_ensureLandingPageStored();
        return $this;
    }

    public function onAfterProductAddToCart(Varien_Event_Observer $observer)
    {
        Mage::getSingleton('core/session')->setCartProductJustAdded(true);
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return self
     */
    public function onAfterOrderPlace(Varien_Event_Observer $observer)
    {
        Mage::Log('Snappic: onAfterOrderPlace');
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();
        $sendable = $this->getHelper()->getSendableOrderData($order);
        $this->getConnect()
            ->setSendable($sendable)
            ->notifySnappicApi('orders/paid');
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return self
     */
    public function onProductAfterSave(Varien_Event_Observer $observer)
    {
        Mage::Log('Snappic: onProductAfterSave');
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();
        if ($product->hasDataChanges()) {
            $product->load($product->getId());
            $this->getConnect()
                ->setSendable(array($this->getHelper()->getSendableProductData($product)))
                ->notifySnappicApi('products/update');
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return self
     */
    public function onProductAfterAttributeUpdate(Varien_Event_Observer $observer)
    {
        Mage::Log('Snappic: onProductAfterAttributeUpdate');
        $productIds = $observer->getEvent()->getProductIds();
        $productModel = Mage::getModel('catalog/product');
        $sendable = array();
        foreach ($productIds as $id) {
            $product = $productModel->load($id);
            $sendable[] = $this->getHelper()->getSendableProductData($product);
        }
        $this->getConnect()->setSendable($sendable)->notifySnappicApi('products/update');

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return self
     */
    public function onProductAfterDelete(Varien_Event_Observer $observer)
    {
        Mage::Log('Snappic: onProductAfterDelete');
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();
        $sendable = [$this->getHelper()->getSendableProductData($product)];
        $this->getConnect()
            ->setSendable($sendable)
            ->notifySnappicApi('products/delete');

        return $this;
    }

    /**
     * Get an instance of the Snappic Connect model.
     *
     * @return AltoLabs_Snappic_Model_Connect
     */
    public function getConnect()
    {
        return Mage::getSingleton('altolabs_snappic/connect');
    }

    /**
     * Get an instance of the Snappic data structure helper.
     *
     * @return AltoLabs_Snappic_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('altolabs_snappic');
    }

    /**
     * Sets the landing URL into the session.
     *
     * @return string
     */
    protected function _ensureLandingPageStored()
    {
        $session = Mage::getSingleton('core/session');
        $landingPage = $session->getLandingPage();
        if ($landingPage == null) {
            $session->setLandingPage(Mage::helper('core/url')->getCurrentUrl());
        }
        return $session->getLandingPage();
    }
}
