var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/shipping-rate-processor/customer-address': {
                'Marketplacer_SellerShipping/js/mixins/model/shipping-rate-processor/customer-address': true
            },
            'Magento_Checkout/js/model/shipping-rate-processor/new-address': {
                'Marketplacer_SellerShipping/js/mixins/model/shipping-rate-processor/new-address': true
            },
            'Magento_Checkout/js/view/shipping-information': {
                'Magento_InventoryInStorePickupFrontend/js/view/shipping-information-ext': true,
                'Marketplacer_SellerShipping/js/mixins/view/shipping-information': true
            },
            'Magento_Checkout/js/model/place-order': {
                'Marketplacer_SellerShipping/js/mixins/model/place-order-mixin': true,
            },
            'Magento_Checkout/js/view/shipping': {
                'Marketplacer_SellerShipping/js/mixins/view/shipping': true
            }
        },
    }
};