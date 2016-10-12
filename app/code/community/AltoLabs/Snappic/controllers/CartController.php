<?php
class AltoLabs_Snappic_CartController extends Mage_Core_Controller_Front_Action
{
    public function totalAction()
    {
        return $this->output($this->_getCart()->getQuote()->getGrandTotal());
    }

    public function addAction()
    {
        $sku = $this->getRequest()->getParam('sku');
        $product = $this->_getProductBySku($sku);
        if ($product) {
            $cart = $this->_getCart();
            $quote = $cart->getQuote();
            try {
                $quote->addProduct($product);
                $quote->collectTotals();
                $cart->save();
                $this->_getSession()->setCartWasUpdated(true);
            } catch (Exception $e) {
                return $this->output(array('error' => $e->getMessage()));
            }
            return $this->output('ok');
        } else {
            return $this->output(array('error' => 'The product was not found.'));
        }
    }

    protected function _getProductBySku($sku)
    {
        return Mage::getModel('catalog/product')->load(
            Mage::getModel('catalog/product')->getIdBySku($sku)
        );
    }

    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    protected function output($data)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($data));
        return $this;
    }
}