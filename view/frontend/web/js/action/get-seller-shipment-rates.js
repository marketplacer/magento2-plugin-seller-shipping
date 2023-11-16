/**
 * @api
 */
define([
    'jquery',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Customer/js/model/customer'
], function (
    $,
    urlBuilder,
    storage,
    errorProcessor,
    resourceUrlManager
) {
    'use strict';

    return function (quoteId, payload, deferred, messageContainer) {
        deferred = deferred || $.Deferred();

        let params = resourceUrlManager.getCheckoutMethod() === 'guest' ?
                {
                    cartId: quoteId
                } : {},
            urls = {
                'guest': payload.address
                    ? '/guest-seller/:cartId/estimate-shipping-method-by-address'
                    :'/guest-seller/:cartId/estimate-shipping-methods',
                'customer': payload.address
                    ? '/seller/estimate-shipping-method-by-address'
                    : '/seller/estimate-shipping-methods'
            };

        let serviceUrl = resourceUrlManager.getUrl(urls, params)

        return storage.post(
            serviceUrl,
            JSON.stringify(payload),
            false,
            'application/json',
            {}
        ).done(function (response) {
            deferred.resolve(response);
        }).fail(function (response) {
            errorProcessor.process(response, messageContainer);
            deferred.reject();
        });
    };
});
