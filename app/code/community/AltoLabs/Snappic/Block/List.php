<?php
class AltoLabs_Snappic_Block_List extends Mage_Rss_Block_List {
    public function getRssMiscFeeds() {
        $feedPrefix = self::XML_PATH_RSS_METHODS.'/snappic/';
        $this->addRssFeed($feedPrefix . 'store', $this->__('Snappic Store Info'));
        $this->addRssFeed($feedPrefix . 'products', $this->__('Snappic Normalized Products'));
        return $this->getRssFeeds();
    }
}
