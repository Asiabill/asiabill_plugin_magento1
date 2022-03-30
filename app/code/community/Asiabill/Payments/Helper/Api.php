<?php
class Asiabill_Payments_Helper_Api extends Varien_Http_Adapter_Curl
{
    const DOMAIN = 'https://safepay.asiabill.com';
    const SANDBOX = 'https://testpay.asiabill.com';

    protected $_options = array();
    protected $url;
    protected $headers;

    public function __construct($mode){
        $this->url = $mode == 'test'? self::SANDBOX: self::DOMAIN;
    }

    public function request($interface,$params,$method = 'POST',$token=false){
        $uri = $this->url.'/services/v3/'.$interface;

        $this->addHeader('Content-type','application/json;charset=\'utf-8\'');
        if( $token ){
            $this->addHeader('sessionToken',$token);
        }

        $this->setConfig([
            'verifypeer' => false,
            'verifyhost' => false,
        ]);

        $body = json_encode($params);

        $this->write($method,$uri,'1.1',$this->headers,$body);

        $response = $this->read();

        $this->close();

        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);
        return json_decode($response,true);

    }

    public function addHeader($name, $value)
    {
        $this->headers[] = $name.': '.$value;
    }




}