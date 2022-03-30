
var initAsiabil = function () {

    asiabill.loadStripe();
    initAsiabillModules();
};


var initAsiabillModules = function()
{
    if (asiabill.oscInitialized) return;

    if (typeof IWD != "undefined" && typeof IWD.OPC != "undefined") {

        IWD.OPC.isAuthenticationInProgress = false;

        var proceed = function() {
            if (typeof $j == 'undefined') $j = $j_opc;
            var form = $j('#co-payment-form').serializeArray();
            IWD.OPC.Checkout.xhr = $j.post(IWD.OPC.Checkout.config.baseUrl + 'onepage/json/savePayment', form, IWD.OPC.preparePaymentResponse, 'json');
        };

        asiabill.placeOrder = function() {
            if( typeof stripe !== 'undefined' && stripe.isPaymentMethodSelected()){
                createStripeToken(function(err) {
                    IWD.OPC.Checkout.xhr = null;
                    IWD.OPC.Checkout.unlockPlaceOrder();
                    if (err) {
                        IWD.OPC.Checkout.hideLoader();
                        stripe.displayCardError(err);
                    } else stripe.placeOrder();
                });
            }else {
                proceed();
            }
        };

        IWD.OPC.savePayment = function() {

            if (!IWD.OPC.saveOrderStatus){
                return;
            }

            if (IWD.OPC.Checkout.xhr !== null) IWD.OPC.Checkout.xhr.abort();
            IWD.OPC.Checkout.lockPlaceOrder();

            if (!asiabill.isSelected()) {
                return asiabill.placeOrder();
            }
            if (IWD.OPC.isAuthenticationInProgress && typeof IWD.OPC.saveOrder != "undefined") {
                IWD.OPC.isAuthenticationInProgress = false;
                IWD.OPC.saveOrder();
                return;
            }
            asiabill.createPaymentMethodId();

            IWD.OPC.Checkout.xhr = null;
            IWD.OPC.Checkout.unlockPlaceOrder();
        };

        asiabill.oscInitialized = true;
    }
    else if ( typeof Review != 'undefined' && typeof Review.prototype.proceed == 'undefined' ){
        Review.prototype.proce = Review.prototype.save;

        asiabill.placeOrder = function()
        {
            // Awesome Checkout && PlumRocket
            checkout.loadWaiting = false;
            // Others
            review.proce();
        };

        Review.prototype.save = function()
        {
            if (!asiabill.isSelected()){
                return asiabill.placeOrder();
            }
            asiabill.createPaymentMethodId();
        };
    }




};



var asiabill = {
    oscInitialized: false,
    formId: 'asiabill-card-form',
    mode: 'pro',
    apiToken: null,
    layout: {
        pageMode: 'block',
        style: {
            frameMaxHeight: 100,
            input: {
                FontSize: 14,
                FontFamily: '',
                FontWeight: '',
                Color: '',
                ContainerBorder: '1px solid #ddd;',
                ContainerBg: '',
                ContainerSh: ''
            }
        }
    },
    billingAddress: {},

    loadStripe: function () {
        var script = document.getElementsByTagName('script')[0];
        var AsiabillPaymentJs = document.createElement('script');
        AsiabillPaymentJs.src = "https://safepay.asiabill.com/static/v3/js/AsiabillPayment.min.js";
        AsiabillPaymentJs.onload = function()
        {
            asiabill.initElement();
        };
        AsiabillPaymentJs.onerror = function(evt) {
            console.warn("AsiabillPayment.js could not be loaded");
            console.error(evt);
        };
        script.parentNode.insertBefore(AsiabillPaymentJs, script);
    },

    initElement: function () {

        try {
            this.asiabillPay = window.AsiabillPay(this.apiToken);

            this.asiabillPay.elementInit("payment_steps", {
                formId: this.formId, // 页面表单id
                frameId: 'asiabill-card-frame', // 生成的IframeId
                mode: this.mode,
                customerId: this.customerId,
                autoValidate:false,
                layout: this.layout

            }).then((res) => {
                console.log("initRES", res);
            }).catch((err) => {
                console.log("initERR", err);
            });
        }catch (e) {
            if (typeof e != "undefined" && typeof e.message != "undefined") {
                this.message = 'Could not initialize asiabillPayment: ' + e.message;
            } else {
                this.message = 'Could not initialize asiabillPayment';
            }
        }


    },

    placeOrder: function () {},

    createPaymentMethodId: function( ) {
        this.cleanError();

        let owner = {
            "billingDetail": this.billingAddress,
            "card": {
                "cardNo": "",
                "cardExpireMonth": "",
                "cardExpireYear": "",
                "cardSecurityCode": "",
                "issuingBank": ""
            }
        };

        this.asiabillPay.confirmPaymentMethod({
            apikey: this.apiToken,
            trnxDetail: owner
        }).then((result) => {

            if( result.data.code === "0" ){
                this.setPaymentMethodId(result.data.data.customerPaymentMethodId);
                this.placeOrder();
            }else {
                this.onError(result.data.message);
            }
        });

    },

    setPaymentMethodId: function (id){
        try
        {
            var input, inputs = document.getElementsByClassName('asiabill-pmid');
            if (inputs && inputs[0]) input = inputs[0];
            else input = document.createElement("input");
            input.setAttribute("type", "hidden");
            input.setAttribute("name", "payment[cc_asiabill_pmid]");
            input.setAttribute("class", 'asiabill-pmid');
            input.setAttribute("value", id);
            input.disabled = false; // Gets disabled when the user navigates back to shipping method

            var form = document.getElementById('co-payment-form');
            if (!form) form = document.getElementById('order-billing_method_form');
            if (!form) form = document.getElementById('onestepcheckout-form');
            if (!form && typeof payment != 'undefined') form = document.getElementById(payment.formId);
            if (!form)
            {
                form = document.getElementById('new-card');
                input.setAttribute("name", "newcard[cc_asiabill_pmid]");
            }
            form.appendChild(input);
        } catch (e) {}
    },

    isSelected: function () {

        if (typeof payment != 'undefined' && typeof payment.currentMethod != 'undefined' && payment.currentMethod.length > 0){
            return (payment.currentMethod == 'asiabill_creditcard');
        } else {
            var radioButton = document.getElementById('p_method_stripe_payments');
            if (!radioButton || !radioButton.checked)
                return false;

            return true;
        }
    },

    onError: function (msg) {

        if( !msg ){
            this.cleanError();
            return;
        }

        if (asiabill.oscInitialized) {
            alert(msg);
            IWD.OPC.Checkout.hideLoader();
        }

        let box = $('asiabill-card-errors');

        if (box)
        {
            try
            {
                checkout.gotoSection("payment");
                box.update(msg);
                box.removeClassName('hide');
            }
            catch (e) {
                alert(msg);
            }

        }else {
            alert(msg);
        }


    },
    
    cleanError: function () {

        let box = $('asiabill-card-errors');
        if (box)
        {
            box.update('');
            box.addClassName('hide');
        }
    }

};


addEventListener("getErrorMessage", e => {
    asiabill.onError(e.detail.errorMessage);
});
