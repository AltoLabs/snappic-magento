<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

class AltoLabs_Snappic_CartController extends Mage_Core_Controller_Front_Action
{

    public function totalAction()
    {
        return $this->_output(
            Mage::getSingleton('checkout/cart')->getQuote()->getGrandTotal()
        );
    }

    public function addAction()
    {
        $sku = $this->getRequest()->getParam('sku');
        $product = $this->_getProductBySku($sku);
        if ($product) {
            $cart = Mage::getSingleton('checkout/cart');
            $quote = $cart->getQuote();
            try {
                $quote->addProduct($product);
                $quote->collectTotals();
                $cart->save();
                Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
            } catch (Exception $e) {
                return $this->_output(array('error' => $e->getMessage()));
            }
            return $this->_output('ok');
        } else {
            return $this->_output(array('error' => 'The product was not found.'));
        }
    }

    protected function _getProductBySku($sku)
    {
        return Mage::getModel('catalog/product')->load(
            Mage::getModel('catalog/product')->getIdBySku($sku)
        );
    }

    protected function _output($data)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($data));
        return $this;
    }
}
