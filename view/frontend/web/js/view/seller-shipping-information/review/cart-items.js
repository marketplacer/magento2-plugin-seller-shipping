/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote',
    'escaper',
    'Magento_Catalog/js/price-utils',
    'Marketplacer_SellerShipping/js/model/totals',
    'Marketplacer_SellerShipping/js/model/quote',
    'uiRegistry'
], function (
    ko,
    $,
    Component,
    stepNavigator,
    quote,
    escaper,
    priceUtils,
    sellerTotals,
    mpQuote,
    uiRegistry
) {
    'use strict';

    var useQty = window.checkoutConfig.useQty;

    return Component.extend({
        defaults: {
            template: 'Marketplacer_SellerShipping/seller-shipping-information/review/cart-items',
            sellerId: null,
            allowedTags: ['b', 'strong', 'i', 'em', 'u']
        },
        items: [],
        totals: null,
        maxCartItemsToDisplay: window.checkoutConfig.maxCartItemsToDisplay,
        cartUrl: window.checkoutConfig.cartUrl,

        /**
         * Returns cart items qty
         *
         * @returns {Number}
         */
        getItemsQty: function () {
            return parseFloat(this.totals()['items_qty']);
        },

        /**
         * Returns count of cart line items
         *
         * @returns {Number}
         */
        getCartLineItemsCount: function () {
            return parseInt(sellerTotals.getItems(this.sellerId())().length, 10);
        },

        /**
         * Returns shopping cart items summary (includes config settings)
         *
         * @returns {Number}
         */
        getCartSummaryItemsCount: function () {
            return useQty ? this.getItemsQty() : this.getCartLineItemsCount();
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();

            this.totals(sellerTotals.getTotals(this.sellerId())());
            // Set initial items to observable field
            this.setItems(sellerTotals.getItems(this.sellerId())());

            let parent = uiRegistry.get(this.parentName);

            // Subscribe for items data changes and refresh items in view
            sellerTotals.getItems(this.sellerId()).subscribe(function (items) {
                parent.isLoading(true);
                this.setItems(items);
                parent.isLoading(false);
            }.bind(this));

            sellerTotals.getTotals(this.sellerId()).subscribe(function (totals) {
                parent.isLoading(true);
                this.totals(totals);
                parent.isLoading(false);
            }.bind(this));
        },
        initObservable: function () {
            this._super().
            observe([
                'sellerId', 'items', 'totals'
            ]);

            return this;
        },

        /**
         * Set items to observable field
         *
         * @param {Object} items
         */
        setItems: function (items) {
            if (items && items.length > 0) {
                items = items.slice(parseInt(-this.maxCartItemsToDisplay, 10));
            }
            this.items(items);
        },

        /**
         * Returns bool value for items block state (expanded or not)
         *
         * @returns {*|Boolean}
         */
        isItemsBlockExpanded: function () {
            return quote.isVirtual() || stepNavigator.isProcessed('shipping');
        },
        /**
         * @param {Object} quoteItem
         * @return {String}
         */
        getNameUnsanitizedHtml: function (name) {
            var txt = document.createElement('textarea');

            txt.innerHTML = name;

            return escaper.escapeHtml(txt.value, this.allowedTags);
        },
        /**
         * @param {*} price
         * @return {*|String}
         */
        getFormattedPrice: function (price) {
            return priceUtils.formatPriceLocale(price, quote.getPriceFormat());
        },
        // Tax
        /**
         * @return {Boolean}
         */
        ifShowValue: function () {
            if (this.isFullMode() && this.getPureValue() == 0) { //eslint-disable-line eqeqeq
                return isZeroTaxDisplayed;
            }

            return true;
        },
    });
});
