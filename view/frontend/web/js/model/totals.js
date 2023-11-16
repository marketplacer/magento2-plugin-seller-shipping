define([
    'jquery',
    'ko',
    'Marketplacer_SellerShipping/js/model/quote',
], function (
    $,
    ko,
    sellerQuote
) {
    'use strict';
    var dataList = ko.observable({}),
        /**
         * @param {int} sellerId
         *
         * @return {*}
         */
        getData = function (sellerId) {
            let data = dataList()[sellerId];

            if ($.isEmptyObject(data)) {
                data = initData(sellerId);
                saveData(sellerId, data);
            }

            return data;
        },
        /**
         * @param {int} sellerId
         * @param {Object} data
         */
        saveData = function (sellerId, data) {
            let sellersData = dataList();
            sellersData[sellerId] = data;
            dataList(sellersData);
        },
        /**
         * @return {*}
         */
        initData = function (sellerId) {
            let quoteItems = ko.observable(sellerQuote.getTotals(sellerId)().items);

            return {
                'quoteItems': quoteItems,
                'totals': sellerQuote.getTotals(sellerId)
            };
        };

    $.each(sellerQuote.getSellersId(), (index, sellerId) => {
        sellerQuote.getTotals(sellerId).subscribe(function (newValue) {
            let data = getData(sellerId)
            data.quoteItems(newValue.items);
            saveData(sellerId, data);
        });
    });

    return {
        getTotals: function (sellerId) {
            return getData(sellerId).totals;
        },
        /**
         * @return {Function}
         */
        getItems: function (sellerId) {
            return getData(sellerId).quoteItems;
        },
    };
});
