<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayPal\Subscription\Block\Adminhtml\Subscriptions\Edit;

class ReleaseButton extends GenericButton
{
    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $confirmMessage = __('Are you sure you want to release this Subscription now?');

        return [
            'label' => __('Release Subscription'),
            'on_click' => sprintf('deleteConfirm("%s", "%s")', $confirmMessage, $this->getManualReleaseUrl()),
            'class' => 'release secondary',
            'sort_order' => 90,
            'aclResource' => 'PayPal_Subscriptions::subscriptions_release'
        ];
    }

    /**
     * Get manual release url
     *
     * @return string
     */
    private function getManualReleaseUrl()
    {
        $id = $this->request->getParam('id');
        return $this->getUrl('paypal_subscription/subscriptions/release', ['id' => $id]);
    }
}
