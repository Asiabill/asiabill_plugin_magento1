<?php

class Asiabill_Payments_Block_Form_Method extends Mage_Payment_Block_Form
{
    public $asiabill;
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('asiabill/form/method.phtml');
        $this->asiabill =  Mage::getModel('asiabill_payments/payment');
    }

    public function checkMode(){
        return $this->asiabill->getConfigData('checkout_model');
    }

}