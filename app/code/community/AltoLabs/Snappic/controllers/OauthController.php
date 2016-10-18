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
      $block->setText($this->pageHtml());
      $this->getLayout()->getBlock('content')->append($block);
      $this->renderLayout();
    }

    protected function pageHtml()
    {
        $helper = Mage::helper('altolabs_snappic');
        $domain = $helper->getDomain();
        $adminHtml = $helper->getAdminHtmlPath();

        $consumer = Mage::getModel('oauth/consumer')->load('Snappic', 'name');
        $consumerKey = $consumer->getKey();
        $consumerSecret = $this->getRequest()->getParam('secret');
        $expectedConsumerSecret = $consumer->getSecret();
        if ($consumerSecret != $expectedConsumerSecret) { return ''; }
        return "
          <script>
            var oauth = new OAuth({
              consumerKey: '$consumerKey',
              consumerSecret: '$consumerSecret',
              requestTokenUrl: 'http://$domain/oauth/initiate',
              authorizationUrl:  'http://$domain/$adminHtml/oauth_authorize',
              accessTokenUrl: 'http://$domain/oauth/token'
            });
            oauth.fetchRequestToken(openAuthoriseWindow, failureHandler);

            function openAuthoriseWindow(url) {
              var wnd = window.open(url, 'authorise');
              setTimeout(waitForPin, 100);
              function waitForPin() {
                if (wnd.closed) {
                  var pin = prompt('Please enter your PIN', '');
                  if (pin == null || pin == '') { return; }
                  oauth.setVerifier(pin);
                  oauth.fetchAccessToken(successHandler, failureHandler);
                } else {
                  setTimeout(waitForPin, 100);
                }
              }
            }
            function successHandler() {
              var ret = document.createElement('div');
              ret.setAttribute('id', 'store_access_token');
              ret.setAttribute('hidden', '');
              ret.innerHTML = '$consumerKey:$consumerSecret' + oauth.getAccessTokenKey() + ':' + oauth.getAccessTokenSecret();
              document.body.append(ret);
            }
            function failureHandler(data) {
              console.error(data);
            }
          </script>
        ";
    }
}
