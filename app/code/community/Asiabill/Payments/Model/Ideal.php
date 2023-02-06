<?php

class Asiabill_Payments_Model_Ideal extends Asiabill_Payments_Model_Payment
{

    protected $_code  = 'asiabill_ideal';
    protected $_formBlockType = 'asiabill_payments/form_redirect';
    protected $_paymentMethod = 'ideal';
    protected $_controller = 'ideal';

}