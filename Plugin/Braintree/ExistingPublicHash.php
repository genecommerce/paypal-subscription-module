<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PayPal\Subscription\Plugin\Braintree;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface as Subject;
use Psr\Log\LoggerInterface;

class ExistingPublicHash
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Subject $subject
     * @param PaymentTokenInterface $token
     * @param OrderPaymentInterface $payment
     * @return array
     */
    public function beforeSaveTokenWithPaymentLink(
        Subject $subject,
        PaymentTokenInterface $token,
        OrderPaymentInterface $payment
    ): array {
        try {
            $existingToken = $subject->getByGatewayToken(
                $token->getGatewayToken(),
                $token->getPaymentMethodCode(),
                $token->getCustomerId()
            );
            if ($existingToken instanceof PaymentTokenInterface &&
                $existingToken->getPublicHash() !== $token->getPublicHash() &&
                $this->tokensAreBraintree($token, $existingToken) === true
            ) {
                $token->setPublicHash($existingToken->getPublicHash());
            }
        } catch (\Throwable $exception) {
            $this->logger->error("An error occurred whilst setting existing public hash to current token", [
                'exception_message' => $exception->getMessage(),
            ]);
        }
        return [$token, $payment];
    }

    /**
     * @param PaymentTokenInterface $token
     * @param PaymentTokenInterface $existingToken
     * @return bool
     */
    private function tokensAreBraintree(PaymentTokenInterface $token, PaymentTokenInterface $existingToken): bool
    {
        return $token->getPaymentMethodCode() === 'braintree' && $existingToken->getPaymentMethodCode() === 'braintree';
    }
}
