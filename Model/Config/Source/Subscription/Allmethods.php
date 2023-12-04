<?php

declare(strict_types=1);

namespace PayPal\Subscription\Model\Config\Source\Subscription;

class Allmethods extends \Magento\Payment\Model\Config\Source\Allmethods
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $paymentList = $this->_paymentData->getPaymentMethodList(true, true, true);
        return isset($paymentList['braintree_group']) ? ['braintree_group' => $paymentList['braintree_group']] : [];
    }
}
