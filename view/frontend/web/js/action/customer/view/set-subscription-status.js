/**
 * PayPal Subscriptions
 */

define([
    'jquery',
    'mage/storage',
    'PayPal_Subscription/js/model/url-builder'
], function ($, storage, urlBuilder) {
    'use strict';

    return function (subscriptionId, status) {

        var url = urlBuilder.createUrl('/subscription/mine/status/:subscriptionId/:statusUpdate', {
            subscriptionId: subscriptionId,
            statusUpdate: status
        });

        /**
         * Adds error message
         *
         * @param {String} message
         */

        return storage.put(
            url,
            {},
            false
        ).done(function (response) {

            // Return Response
            return response;
        })
    };
});
