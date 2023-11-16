/*global define*/
define([
    'jquery',
    'ko',
    'underscore',
    'Magento_Checkout/js/model/quote',
    'domReady!'
], function (
    $,
    ko,
    _,
    quote,
) {
    'use strict';
    var sellers = ko.observable({}),
        generalSellerId = window.checkoutConfig.marketplacer_seller_shipping.generalSellerId,
        filterItemsBySeller = function (sellerId) {
            return quote.getItems().filter(function (item) {
                let marketplacer_seller_id = item.product.marketplacer_seller ?? generalSellerId;
                return sellerId.toString() === marketplacer_seller_id.toString();
            });
        },
        proceedTotalData = function (data) {
            if (_.isObject(data) && _.isObject(data['extension_attributes'])) {
                _.each(data['extension_attributes'], function (element, index) {
                    data[index] = element;
                });
            }

            return data;
        },
        resetTotalData = function (data) {
            data.items_qty = 0;
            data.subtotal = 0;
            data.subtotal_incl_tax = 0;
            data.subtotal_with_discount = 0;
            data.tax_amount = 0;
            data.grand_total = 0;
        },
        recalculateItems = function (data, ids) {
            if (data.items) {
                data.items = data.items.filter((el) => _.includes(ids, el.item_id.toString()));
            } else {
                data.items = [];
            }

            resetTotalData(data)

            _.each(data.items, function (item) {
                data.items_qty += item.qty;
                data.subtotal += parseFloat(item.row_total);
                data.subtotal_incl_tax += parseFloat(item.row_total_incl_tax);
                data.subtotal_with_discount += parseFloat(item.row_total_with_discount);
                data.tax_amount += parseFloat(item.tax_amount);
            })
        },
        proceedSellerTotalData = function (sellerId, data) {
            let ids = filterItemsBySeller(sellerId).map(d => d['item_id'].toString());

            recalculateItems(data, ids);

            return data;
        },
        /**
         * @param {int} sellerId
         *
         * @return {*}
         */
        getData = function (sellerId) {
            let data = sellers()[sellerId];

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
            let sellersData = sellers();
            sellersData[sellerId] = data;
            sellers(sellersData);
        },
       /**
        * @return {*}
        */
       initData = function (sellerId) {
           let totalsData = proceedTotalData({...window.checkoutConfig.totalsData}),
               shippingMethod = ko.observable(null),
               totals = ko.observable(null),
               tax_amount = ko.observable(0),
               shipping_tax_amount = ko.observable(0),
               shipping_price_incl_tax = ko.observable(0),
           updateTotal = function (data) {
               data.tax_amount = tax_amount() + shipping_tax_amount();
               data.grand_total = parseFloat(data.subtotal_incl_tax) + shipping_price_incl_tax()
           };

           proceedSellerTotalData(sellerId, totalsData);
           tax_amount(totalsData.tax_amount);

           totals(totalsData)

           totals.subscribe((data) => {
               updateTotal(data)
           });


           quote.getTotals().subscribe(function (data) {
               let quoteTotalsData = proceedTotalData({...data});

               proceedSellerTotalData(sellerId, quoteTotalsData);
               tax_amount(quoteTotalsData.tax_amount);

               totals(quoteTotalsData);
           });

           shippingMethod.subscribe(function (method) {
               let price_incl_tax = parseFloat(method.price_incl_tax);

               let tax_amount = method?.extension_attributes?.tax_amount;
               if (!tax_amount) {
                   tax_amount = 0.00 /*parseFloat(method.price_incl_tax) - parseFloat(method.price_excl_tax)*/;
               } else {
                   price_incl_tax = parseFloat(method.price_excl_tax) + tax_amount;
               }
               shipping_price_incl_tax(price_incl_tax);
               shipping_tax_amount(tax_amount);
               totals.valueHasMutated();
           });

           return {
               'totals': totals,
               'shippingMethod': shippingMethod
           };
       }, getSellersId = function () {
            let result = [];
            _.each(quote.getItems(), function (item) {
                let sellerId = item.product.marketplacer_seller ?? generalSellerId;
                result.push(sellerId)
            });
            return result;
        };

   return {
       //,
       setShippingMethod: function (sellerId, shippingMethod) {
           let obj = getData(sellerId);
           obj.shippingMethod(shippingMethod);
           saveData(sellerId, obj)
       },
       /**
        * @return {*}
        */
       getShippingMethod: function (sellerId) {
           return getData(sellerId).shippingMethod
       },
       getSellersId: function () {
           return getSellersId();
       },
       getItems: function (sellerId) {
           return filterItemsBySeller(sellerId);
       },
       getTotals: function (sellerId) {
           return getData(sellerId).totals
       }
   }
});