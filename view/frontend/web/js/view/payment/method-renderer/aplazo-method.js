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

        isActive: function() {
            return true;
        },

        validate: function() {
            var $form = $('#' + this.getCode() + '-form');
            return true;
        },

        continueWithAplazo: function (data, event) {
            let _this = this;
            jQuery('body').loader('show');
            $.ajax({
                url: url.build('aplazopayment/index/onplaceorder'),
                type: 'GET',
                cache: false,

                success: function (response) {
                    jQuery('body').loader('hide');
                    //localStorage.removeItem('mage-cache-storage');
                    if (response.error === false && response.redirecturl !== null) {
                        _this.placeOrder(data, event);
                        let url = response.redirecturl;
                        window.location = url;
                    } else {
                        console.log(response);
                    }
                },
                error: function (response) {
                    jQuery('body').loader('hide');
                    console.log(response);
                }
            });
        },
    });
});
