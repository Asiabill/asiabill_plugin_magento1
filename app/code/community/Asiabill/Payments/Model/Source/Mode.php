<?php

class Asiabill_Payments_Model_Source_Mode
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'test',
                'label' => Mage::helper('asiabill_payments')->__('Test')
            ),
            array(
                'value' => 'live',
                'label' => Mage::helper('asiabill_payments')->__('Live')
            ),
        );
    }
}
