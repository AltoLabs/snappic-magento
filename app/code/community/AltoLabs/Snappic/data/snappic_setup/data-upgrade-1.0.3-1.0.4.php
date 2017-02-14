<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

$installer = $this;
$installer->startSetup();

Mage::app()->getConfig()->saveConfig('rss/config/active', 1);
Mage::app()->getConfig()->saveConfig('rss/snappic/store', 1);
Mage::app()->getConfig()->saveConfig('rss/snappic/products', 1);
Mage::app()->getConfig()->reinit();

$helper = Mage::helper('altolabs_snappic');
$token = $helper->getToken();
$secret = $helper->getSecret();

$connect = Mage::getSingleton('altolabs_snappic/connect');
$connect->setSendable(array('token' => $token, 'secret' => $secret))
        ->notifySnappicApi('application/installed');

$installer->endSetup();
