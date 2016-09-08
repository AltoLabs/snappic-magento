<?php
/**
 * Provide information about the current store
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Model_Api2_Snappic_Store_Rest_Admin_V1 extends AltoLabs_Snappic_Model_Api2_Snappic_Store
{
    /**
     * Get information about the current store
     * @return array
     */
    protected function _retrieve()
    {
        /** @var Mage_Core_Model_Store $store */
        $store = Mage::app()->getDefaultStoreView();

        // Add custom paramaters
        $store
            ->setName($store->getName())
            ->setStoreGroupName($store->getGroup()->getName())
            ->setStoreDomain($store->getBaseUrl())
            ->setIanaTimezone(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE, $store->getId()))
            ->setBaseCurrency($store->getBaseCurrencyCode())
            ->setMoneyWithCurrencyFormat(Mage::helper('core')->currency(10.00, true, false));

        return (array) $store->getData();
    }
}
