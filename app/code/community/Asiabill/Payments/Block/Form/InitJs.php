<?php

class Asiabill_Payments_Block_Form_InitJs extends Mage_Payment_Block_Form
{
    public $asiabill;
    public $api;
    public $billingInfo;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('asiabill/form/init_js.phtml');
        $this->asiabill =  Mage::getModel('asiabill_payments/payment');
        $this->api = new Asiabill_Payments_Helper_Api($this->asiabill->mode);
        $this->billingInfo = Mage::helper('asiabill_payments')->getAddress();
    }

    public function hasBillingAddress(){
        return isset($this->billingInfo) && !empty($this->billingInfo);
    }

    public function getBillingAddress(){
        return $this->billingInfo;
    }

    public function getToken(){
        return $this->asiabill->getToken();
    }

    public function getStyle(){
        return $this->asiabill->getConfigData('elements_style');
    }

    public function getMode(){
        return $this->asiabill->mode;
    }

}