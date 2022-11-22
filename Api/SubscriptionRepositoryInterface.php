<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayPal\Subscription\Api;

/**
 * Interface SubscriptionRepositoryInterface
 */
interface SubscriptionRepositoryInterface
{
    /**
     * Get subscription by Id
     *
     * @param int $subscriptionId
     * @return \PayPal\Subscription\Api\Data\SubscriptionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $subscriptionId);

    /**
     * Get subscription by Order Id
     *
     * @param int $orderId
     * @return \PayPal\Subscription\Api\Data\SubscriptionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByOrderId(int $orderId);

    /**
     * Get customer subscription
     *
     * @param int $customerId
     * @param int $subscriptionId
     * @return \PayPal\Subscription\Api\Data\SubscriptionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerSubscription(
        int $customerId,
        int $subscriptionId
    );

    /**
     * Get subscription list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResults
     * @todo change this to be custom search interface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Save subscription
     *
     * @param \PayPal\Subscription\Api\Data\SubscriptionInterface $subscription
     * @return \PayPal\Subscription\Api\Data\SubscriptionInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(\PayPal\Subscription\Api\Data\SubscriptionInterface $subscription);

    /**
     * Delete subscription
     *
     * @param \PayPal\Subscription\Api\Data\SubscriptionInterface $subscription
     * @return void
     */
    public function delete(\PayPal\Subscription\Api\Data\SubscriptionInterface $subscription);
}
