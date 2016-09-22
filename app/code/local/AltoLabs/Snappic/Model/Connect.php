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

        //$client->setParameterPost('data', $sendable);
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
