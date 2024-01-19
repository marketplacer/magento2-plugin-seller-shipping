/*global define*/
define([
    'jquery',
    'Magento_Customer/js/customer-data',
], function ($, storage, ) {
        'use strict';

        var cacheKey = 'checkout-data',
            getCacheKey = function (sellerId) {
                return cacheKey + '-' + sellerId;
            },

        /**
         * @param {int} sellerId
         * @param {Object} data
         */
        saveData = function (sellerId, data) {
            storage.set(getCacheKey(sellerId), data);
        },

        /**
         * @return {*}
         */
        initData = function () {
            return {
                'selectedShippingRate': null
            };
        },

        /**
         * @param {int} sellerId
         *
         * @return {*}
         */
        getData = function (sellerId) {
            var data = storage.get(getCacheKey(sellerId))();

            if ($.isEmptyObject(data)) {
                data = $.initNamespaceStorage('mage-cache-storage').localStorage.get(getCacheKey(sellerId));

                if ($.isEmptyObject(data)) {
                    data = initData();
                    saveData(sellerId, data);
                }
            }

            return data;
        };

        return {
            /**
             * Setting the selected shipping rate pulled from persistence storage
             *
             * @param {int} sellerId
             * @param {Object} data
             */
            setSelectedShippingRateForSeller: function (sellerId, data) {
                let obj = getData(sellerId);

                obj.selectedShippingRate = data;
                saveData(sellerId, obj);
            },

            /**
             * Pulling the selected shipping rate from local storage
             *
             * @return {*}
             */
            getSelectedShippingRateForSeller: function (sellerId) {
                return getData(sellerId).selectedShippingRate;
            },
        }
    });