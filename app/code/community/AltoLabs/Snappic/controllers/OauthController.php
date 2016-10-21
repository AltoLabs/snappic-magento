<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

class AltoLabs_Snappic_OauthController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
      $this->loadLayout();

      $this->getLayout()->getBlock('head')->addItem('skin_js', 'js/snappic/jsoauth.js');

      $block = $this->getLayout()->createBlock('core/text');
      $block->setText($this->indexHeadBlock());
      $this->getLayout()->getBlock('head')->append($block);

      $block = $this->getLayout()->createBlock('core/text');
      $block->setText($this->indexBodyBlock());
      $this->getLayout()->getBlock('content')->append($block);

      $this->renderLayout();
    }

    public function callbackAction()
    {
      $this->loadLayout();

      $block = $this->getLayout()->createBlock('core/text');
      $block->setText($this->callbackHtml());
      $this->getLayout()->getBlock('content')->append($block);

      $this->renderLayout();
    }

    protected function indexHeadBlock()
    {
        $helper = Mage::helper('altolabs_snappic');
        $domain = $helper->getDomain();
        $adminHtml = $helper->getAdminHtmlPath();

        $consumer = Mage::getModel('oauth/consumer')->load('Snappic', 'name');
        $consumerKey = $consumer->getKey();
        $consumerSecret = $this->getRequest()->getParam('secret');
        if ($consumerSecret != $consumer->getSecret()) { return; }
        return "
          <script>
            var oauth = new OAuth({
              consumerKey: '$consumerKey',
              consumerSecret: '$consumerSecret',
              requestTokenUrl: 'https://$domain/oauth/initiate',
              authorizationUrl:  'https://$domain/$adminHtml/oauth_authorize',
              accessTokenUrl: 'https://$domain/oauth/token',
              callbackUrl: 'https://$domain/shopinsta/oauth/callback'
            });

            var authorizeUrl = ''
            function authorize() { window.open(authorizeUrl, 'authorise'); }
            oauth.fetchRequestToken(
              function(url) {
                authorizeUrl = url
                // TODO: Have the link appear.
              },
              function(data) {
                // TODO: Show an error.
                console.log(data);
              }
            );

            this.setPin = function(pin) {
              // TODO: Have the link disappear, show a "Please wait" thingy, as we are hitting OAuth again.
              oauth.setVerifier(pin);
              oauth.fetchAccessToken(
                function() {
                  // TODO: Have the link read a "All done, redirecting..." thingy, as we are ready to move to snappic admin.
                  token = '$consumerKey:$consumerSecret:'+oauth.getAccessTokenKey()+':'+oauth.getAccessTokenSecret();
                  window.location = 'http://www.snappic.io?'+
                    'provider=magento&'+
                    'domain='+encodeURIComponent('$domain')+'&'+
                    'access_token='+encodeURIComponent(token);
                }, function(data) {
                  // TODO: Show an error.
                  console.error(data);
                }
              );
            }
          </script>";
    }

    protected function indexBodyBlock() {
      // TODO: Have an empty DIV created, with a "PLEASE WAIT."
      return "
        <h2>
          <a href='#' onclick='authorize()'>Click here to authorize Snappic.</a>
        </h3>
      ";
    }

    protected function callbackHtml()
    {
        return "
          <script>
            qs = document.location.search.split('+').join(' ');
            var params = {}, tokens, re = /[?&]?([^=]+)=([^&]*)/g;
            while (tokens = re.exec(qs)) { params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]); }
            window.opener.setPin(params['oauth_verifier']);
            window.close();
          </script>";
    }
}
