<?php

class Asiabill_Payments_Model_Source_ElementsStyle
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'inner',
                'label' => Mage::helper('asiabill_payments')->__('One row')
            ),
            array(
                'value' => 'block',
                'label' => Mage::helper('asiabill_payments')->__('Two rows')
            ),
        );
    }
}
