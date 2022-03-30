<?php

class Asiabill_Payments_Model_Source_CheckoutModel
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '1',
                'label' => Mage::helper('asiabill_payments')->__('In-page Checkout')
            ),
//            array(
//                'value' => '2',
//                'label' => Mage::helper('asiabill_payments')->__('Hosted Checkout')
//            ),
        );
    }
}
