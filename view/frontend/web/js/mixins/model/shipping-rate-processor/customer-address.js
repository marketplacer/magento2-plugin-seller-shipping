/*global define*/
define([
    'jquery',
    'underscore',
    'mage/utils/wrapper',
    'Marketplacer_SellerShipping/js/model/shipping-service',
    'Marketplacer_SellerShipping/js/model/quote',
    'Marketplacer_SellerShipping/js/action/get-seller-shipment-rates',
    'Magento_Checkout/js/model/quote',
], function (
    $,
    _,
    wrapper,
    shippingService,
    mpQuote,
    getSellerShipmentRates,
    quote,
) {
    'use strict';
    var cacheRegistry = [];
    var mixins = {
        getRates: function (address) {
            var cache;
            let _return = this._super(address);

            _.each(mpQuote.getSellersId(), function (sellerId) {
                cache = cacheRegistry[address.getKey() + sellerId];
                let payload = {
                    addressId: quote.shippingAddress().customerAddressId,
                    sellerId: sellerId,
                };

                shippingService.getIsLoading(sellerId)(true);

                if (cache) {
                    shippingService.setShippingRates(sellerId, cache);
                    shippingService.getIsLoading(sellerId)(false);
                } else {
                    getSellerShipmentRates(quote.getQuoteId(), payload).then(response => {
                        cacheRegistry[address.getKey() + sellerId] = response;
                        shippingService.setShippingRates(sellerId, response);
                    }).fail(function (response) {
                        shippingService.setShippingRates(sellerId, []);
                    }).always(function () {
                        shippingService.getIsLoading(sellerId)(false);
                    });
                }
            })
            return _return;
        },
    }
    return function (target) {
        target.getRates = wrapper.wrapSuper(target.getRates, mixins.getRates)
        return target;
    }
});