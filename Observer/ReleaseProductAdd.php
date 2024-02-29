<?php
declare(strict_types=1);

namespace PayPal\Subscription\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use PayPal\Subscription\Helper\Data as SubscriptionHelper;
use PayPal\Subscription\Model\ConfigurationInterface;

class ReleaseProductAdd implements ObserverInterface
{
    /**
     * @param TaxHelper $taxHelper
     * @param TaxCalculationInterface $taxCalculation
     * @param ConfigurationInterface $config
     * @param ProductRepositoryInterface $productRepository
     * @param SubscriptionHelper $subscriptionHelper
     */
    public function __construct(
        private readonly TaxHelper $taxHelper,
        private readonly TaxCalculationInterface $taxCalculation,
        private readonly ConfigurationInterface $config,
        private readonly ProductRepositoryInterface $productRepository,
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

        $autoUpdatePrice = $this->config->getAutoUpdatePrice(ScopeInterface::SCOPE_STORE, $product->getStoreId());
        // DEBUG
        $autoUpdatePrice = true;
        $priceType = $product->getData(SubscriptionHelper::SUB_PRICE_TYPE) !== null
            ? (int) $product->getData(SubscriptionHelper::SUB_PRICE_TYPE)
            : null;
        if ($autoUpdatePrice === false && $priceType === SubscriptionHelper::FIXED_PRICE) {
            // Use existing subscription price.
            $priceValue = (float) $product->getPrice();
        } else {
            // Use current subscription price set to product.
            $priceValue = $product->getData(SubscriptionHelper::SUB_PRICE_VALUE) !== null
                ? (float) $product->getData(SubscriptionHelper::SUB_PRICE_VALUE)
                : null;
        }

        if ($priceType === SubscriptionHelper::FIXED_PRICE) {
            $item->setCustomPrice($priceValue);
            $item->setOriginalCustomPrice($priceValue);
        } elseif ($priceType === SubscriptionHelper::DISCOUNT_PRICE) {
            $subscriptionPrice = (float) $product->getPrice();
            if ($autoUpdatePrice === true) {
                // Load current product price and apply discount.
                $product = $this->productRepository->getById($product->getId());
                $subscriptionPrice = $this->subscriptionHelper->getDiscountedPrice(
                    $priceValue,
                    (float) $product->getPrice()
                );
            } else {
                // Use existing price set to subscription item. This price is already discounted.
                if ($this->taxHelper->priceIncludesTax($product->getStoreId())) {
                    // Subscription price need tax adding.
                    $rate = $this->taxCalculation->getCalculatedRate(
                        $product->getTaxClassId(),
                        $item->getQuote()->getCustomerId(),
                        $product->getStoreId()
                    );
                    $taxAmount = ($rate / 100) * (float) $product->getPrice();
                    $subscriptionPrice = (float) $product->getPrice() + $taxAmount;
                }
            }
            $item->setCustomPrice($subscriptionPrice);
            $item->setOriginalCustomPrice($subscriptionPrice);
        }
    }
}
