<?php
declare(strict_types=1);

namespace PayPal\Subscription\ViewModel\Cart;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PayPal\Subscription\Api\FrequencyProfileRepositoryInterface;
use PayPal\Subscription\Helper\Data as SubscriptionHelper;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use PayPal\Subscription\Setup\Patch\Data\AddProductSubscriptionAttributes;

class Item implements ArgumentInterface
{
    /**
     * @var FrequencyProfileRepositoryInterface
     */
    private $frequencyProfile;

    /**
     * @var SubscriptionHelper
     */
    private $subscriptionHelper;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var PricingHelper
     */
    private $pricingHelper;

    /**
     * Item constructor.
     * @param FrequencyProfileRepositoryInterface $frequencyProfile
     * @param SerializerInterface $serializer
     * @param SubscriptionHelper $subscriptionHelper
     * @param PricingHelper $pricingHelper
     */
    public function __construct(
        FrequencyProfileRepositoryInterface $frequencyProfile,
        SerializerInterface $serializer,
        SubscriptionHelper $subscriptionHelper,
        PricingHelper $pricingHelper
    ) {
        $this->frequencyProfile = $frequencyProfile;
        $this->serializer = $serializer;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * @param QuoteItem $item
     * @return bool
     */
    public function hasSubscription(QuoteItem $item): bool
    {
        $isSubscription = $item->getOptionByCode(SubscriptionHelper::IS_SUBSCRIPTION);
        return $isSubscription ? (bool) $isSubscription->getValue() : false;
    }

    /**
     * @param QuoteItem $item
     * @return mixed
     */
    public function getFrequencyInterval(QuoteItem $item)
    {
        $interval = $item->getOptionByCode(SubscriptionHelper::FREQ_OPT_INTERVAL);
        return $interval->getValue();
    }

    /**
     * @param QuoteItem $item
     * @return mixed
     */
    public function getFrequencyIntervalName(QuoteItem $item)
    {
        $interval = $this->getFrequencyInterval($item);
        $options = $this->getFrequencyProfileOptions($item);
        foreach ($options as $option) {
            if ($option['interval'] === $interval) {
                return $option['name'];
            }
        }
        return null;
    }

    /**
     * @param QuoteItem $item
     * @return array
     */
    public function getFrequencyProfileOptions(QuoteItem $item): array
    {
        $product = $item->getProduct();
        try {
            $frequencyProfileId = $product->getData('subscription_frequency_profile');
            $frequencyProfile = $this->frequencyProfile->getById((int)$frequencyProfileId);
            return $this->serializer->unserialize($frequencyProfile->getFrequencyOptions());
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }

    /**
     * @param $item
     * @param $option
     * @return bool
     */
    public function getSelectedFrequency(QuoteItem $item, $option): bool
    {
        return (bool) (
            $item->getOptionByCode(SubscriptionHelper::FREQ_OPT_INTERVAL)->getValue() === $option['interval']
        );
    }

    /**
     * @return bool
     */
    public function isSubscriptionOnly(QuoteItem $item): bool
    {
        return (bool) $item->getProduct()->getData(SubscriptionHelper::SUB_ONLY);
    }

    /**
     * @param float $price
     * @return string
     */
    public function formatPrice(float $price): string
    {
        return $this->pricingHelper->currency($price, true, false);
    }

    /**
     * 0 - Fixed Price e.g 9.99
     * 1 - Discount off of base price e.g. 75% off 10.00 is 2.50
     * @return int|null
     */
    public function getSubscriptionPriceType(QuoteItem $item): ?int
    {
        $product = $item->getProduct();
        return $product->getData(SubscriptionHelper::SUB_PRICE_TYPE) !== null
            ? (int) $product->getData(SubscriptionHelper::SUB_PRICE_TYPE)
            : null;
    }

    /**
     * Returns the saving amount for a user.
     * @return string
     */
    public function getSubscriptionPriceSaving(
        QuoteItem $item
    ): string {

        /** @var ProductInterface|Product $subscriptionProduct */
        $subscriptionProduct = $item->getProduct();

        $subscriptionPriceType = $subscriptionProduct->getData(
            AddProductSubscriptionAttributes::SUBSCRIPTION_PRICE_TYPE
        );
        $subscriptionProductPriceValue = (float) $subscriptionProduct->getData(
            AddProductSubscriptionAttributes::SUBSCRIPTION_PRICE_VALUE
        );
        $discountedPrice = $subscriptionPriceType == SubscriptionHelper::DISCOUNT_PRICE ?
            $this->subscriptionHelper->getDiscountedPrice(
                $subscriptionProductPriceValue,
                (float) $subscriptionProduct->getFinalPrice()
            ) :
            (float) $subscriptionProductPriceValue;

        $discount = (float) $subscriptionProduct->getFinalPrice() - $discountedPrice;

        return $this->pricingHelper->currency(
            $discount,
            true,
            false
        );
    }

    /**
     * @return float|null
     */
    public function getSubscriptionPriceValue(QuoteItem $item): ?float
    {
        if (!property_exists($this, 'product')) {
            $this->product = $item->getProduct();
        }
        return $this->product->getData(SubscriptionHelper::SUB_PRICE_VALUE) !== null
            ? (float) $this->product->getData(SubscriptionHelper::SUB_PRICE_VALUE)
            : null;
    }

    /**
     * Returns the percentage saved
     * @return float
     */
    public function getPercentageSaved(QuoteItem $item): float
    {
        if ($this->getSubscriptionPriceValue($item)
            && $this->getSubscriptionPriceType($item) === SubscriptionHelper::DISCOUNT_PRICE) {
            return round($this->getSubscriptionPriceValue($item), 1);
        }

        return 0;
    }

}
