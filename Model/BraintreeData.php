<?php

declare(strict_types=1);

namespace PayPal\Subscription\Model;

use Magento\Framework\Model\AbstractModel;
use PayPal\Subscription\Api\Data\BraintreeDataInterface;

class BraintreeData extends AbstractModel implements BraintreeDataInterface
{
    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->getData('token');
    }

    /**
     * @param string $token
     * @return BraintreeDataInterface
     */
    public function setToken($token): BraintreeDataInterface
    {
        return $this->setData('token', $token);
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->getData('error');
    }

    /**
     * @param string $error
     * @return BraintreeDataInterface
     */
    public function setError($error): BraintreeDataInterface
    {
        return $this->setData('error', $error);
    }
}
