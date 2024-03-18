<?php

declare(strict_types=1);

namespace PayPal\Subscription\Model\Email;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Mail\Template\TransportBuilderFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PayPal\Subscription\Api\Data\SubscriptionInterface;
use PayPal\Subscription\Model\ConfigurationInterface;
use PayPal\Subscription\Model\Email;
use PayPal\Subscription\Model\Email\Subscription as SubscriptionEmail;
use PayPal\Subscription\ViewModel\PaymentDetails;
use Psr\Log\LoggerInterface;

/**
 * Class Release
 * @package PayPal\Subscription\Model\Email
 */
class Release extends Email
{
    public const TEMPLATE_FAILURE = 'paypal_subscriptions_email_configuration_release_failure';
    public const CONFIG_FAILURE = 'paypal_subscriptions/email_configuration/release_failure';
    public const TEMPLATE_FAILURE_ADMIN = 'paypal_subscriptions_email_configuration_release_failure_admin';
    public const CONFIG_FAILURE_ADMIN = 'paypal_subscriptions/email_configuration/release_failure_admin';
    public const TEMPLATE_REMINDER = 'paypal_subscriptions_email_configuration_release_reminder';
    public const CONFIG_REMINDER = 'paypal_subscriptions/email_configuration/release_reminder';

    /**
     * @var PaymentDetails
     */
    private $paymentDetails;

    /**
     * @var SubscriptionEmail
     */
    private $subscriptionEmail;

    /**
     * Release constructor.
     * @param TransportBuilderFactory $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param LoggerInterface $logger
     * @param PaymentDetails $paymentDetails
     * @param ConfigurationInterface $configuration
     * @param Subscription $subscriptionEmail
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        TransportBuilderFactory $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        LoggerInterface $logger,
        PaymentDetails $paymentDetails,
        ConfigurationInterface $configuration,
        SubscriptionEmail $subscriptionEmail,
        TimezoneInterface $timezone
    ) {
        parent::__construct(
            $transportBuilder,
            $storeManager,
            $scopeConfig,
            $eventManager,
            $logger,
            $configuration,
            $timezone
        );
        $this->paymentDetails = $paymentDetails;
        $this->subscriptionEmail = $subscriptionEmail;
    }

    /**
     * @param CartInterface $quote
     * @param CustomerInterface $customer
     * @param SubscriptionInterface $subscription
     * @return array
     */
    public function success(
        CustomerInterface $customer,
        SubscriptionInterface $subscription
    ): array {
        $subscriptionItems = $this->subscriptionEmail->getSubscriptionItems(
            $subscription->getId()
        );
        $data = [
            'customer_name' => sprintf(
                '%1$s %2$s',
                $customer->getFirstname(),
                $customer->getLastname()
            ),
            'subscription' => $subscription,
            'frequency_label' => $this->subscriptionEmail->getSubscribedFrequencyLabel(
                $subscriptionItems,
                $subscription->getFrequency()
            ),
            'items' => $subscriptionItems,
            'formatted_next_release' => $this->formatDate(
                $subscription->getNextReleaseDate()
            )
        ];
        $template = $this->getScopeConfig()->getValue(
            self::CONFIG_REMINDER,
            ScopeInterface::SCOPE_STORE
        ) ?? self::TEMPLATE_REMINDER;
        $this->eventManager->dispatch(
            'send_reminder_email_before',
            [
                'data' => $data,
                'template' => $template
            ]
        );
        return $this->sendEmail(
            $data,
            $customer,
            $template
        );
    }

    /**
     * @param CustomerInterface $customer
     * @param int $originalOrderId
     * @param int $newOrderId
     * @return array
     */
    public function priceChanged(CustomerInterface $customer, int $originalOrderId, int $newOrderId): array
    {
        $data = [
            'customer_name' => sprintf(
                '%1$s %2$s',
                $customer->getFirstname(),
                $customer->getLastname()
            ),

        ];
    }

    /**
     * @param CustomerInterface $customer
     * @param SubscriptionInterface $subscription
     * @param string $reason
     * @return array
     */
    public function failure(
        CustomerInterface $customer,
        SubscriptionInterface $subscription,
        string $reason
    ): array {
        $data = [
            'customer_name' => sprintf(
                '%1$s %2$s',
                $customer->getFirstname(),
                $customer->getLastname()
            ),
            'failure_reason' => $reason,
            'subscription' => $subscription,
            'items' => $this->subscriptionEmail->getSubscriptionItems(
                $subscription->getId()
            ),
            'maskedCC' => $this->paymentDetails->getMaskedCardNumber(
                $subscription
            ) ?: __('Not visible'),
            'expirationDate' => $this->paymentDetails->getCardExpiry(
                $subscription
            ) ?: __('Not visible'),
            'formatted_next_release' => $this->formatDate(
                $subscription->getNextReleaseDate()
            )
        ];
        return $this->sendEmail(
            $data,
            $customer,
            $this->getCustomTemplate()
        );
    }

    /**
     * @param string $reason
     * @param SubscriptionInterface $subscription
     * @return array
     */
    public function failureAdmin(
        string $reason,
        SubscriptionInterface $subscription
    ): array {
        $data = [
            'customer_name' => 'Admin',
            'failure_reason' => $reason,
            'order_items' => [],
            'formatted_next_release' => $this->formatDate(
                $subscription->getNextReleaseDate()
            ),
            'subscription' => $subscription
        ];
        return $this->sendEmailAdmin(
            $data,
            $this->getCustomAdminTemplate()
        );
    }

    /**
     * @return string
     */
    private function getCustomTemplate(): string
    {
        $template = $this->getScopeConfig()->getValue(
            self::CONFIG_FAILURE,
            ScopeInterface::SCOPE_STORE
        );
        return $template ?? self::TEMPLATE_FAILURE;
    }

    /**
     * @return string
     */
    private function getCustomAdminTemplate(): string
    {
        $template = $this->getScopeConfig()->getValue(
            self::CONFIG_FAILURE_ADMIN,
            ScopeInterface::SCOPE_STORE
        );
        return $template ?? self::TEMPLATE_FAILURE_ADMIN;
    }
}
