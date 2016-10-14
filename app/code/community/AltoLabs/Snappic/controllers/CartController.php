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
        $quote = $this->_getCart()->getQuote();
        return $this->_output(array(
            'status' => 'success',
            'total' => ($quote ? $quote->getGrandTotal() : 0.0)
        ));
    }

    public function addAction()
    {
        $cart = $this->_getCart();
        $quote = $cart->getQuote();
        $core = Mage::helper('core');
        $payload = $core->jsonDecode($this->getRequest()->getRawBody());
        $sku = $payload['sku'];
        $product = Mage::helper('altolabs_snappic')->getProductBySku($sku);
        if ($product->getId()) {
            try {
                $quote->addProduct($product);
                $quote->collectTotals();
                $cart->save();
                Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                return $this->_output(array(
                    'status' => 'success',
                    'total' => ($quote ? $quote->getGrandTotal() : 0.0)
                ));
            } catch (Exception $e) {
                return $this->_output(array(
                    'error' => $e->getMessage(),
                    'total' => ($quote ? $quote->getGrandTotal() : 0.0)
                ));
            }
        } else {
            return $this->_output(array(
                'error' => 'The product was not found.',
                'total' => ($quote ? $quote->getGrandTotal() : 0.0)
            ));
        }
    }

    public function clearAction() {
        $cart = $this->_getCart();
        $quote = $cart->getQuote();
        $quote->removeAllItems();
        $quote->collectTotals();
        $cart->save();
        Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        $this->_output(array(
            'status' => 'success',
            'total' => $quote->getGrandTotal()
        ));
        $cart->save();
    }

    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    protected function _output($data)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($data));
        return $this;
    }
}
