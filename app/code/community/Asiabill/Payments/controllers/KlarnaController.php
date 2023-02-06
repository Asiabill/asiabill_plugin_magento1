<?php
include_once 'PaymentController.php';

class Asiabill_Payments_KlarnaController extends Asiabill_Payments_PaymentController
{

    protected $data;
    protected $model;

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
        $this->data = $_REQUEST;

        $this->model = Mage::getModel('asiabill_payments/klarna');

    }

}