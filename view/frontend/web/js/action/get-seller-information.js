/**
 * @api
 */
define([
    'jquery',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor'
], function ($, urlBuilder, storage, errorProcessor) {
    'use strict';

    return function (sellerId, deferred, messageContainer) {
        var serviceUrl;

        deferred = deferred || $.Deferred();

        serviceUrl = urlBuilder.createUrl('/seller/:sellerId', {
            sellerId: sellerId
        });

        return storage.get(
            serviceUrl, false
        ).done(function (response) {
            deferred.resolve(response);
        }).fail(function (response) {
            errorProcessor.process(response, messageContainer);
            deferred.reject();
        });
    };
});
