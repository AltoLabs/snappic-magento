<?php
/**
 * The Connect model communicates with the Snappic API
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */
class AltoLabs_Snappic_Model_Connect extends Mage_Core_Model_Abstract
{
    /**
     * TEMPORARY - define the connection endpoints and connection details
     *
     * @var string
     */
    const SHARED_SECRET = 'abc123456';
    const SNAPPIC_HOST = 'http://dockerhost:3000';
    // const SNAPPIC_HOST = 'https://api.snappic.io';

    /**
     * The payload to send to the Snappic API
     *
     * @var mixed
     */
    protected $_sendable;

    /**
     * This method is in charge of sending data to the Snappic API.
     *
     * @param  string  $topic    The type of event to be sent
     * @return boolean
     */
    public function notifySnappicApi($topic)
    {
        $client = new Zend_Http_Client(self::SNAPPIC_HOST . '/magento/webhooks');
        $client->setMethod(Zend_Http_Client::POST);

        //$client->setParameterPost('data', $sendable);
        $sendable = $this->seal($this->getSendable());
        $client->setRawData($sendable);
        $client->setHeaders(
            array(
                'Content-type'                => 'application/json',
                'X-Magento-Shop-Domain'       => Mage::getBaseUrl(),
                'X-Magento-Topic'             => $topic,
                'X-Magento-Webhook-Signature' => $this->signPayload($sendable)
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
     * Set the sendable payload for the Snappic API
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
     * Get the sendable payload for the Snappic API
     *
     * @return mixed The actual payload to be serialized and sent
     */
    public function getSendable()
    {
        return $this->_sendable;
    }

    /**
     * Return a JSON representation of the input data
     *
     * @param  mixed $input
     * @return string
     */
    public function seal($input)
    {
        return json_encode(array('data' => $input));
    }

    /**
     * Signs given data
     *
     * @param  string $data The data to be signed
     * @return string       The computed signature
     */
    public function signPayload($data)
    {
        return md5(self::SHARED_SECRET . $data);
    }
}
