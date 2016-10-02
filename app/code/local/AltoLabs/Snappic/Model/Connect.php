<?php
/**
 * The Connect model communicates with the Snappic API.
 *
 * @category Mage
 *
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Model_Connect extends Mage_Core_Model_Abstract
{
    /**
     * Define the connection endpoints and connection details.
     *
     * @var string
     */
    const SNAPPIC_HOST = 'http://dockerhost:3000';

    /**
     * The payload to send to the Snappic API.
     *
     * @var mixed
     */
    protected $_sendable;

    /**
     * This method is in charge of sending data to the Snappic API.
     *
     * @param string $topic The type of event to be sent
     *
     * @return bool
     */
    public function notifySnappicApi($topic)
    {
        Mage::Log('Snappic: notifySnappicApi '.self::SNAPPIC_HOST.'/magento/webhooks');
        $client = new Zend_Http_Client(self::SNAPPIC_HOST.'/magento/webhooks');
        $client->setMethod(Zend_Http_Client::POST);
        $sendable = $this->seal($this->getSendable());
        $client->setRawData($sendable);
        $client->setHeaders(
            array(
                'Content-type' => 'application/json',
                'X-Magento-Shop-Domain' => $this->getHelper()->getStoreDomain(),
                'X-Magento-Topic' => $topic,
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
        Mage::Log('Snappic: getSnappicStore');
        if ($this->get('snappicStore') != NULL) {
          return $this->get('snappicStore');
        }

        $domain = $this->getHelper()->getStoreDomain();
        $client = new Zend_Http_Client(self::SNAPPIC_HOST.'/stores/current?domain='.$domain);
        $client->setMethod(Zend_Http_Client::GET);
        try {
            Mage::Log('Querying facebook ID for '.$domain.'...');
            $body = $client->request()->getBody();
            $snappicStore = json_decode($body);
            $this->setData('snappicStore', $snappicStore);
            return $snappicStore;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * This method checks whether or not a pixel ID is registered for the current store
     * and handles the retrieval and registration of one if not.
     */
    public function getFacebookId()
    {
        $facebookId = Mage::getStoreConfig('snappic/general/facebook_pixel_id');
        if ($facebookId == null || $facebookId == '') {
            Mage::log('Trying to fetch facebook ID from Sanppic API...');
            $facebookId = $this->getSnappicStore()->facebook_pixel_id;
            if ($facebookId != null && $facebookId != '') {
                Mage::log('Got facebook ID from api: '.$facebookId);
                Mage::app()->getConfig()->saveConfig('snappic/general/facebook_pixel_id', $facebookId);
            }
        }
        Mage::Log('Got Facebook ID '.$facebookId.'.');
        return $facebookId;
    }

    /**
     * Set the sendable payload for the Snappic API.
     *
     * @param mixed $sendable The actual payload to be serialized and sent
     *
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
     * @param mixed $input
     *
     * @return string
     */
    public function seal($input)
    {
        return json_encode(array('data' => $input));
    }

    /**
     * Signs given data.
     *
     * @param string $data The data to be signed
     *
     * @return string The computed signature
     */
    public function signPayload($data)
    {
        return md5($this->_snappicOAuthTokenSecret().$data);
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
    protected function _snappicOAuthTokenSecret()
    {
        $consumer = Mage::getModel('oauth/consumer')->load('Snappic', 'name');
        $tokens = Mage::getModel('oauth/token')->getCollection()->addFieldToFilter('consumer_id', $consumer->getId());
        foreach ($tokens as $token) {
            return $token->getSecret();
        }
    }
}
