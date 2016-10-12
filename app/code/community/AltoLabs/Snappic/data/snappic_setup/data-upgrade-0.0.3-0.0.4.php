<?php
/**
 * This script takes care of storing a proper API key for the snappic frontend to use.
 *
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

$installer = $this;
$installer->startSetup();

# Commit #9161772 gets rid of the Session resource.
$installer->run("DROP TABLE IF EXISTS altolabs_snappic_session;");

$installer->endSetup();
