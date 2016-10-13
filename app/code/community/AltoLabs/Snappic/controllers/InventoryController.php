<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

class AltoLabs_Snappic_InventoryController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $payload = $this->_getPayload();
        $productIds = $payload['ids'];
        $quantities = array();
        foreach ($productIds as $productId) {
            $quantities[$productId] = $this->_getProductStockById($productId);
        }
        return $this->output($quantities);
    }

    protected function _getPayload()
    {
        return Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
    }

    protected function _getProductStockById($productId)
    {
        $product = Mage::getModel('catalog/product')->load((int)$productId);
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        return $stockItem->getManageStock() ? $stockItem->getQty() : 99;
    }

    protected function output($data)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($data));
        return $this;
    }
}
