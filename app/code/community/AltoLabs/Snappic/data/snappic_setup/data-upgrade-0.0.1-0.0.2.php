<?php
  /**
 * Installs the Snappic persistent session tables
 *
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

$installer = $this;
$installer->startSetup();

$installer->run("
    DROP TABLE IF EXISTS {$installer->getTable('altolabs_snappic/session')};
    CREATE TABLE {$installer->getTable('altolabs_snappic/session')} (
      id int(11) unsigned zerofill NOT NULL AUTO_INCREMENT,
      soap_session_id varchar(250) DEFAULT NULL,
      quote_id varchar(250) DEFAULT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
  ");

$installer->endSetup();
