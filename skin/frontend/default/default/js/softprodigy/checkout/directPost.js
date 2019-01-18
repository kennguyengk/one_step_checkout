/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Payment
 * @package    Red_Star_Solution
 * @copyright  Copyright (c) 2015 KenNguyen <teogk89@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
if( typeof directPost != 'undefined'){
    directPost.prototype.preparePayment = function() {
        this.changeInputOptions('autocomplete', 'off');
        if ($(this.iframeId)) {
            switch (this.controller) {
                case 'onepage':
                    var button = $$('.btn-checkout').first();
                    $(button).writeAttribute('onclick', '');
                    button.stopObserving('click');
                    button.observe('click', function() {
                        if ($(this.iframeId)) {
                            if (this.validate()) {
                                this.saveOnepageOrder();
                            }
                        } else {
                            checkout.saveOrder();
                        }
                    }.bind(this));
                    break;
            }
            $(this.iframeId).observe('load', this.onLoadIframe);
        }
    }

    directPost.prototype.saveOnepageOrder = function() {
        this.hasError = false;
        this.setLoadWaiting();
        var params = Form.serialize(payment.form);
        if (checkout.checkoutForm) {
            params += '&' + Form.serialize(checkout.checkoutForm);
        }
        params += '&controller=' + this.controller;
        new Ajax.Request(this.orderSaveUrl, {
            method : 'post',
            parameters : params,
            onComplete : this.onSaveOnepageOrderSuccess,
            onFailure : function(transport) {
                this.resetLoadWaiting();
                var response = eval('(' + transport.responseText + ')');
                checkout.updateContents({'error': response.error_msg}, true);
            }
        });
    }
    directPost.prototype.saveOnepageOrderSuccess = function(transport) {
        if (transport.status == 403) {
            checkout.updateContents({'error': 'Service unvailable'}, true);;
        }
        try {
            response = eval('(' + transport.responseText + ')');
        } catch (e) {
            response = {};
        }

        if (response.success && response.directpost) {
            this.orderIncrementId = response.directpost.fields.x_invoice_num;
            var paymentData = {};
            for ( var key in response.directpost.fields) {
                paymentData[key] = response.directpost.fields[key];
            }
            var preparedData = this.preparePaymentRequest(paymentData);
            this.sendPaymentRequest(preparedData);
        } else {
            var msg = response.error_messages;
            if (typeof (msg) == 'object') {
                msg = msg.join("\n");
            }
            checkout.updateContents({'error': msg}, true);
        }
    }
}
