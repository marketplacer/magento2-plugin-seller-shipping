/*global define*/
define([
    'jquery',
    'underscore',
    'mage/utils/wrapper',
    'Marketplacer_SellerShipping/js/model/quote',
    'Marketplacer_SellerShipping/js/checkout-data',
    'Marketplacer_SellerShipping/js/action/select-shipping-method',
], function (
    $,
    _,
    wrapper,
    sellerQuote,
    sellerCheckoutData,
    sellerSelectShippingMethodAction,
) {
    'use strict';

    return {
        resolveShippingRates: function (sellerId, ratesData) {
            var selectedShippingRate = sellerCheckoutData.getSelectedShippingRateForSeller(sellerId),
                availableRate = false,
                shippingMethod = sellerQuote.getShippingMethod(sellerId)();

            if (ratesData.length === 1 && !shippingMethod) {
                //set shipping rate if we have only one available shipping rate
                sellerSelectShippingMethodAction(sellerId, ratesData[0]);
                sellerCheckoutData.setSelectedShippingRateForSeller(
                    sellerId, ratesData[0]['carrier_code'] + '_' + ratesData[0]['method_code']
                );
                return;
            }

            if (shippingMethod) {
                availableRate = _.find(ratesData, function (rate) {
                    return rate['carrier_code'] == shippingMethod['carrier_code'] && //eslint-disable-line
                        rate['method_code'] == shippingMethod['method_code']; //eslint-disable-line eqeqeq
                });
            }

            if (!availableRate && selectedShippingRate) {
                availableRate = _.find(ratesData, function (rate) {
                    return rate['carrier_code'] + '_' + rate['method_code'] === selectedShippingRate;
                });
            }

            if (!availableRate && window.checkoutConfig.selectedShippingMethod) {
                availableRate = _.find(ratesData, function (rate) {
                    var selectedShippingMethod = window.checkoutConfig.selectedShippingMethod;

                    return rate['carrier_code'] == selectedShippingMethod['carrier_code'] && //eslint-disable-line
                        rate['method_code'] == selectedShippingMethod['method_code']; //eslint-disable-line eqeqeq
                });
            }

            //Unset selected shipping method if not available
            if (!availableRate) {
                sellerSelectShippingMethodAction(sellerId, null);
            } else {
                sellerSelectShippingMethodAction(sellerId, availableRate);
                sellerCheckoutData.setSelectedShippingRateForSeller(
                    sellerId, availableRate['carrier_code'] + '_' + availableRate['method_code']
                );
            }
        }
    }
});