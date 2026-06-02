<?php declare(strict_types=1);

namespace PayPal\Subscription\Plugin\Config;

use Magento\Config\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\ScopeInterface;
use PayPal\Subscription\Model\ConfigurationInterface;

class BraintreeVaultWarning
{
    private const SUBSCRIPTIONS_SECTION = 'paypal_subscriptions';
    private const BRAINTREE_CC_VAULT_ACTIVE_CONFIG_PATH = 'payment/braintree_cc_vault/active';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param MessageManagerInterface $messageManager
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly MessageManagerInterface $messageManager
    ) {
    }

    /**
     * Display a warning when subscriptions are enabled without Braintree card vaulting.
     *
     * @param Config $subject
     * @param Config $result
     * @return Config
     */
    public function afterSave(Config $subject, Config $result): Config
    {
        if ($subject->getSection() !== self::SUBSCRIPTIONS_SECTION) {
            return $result;
        }

        [$scopeType, $scopeCode] = $this->getScope($subject);

        if ($this->scopeConfig->isSetFlag(ConfigurationInterface::ACTIVE_CONFIG_PATH, $scopeType, $scopeCode)
            && !$this->scopeConfig->isSetFlag(self::BRAINTREE_CC_VAULT_ACTIVE_CONFIG_PATH, $scopeType, $scopeCode)
        ) {
            $this->messageManager->addWarningMessage(
                __(
                    'Braintree card vaulting must be enabled for subscriptions to work. '
                    . 'Enable "Enable Vault for Card Payments" in the Braintree payment settings.'
                )
            );
        }

        return $result;
    }

    /**
     * Get the scope that was saved by the config model.
     *
     * @param Config $config
     * @return array{0: string, 1: string|null}
     */
    private function getScope(Config $config): array
    {
        if ($config->getStore()) {
            return [ScopeInterface::SCOPE_STORE, (string) $config->getScopeCode()];
        }

        if ($config->getWebsite()) {
            return [ScopeInterface::SCOPE_WEBSITE, (string) $config->getScopeCode()];
        }

        return [ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null];
    }
}
