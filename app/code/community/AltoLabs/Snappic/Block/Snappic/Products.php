<?php

class AltoLabs_Snappic_Block_Snappic_Products extends Mage_Rss_Block_Catalog_Abstract {
    protected function _construct() {
        $this->setCacheKey(
            'rss_snappic_products'
            . $this->getRequest()->getParam('store_id') . '_'
            . $this->getRequest()->getParam('page') . '_'
        );
        $this->setCacheLifetime(1);
    }

    protected function _toHtml() {
        $storeId = $this->_getStoreId();
        $rssObj = Mage::getModel('rss/rss');
        $layer = Mage::getSingleton('catalog/layer')->setStore($storeId);
        $newurl = Mage::getUrl('rss/snappic/products');
        $title = Mage::helper('rss')->__('Snappic Normalized Products');
        $rssObj->_addHeader(array('title' => $title,
                                  'description' => $title,
                                  'link' => $newurl,
                                  'charset' => 'UTF-8'));

        $_productCollection = Mage::getResourceModel('catalog/product_collection')
                                  ->addFieldToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
                                  ->addAttributeToSelect('*')
                                  ->addAttributeToFilter('status', array('eq' => 1))
                                  ->setOrder('entity_id', 'desc')
                                  ->setCurPage($this->_getPage())
                                  ->setPageSize(5);

        if ($_productCollection->getSize()>0) {
            $args = array('rssObj' => $rssObj);
            foreach($_productCollection as $_product) {
                $args['product'] = $_product;
                $this->addProduct($args);
            }
        }
        return $rssObj->createRssXml();
    }

    public function addProduct($args) {
        Mage::dispatchEvent('rss_catalog_normalized_xml_callback', $args);
        $product = Mage::getModel('catalog/product')->load($args['product']['entity_id']);

        $data = array(
            'title'       => $product->getName(),
            'link'        => $product->getProductUrl(),
            'description' => $product->getDescription(),
            'content'     => json_encode(array(
                'price'       => $product->getPrice(),
                'quantity'    => $this->getQuantityForProduct($product),
                'handle'      => $product->getUrlKey(),
                'updated_at'  => $product->getUpdatedAt(),
                'variants'    => $this->getSendableVariantsData($product),
                'images'      => $this->getSendableImagesData($product),
                'options'     => $this->getSendableOptionsData($product)
            ))
        );

        $rssObj = $args['rssObj'];
        $rssObj->_addEntry($data);
    }

    /**
     * @param  Mage_Catalog_Model_Product $product
     * @return Integer
     */
    protected function getQuantityForProduct(Mage_Catalog_Model_Product $product) {
        $stockItem = $product->getStockItem();
        if ($stockItem) {
            return $stockItem->getIsInStock() ? 99 : 0;
        } else {
            return 99;
        }
    }

    /**
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getSendableVariantsData(Mage_Catalog_Model_Product $product) {
        $sendable = array();
        if ($product->isConfigurable()) {
            $subProducts = Mage::getModel('catalog/product_type_configurable')
                ->getUsedProducts(null, $product);
            foreach ($subProducts as $subProduct) {
                $subProduct
                    ->setStoreId($product->getStoreId())
                    ->load($subProduct->getId());

                $sendable[] = array(
                    'id'         => $subProduct->getId(),
                    'title'      => $subProduct->getName(),
                    'sku'        => $subProduct->getSku(),
                    'price'      => $subProduct->getPrice(),
                    'quantity'   => $this->getQuantityForProduct($subProduct),
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
    public function getSendableImagesData(Mage_Catalog_Model_Product $product) {
        $images = $product->getMediaGalleryImages();
        $imagesData = array();
        foreach ($images as $image) {
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
        foreach ($options as $option) {
            $optionValues = array();
            foreach ($option->getValuesCollection() as $optionValue) {
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

    protected function _getPage() {
        $page = (int)$this->getRequest()->getParam('page');
        return $page == null ? 1 : $page;
    }
}
