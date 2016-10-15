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
    $block = $this->getLayout()->createBlock('core/text');
    $block->setText("
      <script src='https://cdn.rawgit.com/bytespider/jsOAuth/master/dist/jsOAuth-1.3.7.js'></script>
      <script>
        // Those simulate things we get from query parameters.
        var query = {
            key: '62f9cbfacecdf0946944f1dbe6633e88',
            secret: '93c56a90e1809b5890e0037f60f0723b',
            domain: 'dockerized-magento.local',
            admin_html: 'admin'
        };
        function urlFor(path) { return 'http://' + query.domain + path; }

        var oauth = new OAuth({
            consumerKey: query.key,
            consumerSecret: query.secret,
            requestTokenUrl: urlFor('/oauth/initiate'),
            authorizationUrl:  urlFor('/' + query.admin_html + '/oauth_authorize'),
            accessTokenUrl: urlFor('/oauth/token')
        });
        oauth.fetchRequestToken(openAuthoriseWindow, failureHandler);

        function openAuthoriseWindow(url) {
            var wnd = window.open(url, 'authorise');
            setTimeout(waitForPin, 100);
            function waitForPin() {
                if (wnd.closed) {
                    var pin = prompt('Please enter your PIN', '');
                    oauth.setVerifier(pin);
                    oauth.fetchAccessToken(getSomeData, failureHandler);
                } else {
                    setTimeout(waitForPin, 100);
                }
            }
        }

        function getSomeData() {
            oauth.get('http://dockerized-magento.local/api/rest/snappic/stores/current', function (data) {
                console.log(data.text);
            }, failureHandler);
        }

        function failureHandler(data) {
            console.error(data);
        }
      </script>
    ");
    $this->getLayout()->getBlock('content')->append($block);
    $this->renderLayout();
  }
}
