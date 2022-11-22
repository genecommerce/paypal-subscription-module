/**
 * PayPal Subscriptions
 */

define([
    'jquery',
    'mage/storage',
    'PayPal_Subscription/js/model/url-builder'
], function ($, storage, urlBuilder) {
    'use strict';

    return function (subscriptionId, qty) {

        var url = urlBuilder.createUrl('/subscription/mine/skipOrder/:subscriptionId', {
            subscriptionId: subscriptionId
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
        ).success(function (response) {
            // Return Response
            return response;
        })
    };
});
