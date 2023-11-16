/*global define*/
define([
    'jquery',
    'underscore',
    'mage/utils/wrapper',
    'Marketplacer_SellerShipping/js/model/shipping-service',
    'Marketplacer_SellerShipping/js/model/quote',
    'Marketplacer_SellerShipping/js/action/get-seller-shipment-rates',
    'Magento_Checkout/js/model/quote'
], function (
    $,
    _,
    wrapper,
    shippingService,
    mpQuote,
    getSellerShipmentRates,
    quote
) {
    'use strict';
    var cacheRegistry = [];
    var mixins = {
        getRates: function (address) {
            var cache, payload;
            let _return = this._super(address);

            payload = {
                    address: {
                        'street': address.street,
                        'city': address.city,
                        'region_id': address.regionId,
                        'region': address.region,
                        'country_id': address.countryId,
                        'postcode': address.postcode,
                        'email': address.email,
                        'customer_id': address.customerId,
                        'firstname': address.firstname,
                        'lastname': address.lastname,
                        'middlename': address.middlename,
                        'prefix': address.prefix,
                        'suffix': address.suffix,
                        'vat_id': address.vatId,
                        'company': address.company,
                        'telephone': address.telephone,
                        'fax': address.fax,
                        'custom_attributes': address.customAttributes,
                        'save_in_address_book': address.saveInAddressBook
                    }
                };

            _.each(mpQuote.getSellersId(), function (sellerId) {
                cache = cacheRegistry[address.getCacheKey() + sellerId];

                shippingService.getIsLoading(sellerId)(true);

                if (cache) {
                    shippingService.setShippingRates(sellerId, cache);
                    shippingService.getIsLoading(sellerId)(false);
                } else {
                    payload.sellerId = sellerId;
                    getSellerShipmentRates(quote.getQuoteId(), payload).then(response => {
                        cacheRegistry[address.getCacheKey() + sellerId] = response;
                        shippingService.setShippingRates(sellerId, response);
                    }).fail(function (response) {
                        shippingService.setShippingRates(sellerId, []);
                    }).always(function () {
                        shippingService.getIsLoading(sellerId)(false);
                    });
                }
            });
            return _return;
        },
    }
    return function (target) {
        target.getRates = wrapper.wrapSuper(target.getRates, mixins.getRates)
        return target;
    }
});