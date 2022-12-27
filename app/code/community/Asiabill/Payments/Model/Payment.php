<?php

ini_set("display_errors", "on");
error_reporting(E_ALL);

include_once __DIR__."/../classes/AsiabillIntegration.php";

class Asiabill_Payments_Model_Payment extends Mage_Payment_Model_Method_Abstract
{

    protected $_code  = 'asiabill_creditcard';
    protected $_formBlockType = 'asiabill_payments/form_method';
    protected $_isGateway               = false;
    protected $_isInitializeNeeded      = true;
    protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_order                   = null;
    protected $_helper = null;
    public $_asiabill;

    public $curl;
    public $mode;
    public $key;


    public function __construct()
    {
        parent::__construct();
        $this->curl = new Varien_Http_Adapter_Curl();
        $this->mode = $this->getConfigData('mode');
        $this->key = $this->mode == 'test'? $this->getConfigData('test_signkey'): $this->getConfigData('signkey');
        $gateway_account = $this->getGatewayAccount();
        $this->_asiabill = new asiabill\Classes\AsiabillIntegration($this->mode,$gateway_account['gatewayNo'],$this->key);
        $this->_helper = Mage::helper('asiabill_payments');
    }

    public function assignData($data){
        $info = $this->getInfoInstance();

        //reset data
        $this->setAdditionalInformation('cc_asiabill_pmid', null);

        if( empty($data['cc_asiabill_pmid']) ){
            return $this;
        }

        $info->setAdditionalInformation('cc_asiabill_pmid', $data['cc_asiabill_pmid']);

        return $this;

    }

    public function initialize($paymentAction, $stateObject)
    {

        $info = $this->getInfoInstance();
        $this->_order = $info->getOrder();
        $amount = $this->_order->getGrandTotal();

        if( $this->_order && $amount > 0 ){

            $parameter = $this->checkConfirmParameter();

            try{

                if ( $this->getConfigData('checkout_model') == '1' ){
                    $api = new Asiabill_Payments_Helper_Api($this->mode);

                    $res = $api->request('confirmCharge',$parameter,'POST',$this->getToken());

                    if($res['code'] != '0'){ //当掉交易
                        Mage::throwException($res['message']);
                    }

                    Mage::getModel('core/session')->setData('asiabillResult',$res['data']);
                }else{


                    foreach( $parameter['goodsDetail'] as $item ){
                        $parameter['goodsDetails'][] = [
                            'goodsCount' => $item['goodscount'],
                            'goodsPrice' => $item['goodsprice'],
                            'goodsTitle' => $item['goodstitle']
                        ];
                    }
                    unset($parameter['goodsDetail']);
                    $parameter['billingAddress'] = [
                        'address' => $parameter['shipping']['address']['line1'].' '.$parameter['shipping']['address']['line2'],
                        'city' => $parameter['shipping']['address']['city'],
                        'country' => $parameter['shipping']['address']['country'],
                        'firstName' =>  $parameter['shipping']['firstName'],
                        'lastName' =>  $parameter['shipping']['lastName'],
                        'phone' => $parameter['shipping']['phone'],
                        'state' => $parameter['shipping']['address']['state'],
                        'zip' => $parameter['shipping']['address']['postalCode'],
                        'email' => $parameter['shipping']['email'],
                    ];
                    unset($parameter['shipping']);
                    $billing = $this->_helper->getAddress();
                    $parameter['deliveryAddress'] = [
                        'shipAddress' => $billing['address']['line1'].' '.$billing['address']['line2'],
                        'shipCity' => $billing['address']['city'],
                        'shipCountry' => $billing['address']['country'],
                        'shipFirstName' =>  $billing['firstName'],
                        'shipLastName' =>  $billing['lastName'],
                        'shipPhone' => $billing['phone'],
                        'shipState' => $billing['address']['state'],
                        'shipZip' => $billing['address']['postalCode'],
                    ];

                    $parameter['returnUrl'] = Mage::getUrl( 'asiabill/payment/result' , array( '_secure' => true ));
                    $parameter['callbackUrl'] = Mage::getUrl( 'asiabill/payment/callback' , array( '_secure' => true ));

                    $res = $this->_asiabill->request('checkoutPayment',['body' =>
                        $parameter
                    ]);

                    if( $res['code'] != '0000' ){
                        Mage::throwException($res['message']);
                    }

                    Mage::getModel('core/session')->setData('asiabillResult',$res['data']);

                }

            }catch (\Exception $e){
                $message = $e->getMessage();
                Mage::throwException($message);
            }

        }
        else{
            Mage::throwException('Sorry, unable to process this payment, please try again or use alternative method.');
        }


        return $this;

    }

    public function getOrderPlaceRedirectUrl()
    {
        $data = Mage::getModel('core/session')->getData('asiabillResult');

        if( empty($data) ){
            $redirect = Mage::getBaseUrl();
        }else if( $data['threeDsType'] == 1 && !empty($data['threeDsUrl']) ){ // 3D交易
            $redirect = $data['threeDsUrl'];
        }elseif( isset($data['redirectUrl']) ){
            $redirect = $data['redirectUrl'];
        }else{
            $redirect = Mage::getUrl('asiabill/payment/return').'?'.http_build_query($data);
        }

        return $redirect;
    }

    protected function checkConfirmParameter(){
        $info = $this->getInfoInstance();

        $gateway_account =  $this->getGatewayAccount();
        $order_info = $this->getOrderInfo();
        $shipping = $this->_helper->getAddress('shipping');

        $parameter = array_merge($gateway_account,$order_info,['shipping'=>$shipping],[
            'customerPaymentMethodId' => $info->getAdditionalInformation('cc_asiabill_pmid'),
            'ip' => $this->_helper->getCusIp(),
            'returnUrl' => Mage::getUrl( 'asiabill/payment/return' , array( '_secure' => true )),
            'callbackUrl' => Mage::getUrl( 'asiabill/payment/webhook' , array( '_secure' => true )),
            'platform' => 'Magento1',
            'isMobile' => $this->_helper->isMobile(),
            'tradeType' => 'web',
            'webSite' => Mage::getBaseUrl(),
        ]);

        $parameter['signInfo'] = $this->getConfirmSign($parameter);

        return $parameter;

    }

    protected function getOrderInfo(){

        $goods_detail_1 = $goods_detail_2 = [];

        $i = 0;

        foreach ($this->_order->getAllItems() as $item){

            if( $i < 10 ){
                $goods_detail_1[] = [
                    'productName' => htmlspecialchars($item->getName()),
                    'price' => $item->getPrice(),
                    'quantity' => (int)$item->getQtyOrdered(),
                ];
            }
            $goods_detail_2[] = [
                'goodstitle' => htmlspecialchars($item->getName()),
                'goodsprice' => $item->getPrice(),
                'goodscount' => (int)$item->getQtyOrdered()
            ];
            $i++;
        }

        return [
            'orderNo' => $this->_order->getRealOrderId(),
            'orderCurrency' => $this->_order->getOrderCurrencyCode(),
            'orderAmount' => $this->_order->getTotalDue(),
            //'goods_detail' => json_encode($goods_detail_1),
            'goodsDetail' => $goods_detail_2
        ];


    }

    public function getGatewayAccount(){

        if( $this->mode == 'test' ){
            return [
                'merNo' => $this->getConfigData('test_mer_no'),
                'gatewayNo' => $this->getConfigData('test_gateway_no'),
            ];
        }

        return [
            'merNo' => $this->getConfigData('mer_no'),
            'gatewayNo' => $this->getConfigData('gateway_no'),
        ];
    }

    public function getConfirmSign($data = []){
        $string = $data['merNo'] . $data['gatewayNo'] . $data['orderNo'] . $data['orderCurrency'] . $data['orderAmount'] .$data['customerPaymentMethodId'] ;
        return self::signInfo(strtolower($string.$this->key));
    }

    public function getV3Sign($data = []){

        ksort($data);
        if( isset( $data['goodsDetail'] ) ) unset($data['goodsDetail']);

        $string = '';
        foreach ($data as $k => $value){
            if( is_array($value) ){
                $value = $this->getV3Sign($value,false);
            }


            if( $value !== '' && $value !== null && $value !== false ){
                // 拼接参数,参与加密的字符转为小写
                $str = trim(urldecode($value));
                $string .= $str;
            }
        }

        if( $this->key == '' ){
            return $string;
        }

        return $this->signInfo(strtolower($string.$this->key));
    }

    public function getToken(){
        $data = $this->getGatewayAccount();
        $data['signInfo'] = $this->getV3Sign($data);

        $api = new Asiabill_Payments_Helper_Api($this->mode);
        $result = $api->request('sessionToken',$data);

        if( isset($result['code']) && $result['code'] == '0' ){
            $token = $result['data']['sessionToken'];
            Mage::getModel('core/session')->setData('asiabillSessionToken',$token);
            return $token;
        }

        return '';
    }

    public function getNotifySign($data = []){
        $string = $data['notifyType'] . $data['operationResult'] . $data['merNo'] . $data['gatewayNo'] . $data['tradeNo'] . $data['orderNo'] . $data['orderCurrency'] . $data['orderAmount'] . $data['orderStatus'] ;
        return self::signInfo($string.$this->key);
    }

    private function signInfo($string){
        $sign_info = strtoupper(hash("sha256" , $string));
        return $sign_info;
    }

    public function getTransactionStatus($code){
        $order_status = false;
        switch ( $code ){
            case 1:
            case 'success':
                $order_status = $this->getConfigData('success_status');;
                break;
            case -1:
            case -2:
            case 'pending':
                $order_status = $this->getConfigData('pending_status');
                break;
            case 0:
            case 'fail':
                if( @substr($_REQUEST['orderInfo'],0,5) == 'I0061' ){
                    // 重复支付订单
                    $order_status = $this->getConfigData('success_status');
                }else{
                    $order_status = $this->getConfigData('failure_status');
                }
                break;
        }
        return $order_status;
    }

}