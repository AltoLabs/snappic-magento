<?php

class AltoLabs_Snappic_Model_Resource_Stock extends Mage_CatalogInventory_Model_Resource_Stock
{
    /**
     * Extends the core CatalogInventory stock resource model to capture the product IDs that
     * have their stock levels changed, dispatches a custom event which our obsever is connected
     * to so that Snappic can have product stock levels updated.
     *
     * @param Mage_CatalogInventory_Model_Stock $stock
     * @param array $productQtys
     * @param string $operator +/-
     * @return Mage_CatalogInventory_Model_Resource_Stock
     */
    public function correctItemsQty($stock, $productQtys, $operator = '-')
    {
        parent::correctItemsQty($stock, $productQtys, $operator);
        if (empty($productQtys)) {
            return $this;
        }

        Mage::dispatchEvent('altolabs_snappic_stock_update', array(
            'product_ids' => array_keys($productQtys)
        ));

        return $this;
    }
}
