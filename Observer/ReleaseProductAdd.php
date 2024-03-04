<?php
declare(strict_types=1);

namespace PayPal\Subscription\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Helper\Data as TaxHelper;

class ReleaseProductAdd implements ObserverInterface
{
    /**
     * @param TaxHelper $taxHelper
     * @param TaxCalculationInterface $taxCalculation
     */
    public function __construct(
        private readonly TaxHelper $taxHelper,
        private readonly TaxCalculationInterface $taxCalculation
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

        // Subscription price already set to product, use as custom price on quote item.
        $subscriptionPrice = (float) $product->getPrice();
        $item->setCustomPrice($subscriptionPrice);
        $item->setOriginalCustomPrice($subscriptionPrice);
    }
}