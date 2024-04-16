<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayPal\Subscription\Api;

use PayPal\Subscription\Api\Data\SubscriptionInterface;

/**
 * @api
 */
interface SubscriptionManagementInterface
{
    /**
     * Create subscription with item
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @param int $frequency
     * @param int|null $frequencyProfileId
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createSubscriptionWithItem(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Api\Data\OrderItemInterface $item,
        int $frequency,
        int $frequencyProfileId = null
    );

    /**
     * Create subscription
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param int $frequency
     * @param int|null $frequencyProfileId
     * @return \PayPal\Subscription\Api\Data\SubscriptionInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createSubscription(
        \Magento\Sales\Api\Data\OrderInterface $order,
        int $frequency,
        $frequencyProfileId = null
    ): \PayPal\Subscription\Api\Data\SubscriptionInterface;

    /**
     * Change frequency
     *
     * @param int $customerId
     * @param int $subscriptionId
     * @param int $frequency
     * @return \PayPal\Subscription\Api\Data\SubscriptionInterface
     */
    public function changeFrequency(
        int $customerId,
        int $subscriptionId,
        int $frequency
    ): \PayPal\Subscription\Api\Data\SubscriptionInterface;

    /**
     * Change subscription status
     *
     * @param int $customerId
     * @param int $subscriptionId
     * @param int $status
     * @return \PayPal\Subscription\Api\Data\SubscriptionInterface
     */
    public function changeStatus(
        int $customerId,
        int $subscriptionId,
        int $status
    ): \PayPal\Subscription\Api\Data\SubscriptionInterface;

    /**
     * Change existing address
     *
     * @param int $customerId
     * @param string $addressType
     * @param int $subscriptionId
     * @param int $addressId
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function changeAddressExisting(
        int $customerId,
        string $addressType,
        int $subscriptionId,
        int $addressId
    ): \Magento\Customer\Api\Data\AddressInterface;

    /**
     * Change new address
     *
     * @param int $customerId
     * @param string $addressType
     * @param int $subscriptionId
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function changeAddressNew(
        int $customerId,
        string $addressType,
        int $subscriptionId,
        \Magento\Customer\Api\Data\AddressInterface $address
    ): \Magento\Customer\Api\Data\AddressInterface;

    /**
     * Change payment method
     *
     * @param int $customerId
     * @param int $subscriptionId
     * @param string $paymentPublicHash
     * @return \PayPal\Subscription\Api\Data\SubscriptionInterface
     */
    public function changePaymentMethod(
        int $customerId,
        int $subscriptionId,
        string $paymentPublicHash
    ): \PayPal\Subscription\Api\Data\SubscriptionInterface;

    /**
     * Collect releases
     *
     * @param string $from
     * @param string $to
     * @param bool|null $emailReminder
     * @return SubscriptionInterface[]
     */
    public function collectReleases(
        string $from,
        string $to,
        ?bool $emailReminder = null
    ): array;
}
