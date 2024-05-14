define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
], function (Component, ko, $, summary,quote) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Altitude_SX/orderinstructions'
            },
            
            getValue: function() {
                var instructions = "!";
                console.log(quote);
                console.log(Component);
                console.log(ko);
                console.log(summary);
                console.log(window.checkoutConfig);
                
                instructions = $('[name="order_instructions"]').val();
                return instructions;
            },
            initialize: function () {
                var self = this;
                this._super();
            }

        });
    }
);