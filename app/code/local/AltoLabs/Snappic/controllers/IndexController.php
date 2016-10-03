<?php
class AltoLabs_Snappic_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        // do stuff
        //$yourData = ['hello' => 'world'];
        //$this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json');
        //$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($yourData));

        // JL-NOTE: append bootstrap code
        $this->loadLayout();
        $html = <<<'HTML'
<div style="width:100%;height:auto"><snpc-main></snpc-main></div>
<script>
var SnappicOptions = {
  domain_override: "madave.com",
  ecommerce_provider: "magento",
  webcomponents_url: "http://localhost:4200/bower_components/webcomponentsjs/webcomponents-lite.min.js",
  styles_url: "http://localhost:4200/styles/main.css",
  bundle_url: "http://localhost:4200/elements/elements.html",
  soapjs_url: "http://localhost:4200/scripts/soap.js",
  enable_ig_error_detect: true,
  enable_infinite_scroll: true,
  enable_checkout_bar: true,
  enable_gallery: false,
  enable_options: false
};
</script>
<script src="http://localhost:4200/scripts/app.js" async></script>
HTML;
        $block = $this->getLayout()->createBlock('core/text');
        $block->setText($html);
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }
}
