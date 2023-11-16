define([
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-rate-processor/new-address',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Marketplacer_SellerShipping/js/model/shipping-service',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Customer/js/customer-data',
    'Marketplacer_SellerShipping/js/model/quote',
], function (
    _,
    quote,
    defaultProcessor,
    totalsDefaultProvider,
    shippingService,
    cartCache,
    customerData,
    mpQuote) {
    'use strict';

    var rateProcessors = {},
        totalsProcessors = {},

        /**
         * Estimate totals for shipping address and update shipping rates.
         */
        updateRates = function (data) {
            if (!data) {
                return;
            }
            var type = quote.shippingAddress().getType();

            if (
                quote.isVirtual() ||
                window.checkoutConfig.activeCarriers && window.checkoutConfig.activeCarriers.length === 0
            ) {
                // update totals block when estimated address was set
                totalsProcessors['default'] = totalsDefaultProvider;
                totalsProcessors[type] ?
                    totalsProcessors[type].estimateTotals(quote.shippingAddress()) :
                    totalsProcessors['default'].estimateTotals(quote.shippingAddress());
            } else {
                _.each(mpQuote.getSellersId(), function (sellerId) {
                    // check if user data not changed -> load rates from cache
                    if (!cartCache.isChanged('address', quote.shippingAddress()) &&
                        !cartCache.isChanged('cartVersion', customerData.get('cart')()['data_id']) &&
                        cartCache.get('rates' + sellerId)
                    ) {
                        shippingService.setShippingRates(sellerId, cartCache.get('rates'));

                        return;
                    }

                    // update rates list when estimated address was set
                    rateProcessors['default'] = defaultProcessor;
                    rateProcessors[type] ?
                        rateProcessors[type].getRates(quote.shippingAddress()) :
                        rateProcessors['default'].getRates(quote.shippingAddress());

                    // save rates to cache after load
                    shippingService.getShippingRates(sellerId).subscribe(function (rates) {
                        cartCache.set('rates' + sellerId, rates);
                    });
                })
            }
        },

        /**
         * Estimate totals for shipping address.
         */
        estimateTotalsShipping = function () {
            totalsDefaultProvider.estimateTotals(quote.shippingAddress());
        },

        /**
         * Estimate totals for billing address.
         */
        estimateTotalsBilling = function () {
            var type = quote.billingAddress().getType();

            if (quote.isVirtual()) {
                // update totals block when estimated address was set
                totalsProcessors['default'] = totalsDefaultProvider;
                totalsProcessors[type] ?
                    totalsProcessors[type].estimateTotals(quote.billingAddress()) :
                    totalsProcessors['default'].estimateTotals(quote.billingAddress());
            }
        };

    quote.shippingAddress.subscribe(updateRates);
    //quote.shippingMethod.subscribe(estimateTotalsShipping);
    //quote.billingAddress.subscribe(estimateTotalsBilling);
});
