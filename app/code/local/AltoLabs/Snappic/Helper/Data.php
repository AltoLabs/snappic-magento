<?php
/**
 * Helper to return appropriate payload structures for various input types.
 *
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getSendableOrderData(Mage_Sales_Model_Order $order)
    {
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');

        return array(
            'id'                     => $order->getId(),
            'number'                 => $order->getId(),
            'order_number'           => $order->getId(),
            'email'                  => $order->getCustomerEmail(),
            'contact_email'          => $order->getCustomerEmail(),
            'total_price'            => $order->getTotalDue(),
            'total_price_usd'        => $order->getTotalDue(),
            'total_tax'              => '0.00',
            'taxes_included'         => true,
            'subtotal_price'         => $order->getTotalDue(),
            'total_line_items_price' => $order->getTotalDue(),
            'total_discounts'        => '0.00',
            'currency'               => $order->getBaseCurrencyCode(),
            'financial_status'       => 'paid',
            'confirmed'              => true,
            'landing_site'           => $session->getLandingPage(),
            'referring_site'         => $session->getLandingPage(),
            'billing_address'        => array(
                'first_name' => $order->getCustomerFirstname(),
                'last_name'  => $order->getCustomerLastname(),
            )
        );
    }

    /**
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getSendableProductData(Mage_Catalog_Model_Product $product)
    {
        return array(
            'id'          => $product->getId(),
            'title'       => $product->getName(),
            'description' => $product->getDescription(),
            'price'       => $product->getPrice(),
            'handle'      => $product->getUrlKey(),
            'updated_at'  => $product->getUpdatedAt(),
            'variants'    => $this->getSendableVariantsData($product),
            'images'      => $this->getSendableImagesData($product),
            'options'     => $this->getSendableOptionsData($product)
        );
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
            foreach ($subProducts as $subProduct) { /* @var Mage_Catalog_Model_Product $subProduct */
                $subProduct
                    ->setStoreId($product->getStoreId())
                    ->load($subProduct->getId());

                $sendable[] = array(
                    'id'         => $subProduct->getId(),
                    'title'      => $subProduct->getName(),
                    'sku'        => $subProduct->getSku(),
                    'price'      => $subProduct->getPrice(),
                    'updated_at' => $subProduct->getUpdatedAt()
                );
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

        foreach ($images as $image) { /* @var Varien_Object $image */
            $imagesData[] = array(
                'id'         => $image->getId(),
                'src'        => $image->getUrl(),
                'position'   => $image->getPosition(),
                'updated_at' => $product->getUpdatedAt()
            );
        }

        return $imagesData;
    }

    /**
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getSendableOptionsData(Mage_Catalog_Model_Product $product)
    {
        $options = $product->getProductOptionsCollection();
        $sendable = array();
        foreach ($options as $option) { /* @var Mage_Catalog_Model_Product_Option $option */
            $optionValues = array();
            foreach ($option->getValuesCollection() as $optionValue) {
                /* @var Mage_Catalog_Model_Product_Option_Value $optionValue */
                $optionValues[] = (string) $optionValue->getTitle();
            }

            $sendable[] = array(
                'id'       => $option->getId(),
                'name'     => $option->getTitle(),
                'position' => $option->getSortOrder(),
                'values'   => $optionValues,
            );
        }
        return $sendable;
    }

    /**
     * Gets the domain for the current store.
     * @return string
     */
    public function getStoreDomain()
    {
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $components = parse_url($url);
        return $components['host'];
    }
}
