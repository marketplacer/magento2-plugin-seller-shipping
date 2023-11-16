/*global define*/
define([
    'jquery',
    'underscore',
    'mage/utils/wrapper',
    'Marketplacer_SellerShipping/js/model/quote',
], function (
    $,
    _,
    wrapper,
    sellerQuote
) {
    'use strict';

    var mixins = {
        getShippingMethodTitle: function () {

            let shippingMethods = [];
            $.each(sellerQuote.getSellersId(), function (index, sellerId) {
                let shippingMethod = sellerQuote.getShippingMethod(sellerId)(),
                    shippingMethodTitle = '';

                if (!shippingMethod) {
                    return '';
                }

                shippingMethodTitle = shippingMethod['carrier_title'];

                if (typeof shippingMethod['method_title'] !== 'undefined') {
                    shippingMethodTitle += ' - ' + shippingMethod['method_title'];
                }
                shippingMethods.push(shippingMethodTitle)
            });

            return shippingMethods.join(", ");
        }
    }
    return function (target) {
        return target.extend(mixins);
    }
});
