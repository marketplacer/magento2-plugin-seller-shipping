define([
    'underscore',
    'uiComponent',
    'Marketplacer_SellerShipping/js/model/quote',
    'Marketplacer_SellerShipping/js/helper',
    //'Marketplacer_SellerShipping/js/model/cart/estimate-service'
], function (
    _,
    Component,
    sellerQuote,
    helper,
    /*estimateService*/) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Marketplacer_SellerShipping/seller/list',
            childrenComponentsList: [],
        },
        initialize: function () {
            this._super();

            this.collectChildrenComponentsList();
            return this;
        },
        collectChildrenComponentsList: function () {
            let self = this;

            _.each(sellerQuote.getSellersId(), function (sellerId) {
                let name = 'seller' + sellerId;
                let component = {
                    [name]: {
                        displayArea: self.displayArea,
                        component: 'Marketplacer_SellerShipping/js/view/seller/seller-item',
                        config: {
                            sellerId: sellerId,
                        }
                    }
                }

                if (self.childrenComponentsList[name] === undefined) {
                    self.childrenComponentsList.push(component);
                    helper.initChildrenComponents(component, self.parentName);
                }
            });
        }
    });
});
