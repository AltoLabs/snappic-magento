<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Model_Connect extends Mage_Core_Model_Abstract
{
    /**
     * Define the connection endpoints and connection details.
     *
     * @var string
     */
    const SNAPPIC_HOST = 'https://dockerhost:3000';

    /**
     * The payload to send to the Snappic API.
     *
     * @var mixed
     */
    protected $_sendable;

    /**
     * This method is in charge of sending data to the Snappic API.
     *
     * @param  string $topic The type of event to be sent
     * @return bool
     */
    public function notifySnappicApi($topic)
    {
        Mage::log('Snappic: notifySnappicApi ' . self::SNAPPIC_HOST . '/magento/webhooks', null, 'snappic.log');
        $client = new Zend_Http_Client(self::SNAPPIC_HOST . '/magento/webhooks');
        $client->setMethod(Zend_Http_Client::POST);
        $sendable = $this->seal($this->getSendable());
        $client->setRawData($sendable);
        $client->setHeaders(
            array(
                'Content-type'                => 'application/json',
                'X-Magento-Shop-Domain'       => $this->getHelper()->getStoreDomain(),
                'X-Magento-Topic'             => $topic,
                'X-Magento-Webhook-Signature' => $this->signPayload($sendable),
            )
        );

        try {
            $response = $client->request();
            if (!$response->isSuccessful()) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * This method retrieves the remote store associated with this magento domain
     * and parses the JSON response.
     *
     * @return mixed
     */
    public function getSnappicStore()
    {
        Mage::log('Snappic: getSnappicStore', null, 'snappic.log');
        if ($this->get('snappicStore')) {
             return $this->get('snappicStore');
        }

        $domain = $this->getHelper()->getStoreDomain();
        $client = new Zend_Http_Client(self::SNAPPIC_HOST . '/stores/current?domain=' . $domain);
        $client->setMethod(Zend_Http_Client::GET);
        try {
            Mage::log('Querying facebook ID for ' . $domain . '...', null, 'snappic.log');
            $body = $client->request()->getBody();
            $snappicStore = Mage::helper('core')->jsonDecode($body, Zend_Json::TYPE_OBJECT);
            $this->setData('snappicStore', $snappicStore);
            return $snappicStore;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * This method checks whether or not a pixel ID is registered for the current store
     * and handles the retrieval and registration of one if not.
     *
     * @return string
     */
    public function getFacebookId()
    {
        $facebookId = Mage::getStoreConfig('snappic/general/facebook_pixel_id');
        if (empty($facebookId)) {
            Mage::log('Trying to fetch Facebook ID from Sanppic API...', null, 'snappic.log');
            $facebookId = $this->getSnappicStore()->facebook_pixel_id;
            if (!empty($facebookId)) {
                Mage::log('Got facebook ID from API: ' . $facebookId);
                Mage::app()->getConfig()->saveConfig('snappic/general/facebook_pixel_id', $facebookId);
            }
        }
        Mage::log('Got Facebook ID ' . $facebookId . '.', null, 'snappic.log');
        return $facebookId;
    }

    /**
     * Set the sendable payload for the Snappic API.
     *
     * @param  mixed $sendable The actual payload to be serialized and sent
     * @return self
     */
    public function setSendable($sendable)
    {
        $this->_sendable = $sendable;
        return $this;
    }

    /**
     * Get the sendable payload for the Snappic API.
     *
     * @return mixed The actual payload to be serialized and sent
     */
    public function getSendable()
    {
        return $this->_sendable;
    }

    /**
     * Return a JSON representation of the input data.
     *
     * @param  mixed $input
     * @return string
     */
    public function seal($input)
    {
        return Mage::helper('core')->jsonEncode(array('data' => $input));
    }

    /**
     * Signs given data.
     *
     * @param  string $data The data to be signed
     * @return string The computed signature
     */
    public function signPayload($data)
    {
        return md5($this->_snappicOauthTokenSecret() . $data);
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

    /**
     * Retrieves OAuth secret for the Snappic consumer.
     *
     * @return string The shared secret
     */
    protected function _snappicOauthTokenSecret()
    {
        /** @var Mage_Oauth_Model_Consumer $consumer */
        $consumer = Mage::getModel('oauth/consumer')->load('Snappic', 'name');
        if (!$consumer->getId()) {
            return '';
        }

        $tokens = Mage::getModel('oauth/token')
            ->getCollection()
            ->addFieldToFilter('consumer_id', $consumer->getId());

        $response = '';
        foreach ($tokens as $token) { /** @var Mage_Oauth_Model_Token $token */
            $response = $token->getSecret();
            break;
        }

        return $response;
    }
}
