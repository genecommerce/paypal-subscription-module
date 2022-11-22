<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayPal\Subscription\Api;

/**
 * Interface FrequencyProfileRepositoryInterface
 */
interface FrequencyProfileRepositoryInterface
{
    /**
     * Get frequency profile by Id
     *
     * @param int $frequencyProfileId
     * @return \PayPal\Subscription\Api\Data\FrequencyProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $frequencyProfileId);

    /**
     * Get list of frequency profile
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \PayPal\Subscription\Api\Data\FrequencyProfileSearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Save frequency profile
     *
     * @param \PayPal\Subscription\Api\Data\FrequencyProfileInterface $frequencyProfile
     * @return \PayPal\Subscription\Api\Data\FrequencyProfileInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\PayPal\Subscription\Api\Data\FrequencyProfileInterface $frequencyProfile);

    /**
     * Delete frequency profile
     *
     * @param \PayPal\Subscription\Api\Data\FrequencyProfileInterface $frequencyProfile
     * @return void
     */
    public function delete(\PayPal\Subscription\Api\Data\FrequencyProfileInterface $frequencyProfile);
}
