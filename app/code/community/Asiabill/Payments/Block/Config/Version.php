<?php
class Asiabill_Payments_Block_Config_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {

        $version = Mage::getConfig()->getModuleConfig("Asiabill_Payment")->version;
        return '<span class="notice">' . $version . '</span>';

    }
}