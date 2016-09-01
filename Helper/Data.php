<?php
/**
 * Helper to return appropriate payload structures for various input types
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * If debug is enabled, output will be logged to var/log/altolabs.log
     * @var boolean
     */
    protected $_debug = false;

    /**
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getSendableProductData(Mage_Catalog_Model_Product $product)
    {
        $sendable = [
            'id'          => $product->getId(),
            'title'       => $product->getName(),
            'description' => $product->getDescription(),
            'handle'      => $product->getUKey(), // $product->getUrlKey() ???
            'updated_at'  => $product->getUpdatedAt(),
            'variants'    => $this->getSendableVariantsData($product),
            'images'      => $this->getSendableImagesData($product),
            'options'     => $this->getSendableOptionsData($product)
        ];
        return $this->_debugReturn($sendable);
    }

    /**
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getSendableVariantsData(Mage_Catalog_Model_Product $product)
    {
        $sendable = array();
        if ($product->isConfigurable()) {
            $subProducts = Mage::getModel('catalog/product_type_configurable')
                ->getUsedProducts(null, $product);
            foreach ($subProducts as $subProduct) { /** @var Mage_Catalog_Model_Product $subProduct */
                $sendable[] = array('id' => $subProduct->getId());
            }
        }
        return $sendable;
    }

    /**
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getSendableImagesData(Mage_Catalog_Model_Product $product)
    {
        $images = $product->getMediaGalleryImages();
        $imagesData = array();
        foreach ($images as $image) {
            $imagesData[] = [
                'id'         => $image->getId(),
                'src'        => $image->getUrl(),
                'position'   => $image->getPosition(),
                'updated_at' => $product->getUpdatedAt()
            ];
        }
        return $imagesData;
    }

    /**
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getSendableOptionsData(Mage_Catalog_Model_Product $product)
    {
        $options = $product->getOptions();
        $sendable = array();
        foreach ($options as $option) {
            $sendable[] = array(
                'id'       => $option->getId(),
                'name'     => $option->name(),
                'position' => $option->getPosition(),
                'values'   => $option->getValues()
            );
        }
        return $sendable;
    }

    /**
     * @param  Mage_Sales_Model_Order $order
     * @return array
     */
    public function getSendableOrderData(Mage_Sales_Model_Order $order)
    {
        $sendable = array(
            'id'                     => $order->getId(),
            'number'                 => $order->getId(),
            'order_number'           => $order->getId(),
            'email'                  => $order->getEmail(),
            'contact_email'          => $order->getEmail(),
            'total_price'            => $order->getTotalDue(),
            'total_price_usd'        => $order->getTotalDue(),
            'total_tax'              => '0.00',
            'taxes_included'         => true,
            'subtotal_price'         => $order->getTotalDue(),
            'total_line_items_price' => $order->getTotalDue(),
            'total_discounts'        => '0.00',
            'currency'               => $order->getBaseCurrency(),
            'financial_status'       => 'paid',
            'confirmed'              => true,
            'billing_address'        => array(
                'first_name' => '',
                'last_name'  => $order->getCustomerName(),
            )
        );

        return $this->_debugReturn($sendable);
    }

    /**
     * Optionally log the return value from each sendable function before returning it
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function _debugReturn($value)
    {
        if ($this->getDebugEnabled()) {
            Mage::log($value, null, 'altolabs.log');
        }
        return $value;
    }

    /**
     * Returns whether or not debug mode is enabled. Debug mode will enable logging for diagnostics and failures.
     * At some stage the configuration setting could be moved to a Magento system configuration setting...?
     *
     * @return boolean
     */
    public function getDebugEnabled()
    {
        return (bool) $this->_debug;
    }
}
