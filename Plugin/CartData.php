<?php
declare(strict_types=1);

namespace PayPal\Subscription\Plugin;

use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;

/**
 * Plugin to add masked quote id to cart data if guest≥
 */
class CartData
{

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $maskedQuote;

    /**
     * CartData constructor.
     * @param Cart $cart
     * @param Session $checkoutSession
     * @param QuoteIdToMaskedQuoteIdInterface $maskedQuote
     */
    public function __construct(
        Session $checkoutSession,
        QuoteIdToMaskedQuoteIdInterface $maskedQuote
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->maskedQuote = $maskedQuote;
    }

    /**
     * @param Cart $subject
     * @param $result
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetSectionData(Cart $subject, $result): array
    {
        $quote = $this->checkoutSession->getQuote();
        $quoteId = $quote ?
            (int) $quote->getId() :
            null;
        if ($quote &&
            !$quote->getCustomerId() &&
            $quoteId != null
        ) {
            $maskedId = $this->maskedQuote->execute((int) $quote->getId());
            $result['guest_masked_id'] = $maskedId;
        }
        return $result;
    }
}

