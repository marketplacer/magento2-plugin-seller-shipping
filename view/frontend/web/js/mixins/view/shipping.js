/*global define*/
define([
    'jquery',
    'underscore',
    'ko',
    'mage/utils/wrapper',
    'Marketplacer_SellerShipping/js/model/quote',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
], function (
    $,
    _,
    ko,
    wrapper,
    sellerQuote,
    quote,
    $t
) {
    'use strict'

    var mixins = {
        setShippingInformation: function () {
            let shippingMethod = null;
            _.each(sellerQuote.getSellersId(), function (sellerId) {
                shippingMethod = sellerQuote.getShippingMethod(sellerId)();
            });
            if (shippingMethod !== null) {
                quote.shippingMethod(shippingMethod);
            }

            return this._super();
        },
        validateShippingInformation: function () {
            let _result = this._super();

            if (_result) {
                _.each(sellerQuote.getSellersId(), (sellerId) => {
                    let shippingMethod = sellerQuote.getShippingMethod(sellerId)()
                    if (!shippingMethod) {
                        this.errorValidationMessage(
                            $t('The shipping method is missing. Select the shipping method and try again.')
                        );
                        _result = false;
                        return false;
                    }
                });

            }

            return _result;
        }
    }
    return function (target) {
        return target.extend(mixins);
    }
});
