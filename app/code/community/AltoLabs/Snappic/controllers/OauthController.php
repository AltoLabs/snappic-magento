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
      $this->getLayout()->getBlock('head')->addJs('jsoauth.js');
      $block = $this->getLayout()->createBlock('core/text');
      $block->setText($this->indexHtml());
      $this->getLayout()->getBlock('head')->append($block);
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

    protected function indexHtml()
    {
        $helper = Mage::helper('altolabs_snappic');
        $domain = $helper->getDomain();
        $adminHtml = $helper->getAdminHtmlPath();

        $consumer = Mage::getModel('oauth/consumer')->load('Snappic', 'name');
        $consumerKey = $consumer->getKey();
        $consumerSecret = $this->getRequest()->getParam('secret');
        if ($consumerSecret != $consumer->getSecret()) { return; }
        return "
          <a href='#' onclick='authorize()'>Authorize</a>
          <script>
            var oauth = new OAuth({
              consumerKey: '$consumerKey',
              consumerSecret: '$consumerSecret',
              requestTokenUrl: 'http://$domain/oauth/initiate',
              authorizationUrl:  'http://$domain/$adminHtml/oauth_authorize',
              accessTokenUrl: 'http://$domain/oauth/token',
              callbackUrl: 'http://$domain/shopinsta/oauth/callback'
            });

            this.setPin = function(pin) {
              oauth.setVerifier(pin);
              oauth.fetchAccessToken(
                function() {
                  token = '$consumerKey:$consumerSecret:'+oauth.getAccessTokenKey()+':'+oauth.getAccessTokenSecret();
                  var location = 'http://www.snappic.io#!/?'+
                    'provider=magento&'+
                    'domain='+encodeURIComponent('$domain')+'&'+
                    'access_token='+encodeURIComponent(token);
                  console.log(location);
                }, function(data) {
                  console.error(data);
                }
              );
            }
            function authorize() {
              oauth.fetchRequestToken(
                function(url) { window.open(url, 'authorise'); },
                function(data) { console.log(data); }
              );
            }
          </script>
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
          </script>
        ";
    }
}
