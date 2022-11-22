<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayPal\Subscription\Cron;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use PayPal\Subscription\Api\SubscriptionManagementInterface;
use PayPal\Subscription\Model\Config\Source\Subscription\MessageBroker;
use PayPal\Subscription\Model\ConfigurationInterface;
use PayPal\Subscription\Model\Email\Release as ReleaseEmail;
use PayPal\Subscription\Model\ReleaseCronRunningFlagInterface;
use PayPal\Subscription\Model\ReleaseConsumer;

class Release
{
    public const TOPIC_NAME = 'paypal.subscription.release';
    public const TOPIC_NAME_DB = 'paypal.subscription.release.db';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SubscriptionManagementInterface
     */
    private $subscriptionManagement;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var ReleaseEmail
     */
    private $releaseEmail;

    /**
     * @var ReleaseCronRunningFlagInterface
     */
    private $releaseCronRunningFlag;

    /**
     * @var ReleaseConsumer
     */
    private $releaseConsumer;


    /**
     * Release constructor
     *
     * @param ConfigurationInterface $configuration
     * @param CustomerRepositoryInterface $customerRepository
     * @param SubscriptionManagementInterface $subscriptionManagement
     * @param PublisherInterface $publisher
     * @param ReleaseEmail $releaseEmail
     * @param ReleaseCronRunningFlagInterface $releaseCronRunningFlag
     * @param ReleaseConsumer $releaseConsumer
     */
    public function __construct(
        ConfigurationInterface $configuration,
        CustomerRepositoryInterface $customerRepository,
        SubscriptionManagementInterface $subscriptionManagement,
        PublisherInterface $publisher,
        ReleaseEmail $releaseEmail,
        ReleaseCronRunningFlagInterface $releaseCronRunningFlag,
        ReleaseConsumer $releaseConsumer
    ) {
        $this->configuration = $configuration;
        $this->customerRepository = $customerRepository;
        $this->subscriptionManagement = $subscriptionManagement;
        $this->publisher = $publisher;
        $this->releaseEmail = $releaseEmail;
        $this->releaseCronRunningFlag = $releaseCronRunningFlag;
        $this->releaseConsumer = $releaseConsumer;
    }

    /**
     * Release the subscriptions via cron
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(): void
    {
        if ($this->releaseCronRunningFlag->getIsReleaseCronRunning() === true) {
            return;
        }
        $this->releaseCronRunningFlag->setIsReleaseCronRunning(true);
        $releases = $this->subscriptionManagement->collectReleases(
            date(
                'Y-m-d 00:00:00',
                strtotime(
                    date('Y-m-d 00:00:00') . ' - 1 year'
                )
            ),
            date('Y-m-d 23:59:59')
        );
        $configuredMessageBroker = $this->configuration->getMessageBroker();
        foreach ($releases as $release) {
            $this->releaseConsumer->execute($release);
        }
        $reminderTiming = $this->configuration->getReleaseReminderEmailTiming();
        if ($reminderTiming !== 0) {
            $subscriptionsForReleaseReminderEmails = $this->subscriptionManagement->collectReleases(
                date(
                    'Y-m-d 00:00:00',
                    strtotime(
                        date('Y-m-d 00:00:00') . ' + ' . $reminderTiming . 'days'
                    )
                ),
                date(
                    'Y-m-d 23:59:59',
                    strtotime(
                        date('Y-m-d 23:59:59') . ' + ' . $reminderTiming . 'days'
                    )
                )
            );
            foreach ($subscriptionsForReleaseReminderEmails as $subscriptionsForReleaseReminderEmail) {
                $customer = $this->customerRepository->getById(
                    (int) $subscriptionsForReleaseReminderEmail->getCustomerId()
                );
                $this->releaseEmail->success(
                    $customer,
                    $subscriptionsForReleaseReminderEmail
                );
            }
        }
        $this->releaseCronRunningFlag->setIsReleaseCronRunning(false);
    }
}
