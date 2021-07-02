define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'Kevin_Payment/js/action/redirect-on-success'
], function ($, ko, Component, quote, $t, kevinRedirect) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Kevin_Payment/payment/kevin',
            isBankInputVisible: false,
            redirectAfterPlaceOrder: false
        },

        showBanks: function() {
            return window.checkoutConfig.payment.kevin_payment.show_banks && this.getBanks().length;
        },

        getBanks: function() {
            var activeList = [];
            if(quote.shippingAddress()){
                var countryId = quote.shippingAddress().countryId
                if(countryId){
                    var bankList = window.checkoutConfig.payment.kevin_payment.banks;
                    if(bankList) {
                        if (bankList[countryId] != undefined) {
                            activeList = bankList[countryId];
                        }
                        if(bankList['card'] != undefined){
                            activeList = activeList.concat(bankList['card']);
                        }
                    }
                }
            }
            return activeList;
        },

        getSelectedBankCode: function() {
            var bankSelector = 'input[name=payment\\[bank\\]]:checked';
            var wrapperSelector = '#payment-list-'+this.getCode();
            return $(bankSelector, wrapperSelector).val();
        },

        getSelectedBankName: function() {
            var bankSelector = 'input[name=payment\\[bank\\]]:checked';
            var wrapperSelector = '#payment-list-'+this.getCode();
            return $(bankSelector, wrapperSelector).data('name');
        },

        selectBank: function (data, event){
            $('.kevin-payment-item').removeClass('active');
            $('#'+data.id).parents('.kevin-payment-item').addClass('active');
            return true;
        },

        validate: function() {
            if (this.showBanks()) {
                var bank = this.getSelectedBankCode();
                if (bank == undefined || !bank) {
                    this.messageContainer.addErrorMessage({
                        "message": $t("Please select payment method.")
                    });
                    return false;
                }
            }
            return true;
        },

        afterPlaceOrder: function() {
            kevinRedirect.execute();
        },

        getData: function() {

            var additionalData = {};

            if (this.showBanks()) {
                additionalData.bank_code = this.getSelectedBankCode();
                additionalData.bank_name = this.getSelectedBankName();
            }

            return {
                'method': this.item.method,
                'additional_data': additionalData
            };
        }
    });
});