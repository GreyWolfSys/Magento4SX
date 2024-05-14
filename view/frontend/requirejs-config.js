var config = {
    map: {
        '*': {
            'Magento_Tax/js/view/checkout/cart/totals/grand-total':
                'Altitude_SX/js/view/checkout/cart/totals/grand-total',
            'Magento_Tax/js/view/checkout/summary/grand-total':
                'Altitude_SX/js/view/checkout/summary/grand-total',
			'Magento_Checkout/template/shipping.html':
                'Altitude_SX/template/shipping.html'
        }
    },
	config: {
        mixins: {
            'Magento_ConfigurableProduct/js/configurable': {
                'Altitude_SX/js/model/skuswitch': true
            },
			'Magento_Swatches/js/swatch-renderer': {
                'Altitude_SX/js/model/swatch-skuswitch': true
            },
            'Magento_Checkout/js/action/place-order': {
                'Altitude_SX/js/order/order_instructions': true
            },

        }
    }
};
