define([
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/payment/place-order-hooks',
    'underscore',
    'mage/utils/wrapper',
    'Marketplacer_SellerShipping/js/model/quote',
    'Marketplacer_SellerShipping/js/checkout-data',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
], function (
    storage,
    errorProcessor,
    fullScreenLoader,
    customerData,
    hooks,
    _,
    wrapper,
    sellerQuote,
    sellerCheckoutData,
    urlBuilder,
    quote,
    customer
) {
    'use strict';

    return function (placeOrder) {
        return wrapper.wrap(placeOrder, function (originalAction, serviceUrl, payload, messageContainer) {
            var headers = {}, redirectURL = '';

            if (customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/seller/mine/payment-information', {});
            } else {
                serviceUrl = urlBuilder.createUrl('/guest-seller/:quoteId/payment-information', {
                    quoteId: quote.getQuoteId()
                });
            }

            fullScreenLoader.startLoader();
            _.each(hooks.requestModifiers, function (modifier) {
                modifier(headers, payload);
            });

            payload.sellerShippingMethod = {
                methods: []
            };
            _.each(sellerQuote.getSellersId(), function (sellerId) {
                payload.sellerShippingMethod.methods.push({
                    sellerId: sellerId,
                    method: sellerCheckoutData.getSelectedShippingRateForSeller(sellerId)
                })
            })


            return storage.post(
                serviceUrl, JSON.stringify(payload), true, 'application/json', headers
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    redirectURL = response.getResponseHeader('errorRedirectAction');

                    if (redirectURL) {
                        setTimeout(function () {
                            errorProcessor.redirectTo(redirectURL);
                        }, 3000);
                    }
                }
            ).done(
                function (response) {
                    var clearData = {
                        'selectedShippingAddress': null,
                        'shippingAddressFromData': null,
                        'newCustomerShippingAddress': null,
                        'selectedShippingRate': null,
                        'selectedPaymentMethod': null,
                        'selectedBillingAddress': null,
                        'billingAddressFromData': null,
                        'newCustomerBillingAddress': null
                    };

                    if (response.responseType !== 'error') {
                        customerData.set('checkout-data', clearData);
                        _.each(sellerQuote.getSellersId(), function (sellerId) {
                            sellerCheckoutData.setSelectedShippingRateForSeller(sellerId, null)
                        })
                        customerData.reload(['cart'], false);
                    }
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                    _.each(hooks.afterRequestListeners, function (listener) {
                        listener();
                    });
                }
            );
        });
    };
});
