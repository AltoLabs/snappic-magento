<?php
/**
 * This file is Copyright Altolabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   Pierre Martin <hickscorp@gmail.com>
 */
class Altolabs_Snappic_Model_Observer
{
    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onControllerActionPredispatch(Varien_Event_Observer $observer)
    {
        Mage::log('-------------------->>> onControllerActionPredispatch', null, 'altolabs.log');
        $this->ensureLandingPageStored();
        return $this;
    }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onAfterOrderPlace(Varien_Event_Observer $observer)
    {
        Mage::log('-------------------->>> onAfterOrderPlace', null, 'altolabs.log');

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();

        $this->getConnect()
            ->setSendable($this->getHelper()->getSendableOrderData($order))
            ->notifySnappicApi('orders/paid');

        return $this;
    }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onProductAfterSave(Varien_Event_Observer $observer)
    {
        Mage::log('-------------------->>> onProductAfterSave', null, 'altolabs.log');

        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();

        if ($product->hasDataChanges()) {
            $this->getConnect()
                ->setSendable(array($this->getHelper()->getSendableProductData($product)))
                ->notifySnappicApi('products/update');
        }

        return $this;
    }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onProductAfterAttributeUpdate(Varien_Event_Observer $observer)
    {
        Mage::log('-------------------->>> onProductAfterAttributeUpdate', null, 'altolabs.log');

        $productIds = $observer->getEvent()->getProductIds();
        $productModel = Mage::getModel('catalog/product');

        $sendable = array();
        foreach ($productIds as $id) {
            $product = $productModel->load($id);
            $sendable[] = $this->getHelper()->getSendableProductData($product);
        }

        $this->getConnect()
            ->setSendable($sendable)
            ->notifySnappicApi('products/update');

        return $this;
    }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onProductAfterDelete(Varien_Event_Observer $observer)
    {
        Mage::log('-------------------->>> onProductAfterDelete', null, 'altolabs.log');

        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();
        $sendable = $product->getId();

        $this->getConnect()
            ->setSendable($sendable)
            ->notifySnappicApi('products/delete');

        return $this;
    }

    /**
     * Get an instance of the Snappic Connect model
     *
     * @return AltoLabs_Snappic_Model_Connect
     */
    public function getConnect()
    {
        return Mage::getSingleton('altolabs_snappic/connect');
    }

    /**
     * Get an instance of the Snappic data structure helper
     *
     * @return AltoLabs_Snappic_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('altolabs_snappic');
    }

    private function ensureLandingPageStored() {
        $session = Mage::getSingleton('core/session');
        $landingPage = $session->getLandingPage();
        if ($landingPage == null) {
            $session->setLandingPage(Mage::helper('core/url')->getCurrentUrl());
        }
        return $session->getLandingPage();
    }
}
