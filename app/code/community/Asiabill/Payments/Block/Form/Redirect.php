<?php

class Asiabill_Payments_Block_Form_Redirect extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('asiabill/form/redirect.phtml');
    }

}