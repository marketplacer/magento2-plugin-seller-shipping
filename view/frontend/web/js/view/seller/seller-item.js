define([
    'ko',
    'jquery',
    'underscore',
    'uiComponent',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/quote',
    'Marketplacer_SellerShipping/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Marketplacer_SellerShipping/js/helper',
    'Marketplacer_SellerShipping/js/action/get-seller-information',
    //'Marketplacer_SellerShipping/js/action/get-seller-shipment-rates',
    'Marketplacer_SellerShipping/js/model/shipping-service',
    'Marketplacer_SellerShipping/js/checkout-data',
    'Marketplacer_SellerShipping/js/action/select-shipping-method',
], function (
    ko,
    $,
    _,
    Component,
    shippingService,
    quote,
    sellerQuote,
    totals,
    helper,
    getSellerInformation,
    //getSellerShipmentRates,
    mpShippingService,
    sellerCheckoutData,
    sellerSelectShippingMethodAction
) {
    'use strict';

    let children = {
        cart_item: {
            displayArea: 'cart-items',
            component: 'Marketplacer_SellerShipping/js/view/seller/summary/cart-items',
            children: {
                details: {
                    component: 'Magento_Checkout/js/view/summary/item/details',
                    children: {
                        thumbnail: {
                            component: 'Magento_Checkout/js/view/summary/item/details/thumbnail',
                            displayArea: 'before_details'
                        },
                        subtotal: {
                            component: 'Magento_Checkout/js/view/summary/item/details/subtotal',
                            displayArea: 'after_details'
                        },
                        message: {
                            component: 'Magento_Checkout/js/view/summary/item/details/message',
                            displayArea: 'item_message'
                        }
                    }
                }
            }
        },
    }, proceedData = function (data) {
        if (_.isObject(data) && _.isObject(data['extension_attributes'])) {
            _.each(data['extension_attributes'], function (element, index) {
                data[index] = element;
            });
        }

        return data;
    }, generalSellerId = window.checkoutConfig.marketplacer_seller_shipping.generalSellerId;

    return Component.extend({
        defaults: {
            shippingMethodItemTemplate: 'Marketplacer_SellerShipping/seller/shipping-method-item',
            template: 'Marketplacer_SellerShipping/seller/seller-item',
            sellerId: null,
            sellerName: null,
        },
        cartIsLoading: true,
        isLoading: true,

        /**
         * @inheritdoc
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

            this.isLoading(mpShippingService.getIsLoading(this.sellerId())())

            mpShippingService.getIsLoading(this.sellerId()).subscribe(function (result) {
                this.isLoading(result);
            }.bind(this));


            return this;
        },
        initObservable: function () {
            this._super().
            observe(['sellerName', 'sellerId', 'isLoading', 'cartIsLoading']);

            return this;
        },
        /**
         * @param {Object} shippingMethod
         * @return {Boolean}
         */
        selectShippingMethod: function (shippingMethod) {
            let data = proceedData(shippingMethod),
                sellerId = data.seller_id !== undefined ? data.seller_id : generalSellerId;

            sellerSelectShippingMethodAction(sellerId, shippingMethod);
            sellerCheckoutData.setSelectedShippingRateForSeller(
                sellerId, shippingMethod['carrier_code'] + '_' + shippingMethod['method_code']
            );

            return true;
        },
        isSelected: function () {
            let method = sellerQuote.getShippingMethod(this.sellerId())();

            return method ?
                method['carrier_code'] + '_' + method['method_code'] :
                null;
        },
        /**
         *
         * @returns {*}
         */
        getRates: function () {
            return mpShippingService.getShippingRates(this.sellerId());
        },
    });
});
