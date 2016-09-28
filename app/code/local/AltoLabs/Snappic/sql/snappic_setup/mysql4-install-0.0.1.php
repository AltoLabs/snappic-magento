<?php

$endl = "\r\n";

Mage::Log("Performing AltoLabs Snappic Extension installation...$endl");

$oauthHelper = Mage::helper('oauth');

Mage::Log("Checking for the Snappic user...$endl");
$user = Mage::getModel('admin/user')->load('snappic', 'username');
if (!$user->getId()) {
  Mage::Log("User was not found, creating...$endl");
  $user = Mage::getModel('admin/user')
      ->setData(array(
          'username'  => 'snappic',
          'firstname' => 'Snappic',
          'lastname'  => 'Snappic',
          'email'     => 'hi@snappic.io',
          'password'  => $oauthHelper->generateToken(),
          'is_active' => 1))
      ->save();
}

Mage::Log("Checking for the Snappic Role...$endl");
$adminRole = Mage::getModel('api2/acl_global_role')->load('Snappic', 'role_name');
if (!$adminRole->getId()) {
    Mage::Log("Role was not found, creating...$endl");
    $adminRole = Mage::getModel('api2/acl_global_role')
        ->setData(array('role_name' => 'Snappic'))
        ->save();
}

Mage::Log("Configuring ACLs...$endl");
$adminRoleId = $adminRole->getId();
foreach (['snappic_product', 'snappic_store'] as $snappicResource) {
    $globalRule = Mage::getModel('api2/acl_global_rule')->load($snappicResource, 'resource_id');
    if ($globalRule->getId()) { continue; }
    Mage::Log("Allowing the Snappic Role to retrieve $snappicResource...$endl");
    Mage::getModel('api2/acl_global_rule')
        ->setRoleId($adminRoleId)
        ->setResourceId($snappicResource)
        ->setPrivilege('retrieve')->save();
}


$aclsByResource = array(
  'snappic_store' => 'name,store_group_name,store_domain,iana_timezone,currency,money_with_currency_format',
  'snappic_product' => 'id,title,description,price,handle,updated_at,variants,images,options'
);
foreach ($aclsByResource as $resource => $attributes) {
  Mage::Log("Checking for ACLs for $resource...$endl");
    $aclEntry = Mage::getModel('api2/acl_filter_attribute')
        ->getCollection()
        ->addFieldToFilter('user_type', Mage_Api2_Model_Acl_Global_Role::ROLE_CONFIG_NODE_NAME_ADMIN)
        ->addFieldToFilter('resource_id', $resource)
        ->addFieldToFilter('operation', Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ)
        ->getFirstItem();
    if (!$aclEntry->getId()) {
      Mage::Log("Creating ACLs for $snappicResource...$endl");
      $aclEntry = Mage::getModel('api2/acl_filter_attribute')
                      ->setUserType(Mage_Api2_Model_Acl_Global_Role::ROLE_CONFIG_NODE_NAME_ADMIN)
                      ->setResourceId($resource)
                      ->setOperation(Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ);
    }
    Mage::Log("Updating attributes for ACLs $snappicResource...$endl");
    $aclEntry->setAllowedAttributes($attributes)->save();
}

Mage::Log("Preparing the Snappic OAuth Consumer...$endl");
$consumer = Mage::getModel('oauth/consumer')->load('Snappic', 'name');
if (!$consumer->getId()) {
    $consumer = Mage::getModel('oauth/consumer')
        ->setData(array(
            'name' => 'Snappic',
            'key' => $oauthHelper->generateToken(),
            'secret' => $oauthHelper->generateTokenSecret(),
            'callback_url' => 'https://www.snappic.io',
            'rejected_callback_url' => 'https://www.snappic.io'))
        ->save();
}

Mage::getStoreConfig('snappic/general/facebook_pixel_id')

Mage::Log("AltoLabs Snappic Setup complete.$endl");
Mage::Log('Key=' . $consumer->getData('key') . ', Secret=' . $consumer->getData('secret') . $endl);


Mage::app()->getConfig()->saveConfig('snappic/general/facebook_pixel_id', $facebookID)
