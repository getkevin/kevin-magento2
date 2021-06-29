define(
    [
        'mage/url'
    ],
    function (url) {
        'use strict';

        return {
            redirectUrl: window.checkoutConfig.payment.kevin_payment.redirectUrl,

            /**
             * Provide redirect to page
             */
            execute: function () {
                window.location.replace(url.build(this.redirectUrl));
            }
        };
    }
);
