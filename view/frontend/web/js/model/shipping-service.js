define([
    'jquery',
    'ko',
    'Marketplacer_SellerShipping/js/model/checkout-data-resolver',
    //'Marketplacer_SellerShipping/js/model/quote'
    'Magento_Checkout/js/model/quote',
], function ($, ko, checkoutDataResolver,mpQuote) {
    'use strict';

    var collectedShippingRates = ko.observable({}),
        isLoading = ko.observable({}),
        /**
         * @param {int} sellerId
         *
         * @return {*}
         */
        getSellerRates = function (sellerId) {
            let data = collectedShippingRates()[sellerId];

            if ($.isEmptyObject(data)) {
                data = initData();
                setSellerRates(sellerId, data);
            }

            return data;
        },
        /**
         * @param {int} sellerId
         * @param {Object} data
         */
        setSellerRates = function (sellerId, data) {
            let sellersData = collectedShippingRates();
            sellersData[sellerId] = data;
            collectedShippingRates(sellersData);
        },
        /**
         * @return {*}
         */
        initData = function () {
            return ko.observableArray([]);
        }, /**
         * @param {int} sellerId
         *
         * @return {*}
         */
        getIsLoading = function (sellerId) {
            let data = isLoading()[sellerId];

            if ($.isEmptyObject(data)) {
                data = isLoadingInitData();
                setIsLoading(sellerId, data);
            }

            return data;
        },
        /**
         * @param {int} sellerId
         * @param {Object} data
         */
        setIsLoading = function (sellerId, data) {
            let isLoadingData = isLoading();
            isLoadingData[sellerId] = data;
            isLoading(isLoadingData);
        },
        /**
         * @return {*}
         */
        isLoadingInitData = function () {
            return ko.observable(true);
        };

    return {
        getIsLoading: function (sellerId)
        {
            return getIsLoading(sellerId);
        },
        /**
         * Set shipping rates
         *
         * @param {int} sellerId
         * @param {*} ratesData
         */
        setShippingRates: function (sellerId, ratesData) {
            let shippingRates = getSellerRates(sellerId), isLoading = getIsLoading(sellerId);

            shippingRates(ratesData);
            shippingRates.valueHasMutated();

            setSellerRates(sellerId, shippingRates);
            checkoutDataResolver.resolveShippingRates(sellerId, ratesData);
            isLoading(false);
            setIsLoading(sellerId, isLoading);

            mpQuote.getTotals().valueHasMutated();
        },

        /**
         * Get shipping rates
         *
         * @returns {*}
         */
        getShippingRates: function (sellerId) {
            return getSellerRates(sellerId);
        }
    };
});
