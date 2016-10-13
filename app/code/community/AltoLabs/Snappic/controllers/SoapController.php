<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

class AltoLabs_Snappic_SoapController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $session = Mage::getSingleton('core/session');
        $storeId = Mage::app()->getStore()->getStoreId();
        $sessionId = $session->getSoapSessionId();
        if (empty($sessionId)) {
            try {
                $username = 'Snappic';
                $apiKey = Mage::helper('altolabs_snappic')->getSoapApiKey();
                $wsdlUrl = Mage::getUrl('api.php') . '?type=v2_soap&wsdl=1';
                $client = new SoapClient($wsdlUrl);
                $sessionId = $client->login($username, $apiKey);
                $session->setSoapSessionId($sessionId);
            } catch (Exception $e) {
                $message = $e->getMessage();
                Mage::log("SOAP Exception: $message", NULL, 'snappic.log');
                die($message);
            }
        }
        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody(json_encode(array('storeId' => $storeId, 'sessionId' => $sessionId)));
    }
}
