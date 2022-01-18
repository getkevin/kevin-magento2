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
            redirectAfterPlaceOrder: false,
            configBankList: window.checkoutConfig.payment.kevin_payment.banks,
            configCountryList: window.checkoutConfig.payment.kevin_payment.countries,
            configShowBankName: window.checkoutConfig.payment.kevin_payment.show_name,
            configShowBankList: window.checkoutConfig.payment.kevin_payment.show_banks,
            configShowCountryList: window.checkoutConfig.payment.kevin_payment.show_country_list,
            configShowSearch: window.checkoutConfig.payment.kevin_payment.show_search,
            availableCountries: ko.observableArray([]),
            availableBanks: ko.observableArray([]),
            selectedCountry: ko.observable(),
            searchText: ko.observable(),
            showSearch: ko.observable(false),
            termsStatus: ko.observable(false),
            termText: $t("I have read and agree with the <a href=\"https://kevin.eu/terms-conditions/\" target=\"_blank\">payment terms</a> and <a href=\"https://kevin.eu/privacy-policy/\" target=\"_blank\">privacy policy</a> of Kevin EU, UAB")
        },

        initialize: function (config) {
            this._super();
            var self = this;

            self.availableCountries(self.configCountryList);

            if(quote.shippingAddress()) {
                var countryId = quote.shippingAddress().countryId

                if(countryId != 'undefined'){
                    self.selectedCountry(countryId);

                    var bankList = self.getBanks(countryId);
                    self.availableBanks(bankList);

                    if(bankList.length && self.configShowSearch){
                        self.showSearch(true);
                    }
                }
            }

            /*self.availableBanks.subscribe(function(list) {
                if(list.length){
                    self.showSearch(true);
                } else {
                    self.showSearch(false);
                }
            }); */

            self.selectedCountry.subscribe(function(value) {
                if(value) {
                    var bankList = self.getBanks(value);
                    self.availableBanks(bankList);
                }
            });

            self.searchText.subscribe(function(value) {
                var bankList = self.searchBank(value);
                self.availableBanks(bankList);
            });

            quote.shippingAddress.subscribe(function(address) {
                var countryId = address.countryId

                if(countryId != 'undefined'){
                    self.selectedCountry(countryId);
                    self.availableBanks(self.getBanks(countryId));
                }
            });
        },

        showBanks: function() {
            return this.configShowBankList && this.availableBanks().length;
        },

        showCountryList: function() {
            return this.configShowCountryList && this.showBanks();
        },

        showSelectionElem: function() {
            return this.configShowBankList;
        },

        showBankName: function() {
            return this.configShowBankName;
        },

        searchBank: function(search){
            var banks = this.getBanks(this.selectedCountry());

            if(search){
                return banks.filter(function(item) {
                    return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
                });
            } else {
                return banks;
            }
        },

        getBanks: function(countryId) {
            var activeList = [];
            if(countryId){
                var bankList = this.configBankList;
                if(bankList) {
                    if(bankList['card'] != undefined){
                        activeList = activeList.concat(bankList['card']);
                    }

                    if (bankList[countryId] != undefined) {
                        activeList = activeList.concat(bankList[countryId]);
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
                var terms = this.termsStatus();
                if (bank == undefined || !bank) {
                    this.messageContainer.addErrorMessage({
                        "message": $t("Please select a payment method.")
                    });
                    return false;
                } else if(terms == undefined || !terms){
                    this.messageContainer.addErrorMessage({
                        "message": $t("Please agree with kevin payment terms.")
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
