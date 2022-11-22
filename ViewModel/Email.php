<?php
declare(strict_types=1);

namespace PayPal\Subscription\ViewModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use PayPal\Subscription\Api\Data\SubscriptionItemInterface;

class Email implements ArgumentInterface
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var array
     */
    private $paymentTokenRenderers;

    /**
     * @var PricingHelper
     */
    private $pricingHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Email constructor.
     *
     * @param LayoutInterface $layout
     * @param PricingHelper $pricingHelper
     * @param ProductRepositoryInterface $productRepository
     * @param array $paymentTokenRenderers
     */
    public function __construct(
        LayoutInterface $layout,
        PricingHelper $pricingHelper,
        ProductRepositoryInterface $productRepository,
        array $paymentTokenRenderers = []
    ) {
        $this->layout = $layout;
        $this->pricingHelper = $pricingHelper;
        $this->productRepository = $productRepository;
        $this->paymentTokenRenderers = $paymentTokenRenderers;
    }

    /**
     * @param float $price
     * @return string
     */
    public function formatPrice(float $price): string
    {
        return $this->pricingHelper->currency(
            $price,
            true,
            false
        );
    }

    /**
     * Return Product name from product associated to subscription item
     *
     * @param SubscriptionItemInterface $subscriptionItem
     * @return string
     */
    public function getProductName(
        SubscriptionItemInterface $subscriptionItem
    ): string {
        $sku = $subscriptionItem->getSku();
        $productName = '';
        try {
            $product = $this->productRepository->get($sku);
            return $product->getName() ?: '';
        } catch (NoSuchEntityException $e) {
            return $productName;
        }
    }

    /**
     * Return Payment Token renderer HTML string
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     */
    public function getTokenHtml(
        PaymentTokenInterface $paymentToken
    ): string {
        $paymentTokenHtml = '';
        $tokenTypeRenderer = $this->paymentTokenRenderers[$paymentToken->getPaymentMethodCode()] ?? null;
        if ($tokenTypeRenderer !== null) {
            $tokenTypeRendererTemplate = $tokenTypeRenderer['template'] ?? null;
            if ($tokenTypeRendererTemplate !== null) {
                $tokenTypeRendererClass = $tokenTypeRenderer['class'] ?? Template::class;
                /** @var Template $tokenRenderer */
                $tokenRenderer = $this->layout->createBlock(
                    $tokenTypeRendererClass
                );
                $tokenRendererData = $tokenRenderer['data'] ?? [];
                $tokenRendererData['payment'] = $paymentToken;
                $tokenRenderer->addData($tokenRendererData);
                $tokenRenderer->setTemplate($tokenTypeRendererTemplate);
                $paymentTokenHtml = $tokenRenderer->toHtml();
            }
        }
        return $paymentTokenHtml;
    }
}
