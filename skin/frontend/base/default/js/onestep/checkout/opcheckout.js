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
var Checkout = Class.create();
Checkout.prototype = {
    initialize: function(blocks, form, successUrl){
        this.successUrl = successUrl;
        this.blocks = blocks.evalJSON();
        this.checkoutForm = form;
        this.accordion = {};
        this._paymentInstace = {};
        this.saveOrderProccessing = false;
        this.hasShippingMethods = true;

        document.observe("dom:loaded", function() {
            this.initBlockContainers();
            $(this.checkoutForm).observe('submit', function(event){
                Event.stop(event);
                var validator = new Validation(this.checkoutForm);
                if(validator.validate()){
                    this.saveOrder();
                }
            }.bind(this));

        }.bind(this));
    },

    hasPayment: function(){
        var methods = document.getElementsByName('payment[method]');
        return methods.length > 0;
    },

    setPayment: function(payment){
        this._paymentInstace = payment;
        return this;
    },

    getPayment: function(){
        return this._paymentInstace;
    },

    setErrorBlock: function(selector){
        this.blocks.error = {};
        this.blocks.error.selector = selector;
        this.blocks.error.container = $$(selector).first();
    },

    initBlockContainers: function(){
        for(var k in this.blocks){
            if(typeof this.blocks[k].selector != 'undefined'){
                var selector = this.blocks[k].selector;
                this.blocks[k].container = $$(selector).first();
                if(k == 'shipping'){
                    this._disableEnableAll(this.blocks[k].container, true);
                }
            }
        }
    },

    addHandler: function(element, event, callback){
        if(typeof callback == 'function'){
            if($(element) != null){
                $(element).observe(event, callback.bind(this));
            }
        }
        return this;
    },

    saveBillingAddress: function(){
        this.saveStep('billing', false);
    },

    setLoadWaiting: function(step){
         $$('.checkout-wrap').first().down().next('.all-loader').addClassName('loader');
    },

    resetLoadWaiting: function(){
         $$('.checkout-wrap').first().down().next('.all-loader').removeClassName('loader');
    },

    saveStep: function(stepId, hideErrors){
        if(typeof hideErrors == 'undefined'){
            hideErrors = true;
        }
        var url = this.blocks[stepId].url;
        var data = this.getStepValues(stepId);
        data['blocks'] = this.getBlocksToReloadByType(stepId);
        new Ajax.Request(
            url,
            {
                method: 'post',
                parameters: data,
                onLoading: function(){
                    this.addLoadingMask(stepId);
                }.bind(this),
                onComplete: function(){
                    this.removeLoadingMask(stepId);
                }.bind(this),
                onSuccess: function(transport){
                    if(transport.responseText.isJSON()){
                        this.updateContents(transport.responseText.evalJSON(), hideErrors);
                    }
                }.bind(this)
            }
        );
    },

    getStepValues: function(type){
        var result = {};
        $$('input[name^=' + type + '], select[name^=' + type + '], textarea[name^=' + type + ']').each(function(element){
            if((element.type == 'radio' || element.type == 'checkbox') && !element.checked){
                return;
            }
            result[element.name] = element.value;
        });
        return result;
    },

    saveShippingAddress: function(){
        this.saveStep('shipping', false);
    },

    saveMethod: function(method){
        if(method == 'shipping'){
            method = 'shipping_method';
        }
        this.saveStep(method);
    },

    updateContents: function(contents, updateError){
        if(typeof this.blocks.error.container != 'undfined'){
            this.blocks.error.container.update('');
            this.blocks.error.container.hide();
        }

        var blockContents = contents['blocks'];
        for(var key in blockContents){
            if(typeof this.blocks[key].container != 'undefined'){
                this.blocks[key].container.down('.checkout-content-inner').update(blockContents[key]);
            }
        }
        if(updateError && typeof this.blocks.error.container != 'undfined'){
            var error = contents['error'];
            if(error){
                this.blocks.error.container.update(error);
                this.blocks.error.container.show();
            }
        }
        return this;
    },

    validateCheckout: function(){
        if(!this.hasShippingMethods){
            alert(Translator.translate('Your order cannot be completed at this time as there is no shipping methods available for it. Please make necessary changes in your shipping address.').stripTags());
            return false;
        }
        if(!this.hasPayment()){
            alert(Translator.translate('Your order cannot be completed at this time as there is no payment methods available for it.').stripTags());
            return false;
        }
        return true;
    },

    saveOrder: function(){
        if(this.saveOrderProccessing){
            this.saveOrderProccessing = false;
            return false;
        }
        if(typeof this.blocks.error.container != 'undfined'){
            this.blocks.error.container.hide();
        }
        this.saveOrderProccessing = true;
        if(!this.validateCheckout()){
            return false;
        }
        new Ajax.Request(
            $(this.checkoutForm).action,
            {
                method: 'post',
                parameters: Form.serialize($(this.checkoutForm)),
                onLoading: function(){
                    this.setLoadWaiting();
                }.bind(this),
                onComplete: function(){
                    this.resetLoadWaiting();
                }.bind(this),
                onSuccess: function(transport){
                    this.resetLoadWaiting();
                    this.saveOrderProccessing = false;
                    if(transport.responseText.isJSON()){
                        var data = transport.responseText.evalJSON();
                        if(typeof data.redirect != 'undefined' && data.success){
                            location.href = data.redirect;
                            return;
                        }
                        if(data.success){
                            location.href = this.successUrl;
                            return;
                        }
                        if(data.error){
                            this.updateContents({'error': data.error}, true);
                        }
                    }
                }.bind(this)
            }
        );
    },

    getBlocksToReloadByType: function(type){
        var blocks = '';
        for(var block in this.blocks[type]['blocks']){
            blocks += blocks.length ? ',' + block : block;
        }
        return blocks;
    },

    hideShippingAddress: function(){
        this.blocks.shipping.container.hide();
        this._disableEnableAll(this.blocks.shipping.container, true);
        $('shipping:same_as_billing').value = 1;
    },

    showShippingAddress: function(){
        this.blocks.shipping.container.show();
        this._disableEnableAll(this.blocks.shipping.container, false);
        $('shipping:same_as_billing').value = 0;
    },

    _disableEnableAll: function(element, isDisabled) {
        var descendants = element.descendants();
        for (var k in descendants) {
            descendants[k].disabled = isDisabled;
        }
        element.disabled = isDisabled;
    },

    newAddress: function(type, isNew){
        if (isNew) {
            this.resetSelectedAddress(type);
            Element.show(type + '-new-address-form');
        } else {
            Element.hide(type + '-new-address-form');
        }
        this.saveStep(type);
    },

    resetSelectedAddress: function(type){
        var selectElement = $(type + '-address-select')
        if (selectElement) {
            selectElement.value='';
        }
    },

    addLoadingMask: function(type){
        var blocks = this.blocks[type]['blocks'];
        for(var block in blocks){
            this.blocks[block].container.down('.loader').addClassName('ajax-loader');
        }
    },

    removeLoadingMask: function(type){
        var blocks = this.blocks[type]['blocks'];
        for(var block in blocks){
            this.blocks[block].container.down('.loader').removeClassName('ajax-loader');
        }
    }

}


var Payment = Class.create();
Payment.prototype = {
    beforeInitFunc:$H({}),
    afterInitFunc:$H({}),
    beforeValidateFunc:$H({}),
    afterValidateFunc:$H({}),
    initialize: function(form, saveUrl){
        this.form = form;
        this.saveUrl = saveUrl;
        this.callbacks = {};
        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    addCallback: function(name, callback){
        this.callbacks[name] = callback;
        return this;
    },

    addBeforeInitFunction : function(code, func) {
        this.beforeInitFunc.set(code, func);
    },

    beforeInit : function() {
        (this.beforeInitFunc).each(function(init){
            (init.value)();;
        });
    },

    init : function () {
        this.beforeInit();
        var elements = Form.getElements(this.form);
        if ($(this.form)) {
            $(this.form).observe('submit', function(event){
                this.save();
                Event.stop(event);
            }.bind(this));
        }
        var method = null;
        for (var i=0; i<elements.length; i++) {
            if (elements[i].name=='payment[method]') {
                if (elements[i].checked) {
                    method = elements[i].value;
                }
            } else {
                elements[i].disabled = true;
            }
            elements[i].setAttribute('autocomplete','off');
        }
        if (method) this.switchMethod(method);
        this.afterInit();
    },

    addAfterInitFunction : function(code, func) {
        this.afterInitFunc.set(code, func);
    },

    afterInit : function() {
        (this.afterInitFunc).each(function(init){
            (init.value)();
        });
    },

    switchMethod: function(method){
        if (this.currentMethod && $('payment_form_'+this.currentMethod)) {
            this.changeVisible(this.currentMethod, true);
            $('payment_form_'+this.currentMethod).fire('payment-method:switched-off', {
                method_code : this.currentMethod
                });
        }
        if ($('payment_form_'+method)){
            this.changeVisible(method, false);
            $('payment_form_'+method).fire('payment-method:switched', {
                method_code : method
            });
        } else {
            //Event fix for payment methods without form like "Check / Money order"
            document.body.fire('payment-method:switched', {
                method_code : method
            });
        }
        if (method) {
            this.lastUsedMethod = method;
        }
        this.currentMethod = method;
    },

    changeVisible: function(method, mode) {
        var block = 'payment_form_' + method;
        [block + '_before', block, block + '_after'].each(function(el) {
            element = $(el);
            if (element) {
                element.style.display = (mode) ? 'none' : '';
                element.select('input', 'select', 'textarea', 'button').each(function(field) {
                    field.disabled = mode;
                });
            }
        });
    },

    addBeforeValidateFunction : function(code, func) {
        this.beforeValidateFunc.set(code, func);
    },

    beforeValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        (this.beforeValidateFunc).each(function(validate){
            hasValidation = true;
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },

    validate: function() {
        var result = this.beforeValidate();
        if (result) {
            return true;
        }
        var methods = document.getElementsByName('payment[method]');
        if (methods.length==0) {
            alert(Translator.translate('Your order cannot be completed at this time as there is no payment methods available for it.').stripTags());
            return false;
        }
        for (var i=0; i<methods.length; i++) {
            if (methods[i].checked) {
                return true;
            }
        }
        result = this.afterValidate();
        if (result) {
            return true;
        }
        alert(Translator.translate('Please specify payment method.').stripTags());
        return false;
    },

    addAfterValidateFunction : function(code, func) {
        this.afterValidateFunc.set(code, func);
    },

    afterValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        (this.afterValidateFunc).each(function(validate){
            hasValidation = true;
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },

    save: function(){
        if(typeof this.callbacks.save == 'function'){
            this.callbacks.save();
        }
    },

    resetLoadWaiting: function(){
        if(typeof this.callbacks.resetLoadWaiting == 'function'){
            this.callbacks.resetLoadWaiting();
        }

    },

    nextStep: function(transport){
        if(typeof this.callbacks.nextStep == 'function'){
            this.callbacks.nextStep(transport);
        }
    },

    initWhatIsCvvListeners: function(){
        $$('.cvv-what-is-this').each(function(element){
            Event.observe(element, 'click', toggleToolTip);
        });
    }
}

var Review = Class.create();
Review.prototype = {
    initialize: function(){
    },
    save: function(){
        var validator = new Validation(checkout.checkoutForm);
        if(validator.validate()){
            checkout.saveOrder();
        }
    }
}

var review = new Review();
