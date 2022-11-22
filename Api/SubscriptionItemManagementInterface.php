<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayPal\Subscription\Api;

interface SubscriptionItemManagementInterface
{
    /**
     * Create subscription item
     *
     * @param \PayPal\Subscription\Api\Data\SubscriptionInterface $subscription
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return mixed
     */
    public function createSubscriptionItem(\PayPal\Subscription\Api\Data\SubscriptionInterface $subscription, \Magento\Sales\Api\Data\OrderItemInterface $item);
}
