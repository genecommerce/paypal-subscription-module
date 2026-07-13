define([], function () {
    'use strict';

    /**
     * Quote Item Data
     * @type {Window.checkoutConfig.quoteItemData}
     */
    let quoteItemData = window.checkoutConfig.quoteItemData ?? [];

    return function (originalHostedFields) {
        return originalHostedFields.extend({
            defaults: {
                template: 'PayPal_Subscription/payment/form'
            },

            /**
             * Is vault enabled
             *
             * @returns {boolean|*}
             */
            isVaultEnabled: function () {
                // If cart contains any subscription item, vault must be enabled.
                return this.cartContainsSubscriptions() === true ? true : this.vaultEnabler.isVaultEnabled();
            },

            /**
             * Check whether cart contains subscription products or not
             *
             * @returns {*}
             */
            cartContainsSubscriptions: function () {
                return quoteItemData.some(item => item.is_subscription === "1");
            },

            /**
             * Get data
             *
             * @returns {*}
             */
            getData: function () {
                let data = this._super();

                if (this.cartContainsSubscriptions() === true) {
                    data['additional_data']['is_active_payment_token_enabler'] = true;
                }

                return data;
            }
        });
    };
});
