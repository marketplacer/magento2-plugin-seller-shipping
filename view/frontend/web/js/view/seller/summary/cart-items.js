define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote',
    'Marketplacer_SellerShipping/js/model/totals',
    'uiRegistry'
], function (
    ko,
    $,
    Component,
    stepNavigator,
    quote,
    sellerTotals,
    uiRegistry
) {
    'use strict';

    var useQty = window.checkoutConfig.useQty;

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/summary/cart-items',
            sellerId: null,
        },
        totals: {},
        items: [],
        maxCartItemsToDisplay: window.checkoutConfig.maxCartItemsToDisplay,
        cartUrl: window.checkoutConfig.cartUrl,

        /**
         * Returns cart items qty
         *
         * @returns {Number}
         */
        getItemsQty: function () {
            return parseFloat(this.totals['items_qty']);
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

            this.totals = sellerTotals.getTotals(this.sellerId())();
            // Set initial items to observable field
            this.setItems(sellerTotals.getItems(this.sellerId())());

            let parent = uiRegistry.get(this.parentName);

            parent.cartIsLoading(false);
            // Subscribe for items data changes and refresh items in view
            sellerTotals.getItems(this.sellerId()).subscribe(function (items) {
                parent.cartIsLoading(true);
                this.setItems(items);
                parent.cartIsLoading(false);
            }.bind(this));
        },
        initObservable: function () {
            this._super().
            observe(['sellerId', 'items']);

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
        }
    });
});
