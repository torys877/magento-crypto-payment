<?php
/*
 * Copyright © Ihor Oleksiienko (https://github.com/torys877)
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Crypto\CryptoPayment\Setup\Patch\Data;

use Crypto\CryptoCurrency\Api\BlockchainRepositoryInterface;
use Crypto\CryptoCurrency\Api\CryptoCurrencyRepositoryInterface;
use Crypto\CryptoCurrency\Api\CurrencyAddressRepositoryInterface;
use Crypto\CryptoCurrency\Api\Data\BlockchainInterface;

use Crypto\CryptoCurrency\Api\Data\BlockchainInterfaceFactory;
use Crypto\CryptoCurrency\Api\Data\CryptoCurrencyInterface;
use Crypto\CryptoCurrency\Api\Data\CryptoCurrencyInterfaceFactory;

use Crypto\CryptoCurrency\Api\Data\CurrencyAddressInterface;
use Crypto\CryptoCurrency\Api\Data\CurrencyAddressInterfaceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddCryptoCurrencies implements DataPatchInterface
{
    private string $installedCurrenciesPath = 'system/currency/installed';
    private ModuleDataSetupInterface $moduleDataSetup;
    private WriterInterface $configWriter;
    private ScopeConfigInterface $config;

    private BlockchainRepositoryInterface $blockchainRepository;
    private CryptoCurrencyRepositoryInterface $cryptoCurrencyRepository;
    private CurrencyAddressRepositoryInterface $currencyAddressRepository;
    private BlockchainInterfaceFactory $blockchainInterfaceFactory;
    private CryptoCurrencyInterfaceFactory $cryptoCurrencyInterfaceFactory;
    private CurrencyAddressInterfaceFactory $currencyAddressInterfaceFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter,
        ScopeConfigInterface $config,
        BlockchainRepositoryInterface $blockchainRepository,
        CryptoCurrencyRepositoryInterface $cryptoCurrencyRepository,
        CurrencyAddressRepositoryInterface $currencyAddressRepository,
        BlockchainInterfaceFactory $blockchainInterfaceFactory,
        CryptoCurrencyInterfaceFactory $cryptoCurrencyInterfaceFactory,
        CurrencyAddressInterfaceFactory $currencyAddressInterfaceFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
        $this->config = $config;

        $this->blockchainRepository = $blockchainRepository;
        $this->cryptoCurrencyRepository = $cryptoCurrencyRepository;
        $this->currencyAddressRepository = $currencyAddressRepository;
        $this->blockchainInterfaceFactory = $blockchainInterfaceFactory;
        $this->cryptoCurrencyInterfaceFactory = $cryptoCurrencyInterfaceFactory;
        $this->currencyAddressInterfaceFactory = $currencyAddressInterfaceFactory;
    }

    public function apply(): void
    {
        $format = '{{amount}} {{symbol}}';
        $formatHtml = '<span class=""currency""><span class=""currency-amount"">{{amount}}</span> <span class=""currency-symbol"">{{symbol_html}}</span></span>';

        // CREATE ETHEREUM CURRENCY
        /** @var CryptoCurrencyInterface $ethCurrency */
        $ethCurrency = $this->cryptoCurrencyInterfaceFactory->create();
        $ethCurrency
            ->setIsCrypto(true)
            ->setIsToken(false)
            ->setCryptoCode('ETH')
            ->setCode('ETH')
            ->setStatus(1)
            ->setPrecision(6)
            ->setName('Ether')
            ->setPlural('Ethers')
            ->setFormat($format)
            ->setFormatHtml($formatHtml)
            ->setSymbol('ETH');

        $ethCurrency = $this->cryptoCurrencyRepository->save($ethCurrency);

        // CREATE POLYGON CURRENCY
        /** @var CryptoCurrencyInterface $polygonCurrency */
        $polygonCurrency = $this->cryptoCurrencyInterfaceFactory->create();
        $polygonCurrency
            ->setIsCrypto(true)
            ->setIsToken(false)
            ->setCryptoCode('MATIC')
            ->setCode('MAT')
            ->setStatus(1)
            ->setPrecision(2)
            ->setName('Matic')
            ->setPlural('Matics')
            ->setFormat($format)
            ->setFormatHtml($formatHtml)
            ->setSymbol('MAT');

        $polygonCurrency = $this->cryptoCurrencyRepository->save($polygonCurrency);

        // CREATE USDT CURRENCY
        /** @var CryptoCurrencyInterface $usdtCurrency */
        $usdtCurrency = $this->cryptoCurrencyInterfaceFactory->create();
        $usdtCurrency
            ->setIsCrypto(true)
            ->setIsToken(true)
            ->setCryptoCode('USDT')
            ->setCode('UST')
            ->setStatus(1)
            ->setPrecision(2)
            ->setName('USDT')
            ->setPlural('USDTs')
            ->setFormat($format)
            ->setFormatHtml($formatHtml)
            ->setSymbol('USDT');

        $usdtCurrency = $this->cryptoCurrencyRepository->save($usdtCurrency);

        // CREATE USDC CURRENCY
        /** @var CryptoCurrencyInterface $usdtCurrency */
        $usdcCurrency = $this->cryptoCurrencyInterfaceFactory->create();
        $usdcCurrency
            ->setIsCrypto(true)
            ->setIsToken(true)
            ->setCryptoCode('USDC')
            ->setCode('USC')
            ->setStatus(1)
            ->setPrecision(2)
            ->setName('USDC')
            ->setPlural('USDCs')
            ->setFormat($format)
            ->setFormatHtml($formatHtml)
            ->setSymbol('USDC');

        $usdcCurrency = $this->cryptoCurrencyRepository->save($usdcCurrency);

        //CREATE ETHEREUM BLOCKCHAIN
        /** @var BlockchainInterface $ethereumBlockchain */
        $ethereumBlockchain = $this->blockchainInterfaceFactory->create();
        $ethereumBlockchain
            ->setName('Ethereum')
            ->setCode('ethereum')
            ->setNetworkId(1)
            ->setBlockExplorerUrl('https://etherscan.io/')
            ->setIsBlockExplorerCheck(false);

        $ethereumBlockchain = $this->blockchainRepository->save($ethereumBlockchain);

        //CREATE POLYGON BLOCKCHAIN
        /** @var BlockchainInterface $polygonBlockchain */
        $polygonBlockchain = $this->blockchainInterfaceFactory->create();
        $polygonBlockchain
            ->setName('Polygon')
            ->setCode('polygon')
            ->setNetworkId(137) // chain id
            ->setBlockExplorerUrl('https://polygonscan.com/')
            ->setIsBlockExplorerCheck(false);

        $polygonBlockchain = $this->blockchainRepository->save($polygonBlockchain);

        //create currency addresses
        /** @var CurrencyAddressInterface $address */
        // ETHEREUM, ETHER
        $address = $this->currencyAddressInterfaceFactory->create();
        $address->setCurrencyId((int) $ethCurrency->getId())
            ->setBlockchainNetworkId((int) $ethereumBlockchain->getEntityId());

        $this->currencyAddressRepository->save($address);

        // ETHEREUM, USDT
        $address = $this->currencyAddressInterfaceFactory->create();
        $address->setCurrencyId((int)$usdtCurrency->getId())
            ->setBlockchainNetworkId((int)$ethereumBlockchain->getEntityId());

        $this->currencyAddressRepository->save($address);

        // ETHEREUM, USDC
        $address = $this->currencyAddressInterfaceFactory->create();
        $address->setCurrencyId((int)$usdcCurrency->getId())
            ->setBlockchainNetworkId((int)$ethereumBlockchain->getEntityId());

        $this->currencyAddressRepository->save($address);

        // POLYGON, MATIC
        $address = $this->currencyAddressInterfaceFactory->create(); //polygon
        $address->setCurrencyId((int)$polygonCurrency->getId())
            ->setBlockchainNetworkId((int)$polygonBlockchain->getEntityId());

        $this->currencyAddressRepository->save($address);
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
