<?php

declare(strict_types=1);

namespace PayPal\Subscription\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\ScopeInterface;
use PayPal\Subscription\Api\Data\SubscriptionInterface;
use PayPal\Subscription\Api\SkipNextSubscriptionOrderInterface;
use PayPal\Subscription\Api\SubscriptionRepositoryInterface;
use PayPal\Subscription\Helper\Data as SubscriptionHelper;
use PayPal\Subscription\Model\Email\Subscription as SubscriptionEmail;

/**
 * Class SkipNextSubscriptionOrder
 * @package PayPal\Subscription\Model
 */
class SkipNextSubscriptionOrder implements SkipNextSubscriptionOrderInterface
{
    /**
     * @var SubscriptionRepositoryInterface
     */
    private $subscriptionRepository;

    /**
     * @var SubscriptionEmail
     */
    private $subscriptionEmailSender;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * SkipNextSubscriptionOrder constructor
     * @param SubscriptionRepositoryInterface $subscriptionRepository
     * @param CustomerRepository $customerRepository
     * @param SubscriptionEmail $subscriptionEmailSender
     */
    public function __construct(
        SubscriptionRepositoryInterface $subscriptionRepository,
        CustomerRepository $customerRepository,
        SubscriptionEmail $subscriptionEmailSender
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->customerRepository = $customerRepository;
        $this->subscriptionEmailSender = $subscriptionEmailSender;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(
        int $subscriptionId,
        int $customerId
    ): SubscriptionInterface {
        $subscription = $this->subscriptionRepository->getById($subscriptionId);
        $subscription->setNextReleaseDate(date(
            'Y-m-d H:i:s',
            strtotime(sprintf(
                $subscription->getNextReleaseDate() . ' + %d days',
                $subscription->getFrequency()
            ))
        ));
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->getById(
            $subscription->getCustomerId()
        );
        $this->subscriptionEmailSender->skippedSubscription(
            $subscription,
            $customer
        );
        $this->subscriptionRepository->save($subscription);
        return $subscription;
    }
}
