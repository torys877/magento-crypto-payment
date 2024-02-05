/*
 * Copyright © Ihor Oleksiienko (https://github.com/torys877)
 * See LICENSE for license details.
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'crypto_payment',
                component: 'Crypto_CryptoPayment/js/view/payment/method-renderer/crypto-payment-renderer'
            }
        );

        return Component.extend({});
    }
);
