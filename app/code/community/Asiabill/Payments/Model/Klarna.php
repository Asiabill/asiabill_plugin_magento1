<?php

class Asiabill_Payments_Model_Klarna extends Asiabill_Payments_Model_Payment
{

    protected $_code  = 'asiabill_klarna';
    protected $_formBlockType = 'asiabill_payments/form_redirect';
    protected $_paymentMethod = 'klarna';
    protected $_controller = 'klarna';
    
}