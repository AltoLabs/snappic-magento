<?php
require_once 'Mage/Rss/controllers/CatalogController.php';

class AltoLabs_Snappic_SnappicController extends Mage_Rss_CatalogController {
    public function storeAction() {
      $this->renderFeed();
    }

    public function productsAction() {
      $this->renderFeed();
    }

    protected function renderFeed() {
        $this->getResponse()->setHeader('Content-type', 'text/xml; charset=UTF-8');
        $this->loadLayout(false);
        $this->renderLayout();
    }
}
