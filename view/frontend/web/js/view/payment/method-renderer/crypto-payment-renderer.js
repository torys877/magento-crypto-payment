/*
 * Copyright © Ihor Oleksiienko (https://github.com/torys877)
 * See LICENSE for license details.
 */
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (ko,
              $,
              quote,
              urlBuilder,
              storage,
              customerData,
              Component,
              placeOrderAction,
              selectPaymentMethodAction,
              customer,
              checkoutData,
              additionalValidators,
              url,
              fullScreenLoader) {
        'use strict';

        var checkoutConfig = window.checkoutConfig.payment;

        return Component.extend({
            selectedCurrency: ko.observable(null),
            defaults: {
                template: 'Crypto_CryptoPayment/payment/crypto_payment',
                redirectAfterPlaceOrder: false
            },
            initObservable: function () {
                this._super();

                this.selectedCurrency(window.checkoutConfig.quoteData.quote_currency_code);

                return this;
            },
            afterPlaceOrder: function (id) {
                self.redirectAfterPlaceOrder = false
                window.location.replace(
                    url.build(
                        'cryptopayment/payment/processing?nocache=' + (new Date().getTime())
                    ));
            },
            isCryptoAvailable: function () {
                // var cryptoCurrencies = window.checkoutConfig;
                var paymentData = window.checkoutConfig.payment[this.getCode()];
                if (paymentData.currencies.length) {
                    return true;
                }

                return false;
            },
            getCryptoCurrencies: function() {
                var currencies = window.checkoutConfig.payment[this.getCode()].currencies;
                var Currency = function(name, code) {
                    this.name = name;
                    this.code = code;
                };

                var currencyData = [];
                for (var i in currencies) {
                    currencyData.push(new Currency(currencies[i].name, currencies[i].code));
                }

                return currencyData;
            },
            changeCurrency: function(obj, event) {
                if (event.originalEvent) { //user changed

                    window.checkoutConfig.quoteData.quote_currency_code = event.target.value;
                    window.location.href = window.BASE_URL + 'directory/currency/switch/?currency=' + event.target.value;
                }
            }
        });
    }
);
