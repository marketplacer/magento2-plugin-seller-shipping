/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'ko',
    'Magento_Catalog/js/price-utils',
    'uiComponent',
    'Marketplacer_SellerShipping/js/helper',
    'Magento_Checkout/js/model/quote',
    'Marketplacer_SellerShipping/js/model/quote',
    'Marketplacer_SellerShipping/js/action/get-seller-information',
], function (
    _,
    ko,
    priceUtils,
    Component,
    helper,
    quote,
    sellerQuote,
    getSellerInformation
) {
    'use strict';

    let children = {
        cart_item: {
            displayArea: 'cart-items',
            component: 'Marketplacer_SellerShipping/js/view/seller-shipping-information/review/cart-items',
            /*children: {
                details: {
                    component: 'Marketplacer_SellerShipping/js/view/seller-shipping-information/review/item/details',
                    children: {
                        thumbnail: {
                            component: 'Magento_Checkout/js/view/summary/item/details/thumbnail',
                            displayArea: 'before_details'
                        },
                        subtotal: {
                            component: 'Magento_Checkout/js/view/summary/item/details/subtotal',
                            displayArea: 'after_details'
                        },
                    }
                }
            }*/
        },
    }

    return Component.extend({
        defaults: {
            template: 'Marketplacer_SellerShipping/seller-shipping-information/review',
            sellerId: null,
            sellerGroupsList: [],
        },
        sellerName: null,
        isLoading: true,

        /**
         * Initialize view.
         *
         * @returns {Component} Chainable.
         */
        initialize: function () {
            this._super();

            getSellerInformation(this.sellerId()).then(response => {
                this.sellerName(response.name);
            });

            children.cart_item.config = {
                sellerId: this.sellerId
            };

            helper.initChildrenComponents(children, this.name)

            return this;
        },
        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe(['sellerName', 'sellerId', 'isLoading']);

            return this;
        },
        /**
         * @return {String}
         */
        getShippingMethodTitle: function () {
            var shippingMethod = sellerQuote.getShippingMethod(this.sellerId())(),
                shippingMethodTitle = '';

            if (!shippingMethod) {
                return '';
            }

            shippingMethodTitle = shippingMethod['carrier_title'];

            if (typeof shippingMethod['method_title'] !== 'undefined') {
                shippingMethodTitle += ' - ' + shippingMethod['method_title'];
            }

            return shippingMethodTitle;
        },
        getShippingMethodPrice: function () {
            var shippingMethod = sellerQuote.getShippingMethod(this.sellerId())();

            if (!shippingMethod) {
                return '';
            }

            return shippingMethod.price_excl_tax;
        },
        /**
         * @param {*} price
         * @return {*|String}
         */
        getFormattedPrice: function (price) {
            return priceUtils.formatPriceLocale(price, quote.getPriceFormat());
        },
    });
});
