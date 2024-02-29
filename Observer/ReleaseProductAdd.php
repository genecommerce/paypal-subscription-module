<?php
declare(strict_types=1);

namespace PayPal\Subscription\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item;
use PayPal\Subscription\Helper\Data as SubscriptionHelper;

class ReleaseProductAdd implements ObserverInterface
{
    /**
     * @param SubscriptionHelper $subscriptionHelper
     */
    public function __construct(
        private readonly SubscriptionHelper $subscriptionHelper
    ) {}

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Item $item */
        $item = $observer->getEvent()->getData('quote_item');

        if (!$item->getQuote() instanceof CartInterface ||
            (bool) $item->getQuote()->getData('is_subscription_release') !== true
        ) {
            return;
        }

        $product = $item->getProduct();
        if (!$product instanceof ProductInterface) {
            throw new NotFoundException(__("Could not find product for quote item ID %1", $item->getItemId()));
        }

        $priceType = $product->getData(SubscriptionHelper::SUB_PRICE_TYPE) !== null
            ? (int) $product->getData(SubscriptionHelper::SUB_PRICE_TYPE)
            : null;
        $priceValue = $product->getData(SubscriptionHelper::SUB_PRICE_VALUE) !== null
            ? (float) $product->getData(SubscriptionHelper::SUB_PRICE_VALUE)
            : null;

        if ($priceType === SubscriptionHelper::FIXED_PRICE) {
            $item->setCustomPrice($priceValue);
            $item->setOriginalCustomPrice($priceValue);
        } elseif ($priceType === SubscriptionHelper::DISCOUNT_PRICE) {
            $discountedPrice = $this->subscriptionHelper->getDiscountedPrice(
                $priceValue,
                (float) $product->getPrice()
            );
            $item->setCustomPrice($discountedPrice);
            $item->setOriginalCustomPrice($discountedPrice);
        }
    }
}
