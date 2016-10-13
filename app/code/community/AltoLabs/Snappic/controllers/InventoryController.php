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
        return $this->_output($quantities);
    }

    protected function _getPayload()
    {
        return Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
    }

    protected function _getProductStockById($productId)
    {
        try {
          $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct(
              Mage::getModel('catalog/product')->load($productId)
          );
          return $stockItem->getManageStock() ? $stockItem->getQty() : 99;
        } catch (Exception $e) {
          return 99;
        }
    }

    protected function _output($data)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($data));
        return $this;
    }
}
