<?php
class AltoLabs_Snappic_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $html = <<<'HTML'
<div style="width:100%;height:auto"><snpc-main></snpc-main></div>
<script>
  var SnappicOptions = {
    ecommerce_provider: 'magento',
    webcomponents_url: 'http://store.snappic-staging.tk/preview/bower_components/webcomponentsjs/webcomponents-lite.min.js',
    styles_url: 'http://store.snappic-staging.tk/preview/styles/main.css',
    bundle_url: 'http://store.snappic-staging.tk/preview/elements/elements.vulcanized.html',
    soapjs_url: 'http://store.snappic-staging.tk/preview/scripts/soap.js',
    xml2json_url: 'http://store.snappic-staging.tk/preview/scripts/xml2json.min.js',
    enable_ig_error_detect: true,
    enable_infinite_scroll: true,
    enable_checkout_bar: true,
    enable_gallery: false,
    enable_options: false
  };
</script>
<script src="http://store.snappic-staging.tk/preview/scripts/app.js" async></script>
HTML;
        $block = $this->getLayout()->createBlock('core/text');
        $block->setText($html);
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }
}
