define([
    'Magento_Payment/js/view/payment/cc-form',
    'jquery',
    'mage/url'
], function (
    Component,
    $,
    url
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Spro_AplazoPayment/payment/aplazo-form'
        },

        getTitle: function () {
            return this.item.title;
        },

        getCode: function() {
            return this.item.method;
        },

        getRedirectUrl: function () {
            return window.avvePaymentRedirectUrl !== undefined ? window.avvePaymentRedirectUrl : 'https://fluffyrobots.avve.com/purchase/true/';
        },

        isActive: function() {
            return true;
        },

        validate: function() {
            var $form = $('#' + this.getCode() + '-form');
            return true;
        },

        continueWithAplazo: function (data, event) {
            let _this = this;

            $.ajax({
                url: url.build('avvepayment/ajax/transaction'),
                type: 'GET',
                cache: false,

                success: function (response) {
                    console.log(window.avvePaymentRedirectUrl)

                    if (response.error === false && response.transactionId !== null) {
                        let url = _this.getRedirectUrl();
                        window.location = url + response.transactionId;
                        _this.placeOrder(data, event);
                    } else {
                        console.log(response);
                    }
                },
                error: function (response) {
                    console.log(response);
                }
            });
        },
    });
});
