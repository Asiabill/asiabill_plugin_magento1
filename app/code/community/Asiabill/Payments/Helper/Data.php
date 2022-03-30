<?php

class Asiabill_Payments_Helper_Data extends Mage_Payment_Helper_Data
{

    public function __construct()
    {
        $this->cache = Mage::app()->getCache();
    }

    public function getSessionQuote()
    {
        // If we are in the back office
        if (Mage::app()->getStore()->isAdmin())
        {
            return Mage::getSingleton('adminhtml/sales_order_create')->getQuote();
        }
        // If we are a user
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    public function getBillingAddress($quote = null)
    {
        $quote = $this->getSessionQuote();

        if (!empty($quote) && $quote->getBillingAddress())
            return $quote->getBillingAddress();

        return null;
    }

    public function getShippingAddress($quote = null)
    {
        $quote = $this->getSessionQuote();

        if (!empty($quote) && $quote->getShippingAddress())
            return $quote->getShippingAddress();

        return null;
    }

    public function getAddress($type = 'billing'){

        if( $type == 'billing' ){
            $address = $this->getBillingAddress();
        }else{
            $address = $this->getShippingAddress();
        }

        $first_name = $address->getFirstname();
        $last_name = $address->getLastname();
        $phone = $address->getTelephone();
        $country = $address->getCountryId();
        $state = $address->getRegionCode();
        $city = $address->getCity();
        $street = $address->getStreet();
        $postcode = $address->getPostcode();
        $email = $address->getEmail();


        return array(
            'firstName' => $first_name,
            'lastName' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'address' => [
                'country' => $country,
                'state'=> $state,
                'city' => $city,
                'line1' => isset($street[0])?$street[0]:'',
                'line2' => isset($street[1])?$street[1]:'',
                'postalCode' => $postcode
            ]
        );
    }

    public function isMobile()
    {
        $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $useragent_commentsblock = preg_match('|\(.*?\)|', $useragent, $matches) > 0 ? $matches[0] : '';


        $mobile_os_list = array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
        $mobile_token_list = array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');

        $found_mobile = $this->CheckSubstrs($mobile_os_list, $useragent_commentsblock) || $this->CheckSubstrs($mobile_token_list,$useragent);

        if ($found_mobile){
            return 1;   //手机登录
        }
        return 0;  //电脑登录

    }

    public function CheckSubstrs($substrs, $text){
        foreach($substrs as $substr){
            if(false !== strpos($text, $substr)){
                return true;
            }
        }
        return false;
    }

    public function getCusIp(){

        if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            return trim( current( preg_split( '/,/',  $_SERVER['HTTP_X_FORWARDED_FOR']  ) ) );
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return '';
    }

}
