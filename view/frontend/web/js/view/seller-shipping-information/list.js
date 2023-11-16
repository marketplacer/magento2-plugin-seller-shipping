define([
    'underscore',
    'uiComponent',
    'Marketplacer_SellerShipping/js/helper',
    'Marketplacer_SellerShipping/js/model/quote',
], function (
    _,
    Component,
    helper,
    sellerQuote
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Marketplacer_SellerShipping/seller-shipping-information/list',
            childrenComponentsList: [],
        },

        /**
         * Initialize view.
         *
         * @returns {Component} Chainable.
         */
        initialize: function () {
            this._super();

            this.collectChildrenComponentsList();

            return this;
        },
        collectChildrenComponentsList: function () {
            _.each(sellerQuote.getSellersId(), (sellerId) => {
                let name = 'seller' + sellerId;
                let component = {
                    [name]: {
                        displayArea: this.displayArea,
                        component: 'Marketplacer_SellerShipping/js/view/seller-shipping-information/review',
                        config: {
                            sellerId: sellerId,
                        }
                    }
                }

                if (this.childrenComponentsList[name] === undefined) {
                    this.childrenComponentsList.push(component);
                    helper.initChildrenComponents(component, this.name);
                }
            });
        },
    });
});
