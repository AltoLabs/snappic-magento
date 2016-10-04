<?php
/**
 * Installs the Snappic extension and creates necessary API rules for connections to and from the Snappic API
 *
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

Mage::log('Performing AltoLabs Snappic Extension installation...', null, 'snappic.log');

/** @var Mage_OAuth_Helper_Data $oauthHelper */
$oauthHelper = Mage::helper('oauth');

Mage::log('Checking for the Snappic user...', null, 'snappic.log');
$user = Mage::getModel('admin/user')->load('snappic', 'username');
if (!$user->getId()) {
    Mage::log('User was not found, creating...', null, 'snappic.log');
    $user = Mage::getModel('admin/user')
        ->setData(
            array(
                'username'  => 'snappic',
                'firstname' => 'Snappic',
                'lastname'  => 'Snappic',
                'email'     => 'hi@snappic.io',
                'password'  => $oauthHelper->generateToken(),
                'is_active' => 1
            )
        )->save();
}

Mage::log('Checking for the Snappic Role...', null, 'snappic.log');
/** @var Mage_Api2_Model_Global_Role $adminRole */
$adminRole = Mage::getModel('api2/acl_global_role')
    ->load('Snappic', 'role_name');

if (!$adminRole->getId()) {
    Mage::log('Role was not found, creating...', null, 'snappic.log');
    $adminRole = Mage::getModel('api2/acl_global_role')
        ->setData(array('role_name' => 'Snappic'))
        ->save();
}

Mage::log('Configuring ACLs...', null, 'snappic.log');
$adminRoleId = $adminRole->getId();
foreach (array('snappic_product', 'snappic_store') as $snappicResource) {
    $globalRule = Mage::getModel('api2/acl_global_rule')
        ->load($snappicResource, 'resource_id');

    if ($globalRule->getId()) {
        continue;
    }

    Mage::log("Allowing the Snappic Role to retrieve $snappicResource...", null, 'snappic.log');
    Mage::getModel('api2/acl_global_rule')
        ->setRoleId($adminRoleId)
        ->setResourceId($snappicResource)
        ->setPrivilege('retrieve')
        ->save();
}

$aclsByResource = array(
  'snappic_store'   => 'name,store_group_name,store_domain,iana_timezone,currency,money_with_currency_format',
  'snappic_product' => 'id,title,description,price,handle,updated_at,variants,images,options'
);
foreach ($aclsByResource as $resource => $attributes) {
    Mage::log("Checking for ACLs for $resource...", null, 'snappic.log');

    /** @var Mage_Api2_Model_Acl_Filter_Attribute $aclEntry */
    $aclEntry = Mage::getModel('api2/acl_filter_attribute')
        ->getCollection()
        ->addFieldToFilter('user_type', Mage_Api2_Model_Acl_Global_Role::ROLE_CONFIG_NODE_NAME_ADMIN)
        ->addFieldToFilter('resource_id', $resource)
        ->addFieldToFilter('operation', Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ)
        ->getFirstItem();

    if (!$aclEntry->getId()) {
        Mage::log("Creating ACLs for $snappicResource...", null, 'snappic.log');
        $aclEntry = Mage::getModel('api2/acl_filter_attribute')
            ->setUserType(Mage_Api2_Model_Acl_Global_Role::ROLE_CONFIG_NODE_NAME_ADMIN)
            ->setResourceId($resource)
            ->setOperation(Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ);
    }

    Mage::log("Updating attributes for ACLs $snappicResource...", null, 'snappic.log');

    $aclEntry
        ->setAllowedAttributes($attributes)
        ->save();
}

Mage::log('Preparing the Snappic OAuth Consumer...', null, 'snappic.log');
$consumer = Mage::getModel('oauth/consumer')->load('Snappic', 'name');
if (!$consumer->getId()) {
    /** @var Mage_Oauth_Model_Consumer $consumer */
    $consumer = Mage::getModel('oauth/consumer')
        ->setData(
            array(
                'name'                  => 'Snappic',
                'key'                   => $oauthHelper->generateToken(),
                'secret'                => $oauthHelper->generateTokenSecret(),
                'callback_url'          => 'https://www.snappic.io',
                'rejected_callback_url' => 'https://www.snappic.io'
            )
        )->save();
}

// TODO: Prepare SOAP User and Role.

$connect = Mage::getSingleton('altolabs_snappic/connect');

Mage::log('Ensuring a Facebook pixel ID is set...', null, 'snappic.log');
$facebookId = $connect->getFacebookId();

Mage::log(
    'AltoLabs Snappic Setup successfuly completed with '.
    'Key=' . $consumer->getData('key') . ', ' .
    'Secret=' . $consumer->getData('secret') . ', ' .
    'FacebookId=' . $facebookId,
    null,
    'snappic.log'
);

$connect->setSendable(array(
            'key'         => $consumer->getData('key'),
            'secret'      => $consumer->getData('secret'),
            'facebook_id' => $facebookId))
        ->notifySnappicApi('application/installed');
