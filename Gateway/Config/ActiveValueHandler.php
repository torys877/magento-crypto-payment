<?php
/*
 * Copyright © Ihor Oleksiienko (https://github.com/torys877)
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Crypto\CryptoPayment\Gateway\Config;

use Crypto\CryptoCurrency\Api\CryptoCurrencyRepositoryInterface;
use Crypto\CryptoPayment\Gateway\Config\Config as ConfigHelper;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Store\Model\StoreManagerInterface;

class ActiveValueHandler implements ValueHandlerInterface
{
    private StoreManagerInterface $storeManager;
    private ConfigHelper $config;
    private CryptoCurrencyRepositoryInterface $cryptoCurrencyRepository;

    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigHelper $config,
        CryptoCurrencyRepositoryInterface $cryptoCurrencyRepository
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->cryptoCurrencyRepository = $cryptoCurrencyRepository;
    }

    public function handle(array $subject, $storeId = null): bool
    {
        $isCurrencyActive = $this->cryptoCurrencyRepository->isCurrencyActive(
            $this->storeManager->getStore()->getCurrentCurrency()->getCode()
        );

        if (!$this->config->isActive() || !$isCurrencyActive) {
            return false;
        }

        return true;
    }
}
