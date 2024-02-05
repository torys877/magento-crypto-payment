<?php
/*
 * Copyright © Ihor Oleksiienko (https://github.com/torys877)
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Crypto\CryptoPayment\Model\Ui;

use Crypto\CryptoCurrency\Api\CryptoCurrencyRepositoryInterface;
use Crypto\CryptoCurrency\Api\Data\CryptoCurrencyInterface;
use Crypto\CryptoPayment\Gateway\Config\Config;
use Crypto\CryptoPayment\Helper\ConfigReader;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'crypto_payment';
    private Config $config;
    private Quote $quote;
    private StoreManagerInterface $storeManager;
    private CryptoCurrencyRepositoryInterface $cryptoCurrencyRepository;

    public function __construct(
        Config $config,
        Quote $quote,
        StoreManagerInterface $storeManager,
        CryptoCurrencyRepositoryInterface $cryptoCurrencyRepository
    ) {
        $this->config = $config;
        $this->quote = $quote;
        $this->storeManager = $storeManager;
        $this->cryptoCurrencyRepository = $cryptoCurrencyRepository;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        $isCurrencyActive = $this->cryptoCurrencyRepository->isCurrencyActive(
            $this->storeManager->getStore()->getCurrentCurrency()->getCode()
        );

        if (!$this->config->isActive() || !$isCurrencyActive) {
            return [];
        }

        $allActiveCurrencies = $this->cryptoCurrencyRepository->getAllActiveCurrencies();
        $currencies = [];

        if ($allActiveCurrencies->count()) {
            foreach ($allActiveCurrencies as $currency) {
                /** @var CryptoCurrencyInterface $currency */
                $currencies[] =
                    [
                        CryptoCurrencyInterface::CODE => $currency->getCode(),
                        CryptoCurrencyInterface::CRYPTO_CODE => $currency->getCryptoCode(),
                        CryptoCurrencyInterface::NAME => $currency->getName(),
                    ];
            }
        }

        $config = [
            'payment' => [
                ConfigReader::PAYMENT_CODE => [
                    'isActive' => $this->config->isActive(),
                    'title' => $this->config->getTitle(),
                    'store_currency_code' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
                    'currencies' => $currencies
                ]
            ]
        ];

        return $config;
    }
}
