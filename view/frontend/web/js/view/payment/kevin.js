define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'kevin_payment', // must equals the payment code
            component: 'Kevin_Payment/js/view/payment/method-renderer/kevin-method'
        }
    );

    /** Add view logic here if you needed */
    return Component.extend({});
});