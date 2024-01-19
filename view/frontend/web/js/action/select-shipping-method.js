/**
 * @api
 */
define([
    'Marketplacer_SellerShipping/js/model/quote',
    'Magento_Checkout/js/model/payment/place-order-hooks'
], function (sellersQuote, placeOrderHooks) {
    'use strict';

    return function (sellerId, shippingMethod) {
        sellersQuote.setShippingMethod(sellerId, shippingMethod);
    };
});
