<?php
    require_once 'Mage/Checkout/controllers/CartController.php';
    class AltoLabs_Snappic_Checkout_CartController extends Mage_Checkout_CartController {
        public function addAction()
        {
            $formKey = $this->getFormKey();
            if (!$formKey || $formKey != Mage::getSingleton('core/session')->getFormKey()) {
                $newFormKey = Mage::getSingleton('core/session')->getFormKey();
                $this->getRequest()->setParams(array('form_key' => $newFormKey));
            }
            parent::addAction();
        }

        protected function getFormKey()
        {
            return $this->getRequest()->getParam('form_key', null);
        }
    }
?>
