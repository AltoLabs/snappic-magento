<?php
class AltoLabs_Snappic_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $soapUsername = 'Snappic';
        $soapApiKey = Mage::getModel('api/user')->load($soapUsername, 'username')->getApiKey();
        $storeAssetsHost = Mage::helper('altolabs_snappic')->getStoreAssetsHost();
        $block = $this->getLayout()->createBlock('core/text');
        $block->setText("
          <div style=\"width:100%;height:auto\"><snpc-main></snpc-main></div>
          <script>
            var SnappicOptions = {
              ecommerce_provider: 'magento',
              webcomponents_url: '$storeAssetsHost/preview/bower_components/webcomponentsjs/webcomponents-lite.min.js',
              styles_url: '$storeAssetsHost/preview/styles/main.css',
              bundle_url: '$storeAssetsHost/preview/elements/elements.vulcanized.html',
              soapjs_url: '$storeAssetsHost/preview/scripts/soap.js',
              xml2json_url: '$storeAssetsHost/preview/scripts/xml2json.min.js',
              enable_ig_error_detect: true,
              enable_infinite_scroll: true,
              enable_checkout_bar: true,
              enable_gallery: false,
              enable_options: false,
              MAGENTO_API_USER: '$soapUsername',
              MAGENTO_API_KEY: '$soapApiKey'
            };
          </script>
          <script src=\"$storeAssetsHost/preview/scripts/app.js\" async></script>
        ");
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }
}
