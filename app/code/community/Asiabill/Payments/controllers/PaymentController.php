<?php

class Asiabill_Payments_PaymentController extends Mage_Core_Controller_Front_Action
{

    protected $data;
    protected $model;

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
        $this->data = $_REQUEST;

        $this->model = Mage::getModel('asiabill_payments/payment');

    }

    public function returnAction(){

        if( !$this->validated() ){
            return $this->_redirect('/index');
        }

        if( $this->data['orderStatus'] == '0' ){
            $redirect = 'checkout/cart';
            Mage::getSingleton('core/session')->addError($this->data['orderInfo']);
        }else{
            $redirect = 'checkout/onepage/success';
            Mage::getSingleton('core/session')->addSuccess($this->data['orderInfo']);
        }

        $this->setOrderStatus();

        return $this->_redirect($redirect);

    }

    public function webhookAction(){

        $request_body = file_get_contents( 'php://input' );

        if( empty($this->data) ){
            $this->data = json_decode($request_body,true);
        }



        if( !$this->validated() ){
            echo 'error';
            exit();
        }
        $this->setOrderStatus();
        echo 'success';
        exit();

    }

    public function resultAction(){

        if ($this->model->_asiabill->verification()){

            if( $this->data['orderStatus'] == 'fail' ){
                $redirect = 'checkout/cart';
                Mage::getSingleton('core/session')->addError($this->data['orderInfo']);
            }else{
                $redirect = 'checkout/onepage/success';
                Mage::getSingleton('core/session')->addSuccess($this->data['orderInfo']);
            }
            //$this->setOrderStatus();
            return $this->_redirect($redirect);

        }
        return $this->_redirect('/index');
    }

    public function callbackAction(){
        if( $this->model->_asiabill->verification() ){
            $this->data = $this->model->_asiabill->getWebhookData()['data'];

            $this->setOrderStatus();
            echo 'success';
            exit();
        }
        echo 'error';
        exit();
    }

    protected function setOrderStatus(){

        $order =  Mage::getModel('sales/order')->loadByIncrementId($this->data['orderNo']);

        if( empty($order) ){
            return false;
        }

        $order_status = $order->getStatus();

        if( in_array($order_status,['processing','complete','closed']) ){
            return false;
        }

        $transaction_status = $this->model->getTransactionStatus($this->data['orderStatus']);

        if( $transaction_status === false ){
            return false;
        }

        if( $transaction_status == $order_status ){
            return false;
        }

        $comment = 'tradeNo:'.$this->data['tradeNo'].';'.( isset($this->data['orderInfo'])?'orderInfo:'.$this->data['orderInfo']:'orderStatus:'.$this->data['orderStatus'] );

        $order->addStatusToHistory($transaction_status, $comment);


        if( $transaction_status == $this->model->getConfigData('success_status') ){
            //发送邮件
            $order->sendNewOrderEmail();
            //自动Invoice
            $this->saveInvoice($order);
        }

        $order->save();

        return true;

    }

    protected function saveInvoice(Mage_Sales_Model_Order $order)
    {
        if ($order->canInvoice()) {
            try{
                $convertor = Mage::getModel('sales/convert_order');
                $invoice = $convertor->toInvoice($order);
                foreach ($order->getAllItems() as $orderItem) {
                    if (!$orderItem->getQtyToInvoice()) {
                        continue;
                    }
                    $item = $convertor->itemToInvoiceItem($orderItem);
                    $item->setQty($orderItem->getQtyToInvoice());
                    $invoice->addItem($item);
                }
                $invoice->collectTotals();
                $invoice->register();
                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();
            }catch (\Exception $e){
                var_dump($e->getMessage());
                exit();
            }


        }


    }

    protected function validated(){

        if( !isset($this->data['orderNo']) ||
            !isset($this->data['tradeNo']) ||
            !isset($this->data['merNo']) ||
            !isset($this->data['gatewayNo'])){
            return false;
        }

        $signInfo = $this->data['signInfo'];
        unset($this->data['signInfo']);

        if( strtoupper($signInfo) == $this->model->getV3Sign($this->data) ){
            return true;
        }

        if( $this->data['notifyType'] == 'Capture' || $this->data['notifyType'] == 'Void' ){
            if( strtoupper($signInfo) == $this->model->getNotifySign($this->data) ){
                return true;
            }
        }

        return false;
    }

}