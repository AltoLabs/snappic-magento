<?php
/**
 * Provide information about the current store.
 *
 * @category Mage
 *
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Model_Api2_Snappic_Store_Rest_Admin_V1 extends AltoLabs_Snappic_Model_Api2_Snappic_Store
{
    /**
     * Get information about the current store.
     *
     * @return array
     */
    protected function _retrieve()
    {
        /** @var Mage_Core_Model_Store $store */
        $store = Mage::app()->getDefaultStoreView();

        // Add custom paramaters
        $store->setId((int) $store->getId())
               ->setName($store->getGroup()->getName())
               ->setStoreDomain($this->getHelper()->getStoreDomain())
               ->setIanaTimezone($this->_getIanaTimezone($store))
               ->setCurrency($store->getBaseCurrencyCode($store))
               ->setMoneyWithCurrencyFormat($this->_getMoneyWithCurrencyFormat($store));

        return (array) $store->getData();
    }

    protected function _getIanaTimezone($store)
    {
        return Mage::getStoreConfig(
        Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE, $store->getId()
      );
    }

    protected function _getMoneyWithCurrencyFormat($store)
    {
        $localeCode = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $store->getId());
        $currency = new Zend_Currency(null, $localeCode);
        $currency->setLocale($localeCode);
        $formatted = $currency->toCurrency(0.50);
        $unformatted = $currency->toCurrency(0.50, ['display' => Zend_Currency::NO_SYMBOL]);

        return str_replace($unformatted, '{{amount}}', $formatted);
    }

    /**
     * Get an instance of the Snappic data structure helper.
     *
     * @return AltoLabs_Snappic_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('altolabs_snappic');
    }
}
