<?php
/*
 * Copyright © Ihor Oleksiienko (https://github.com/torys877)
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Crypto\CryptoPayment\ViewModel;

use Crypto\CryptoCurrency\Api\CurrencyAddressRepositoryInterface;
use Crypto\CryptoCurrency\Api\Data\BlockchainInterface;
use Crypto\CryptoCurrency\Api\Data\BlockchainInterfaceFactory;
use Crypto\CryptoCurrency\Api\Data\CryptoCurrencyInterface;
use Crypto\CryptoCurrency\Api\Data\CurrencyAddressInterface;
use Crypto\CryptoCurrency\Api\Data\CurrencyAddressInterfaceFactory;
use Crypto\CryptoCurrency\Model\CryptoCurrencyRepository;
use Crypto\CryptoCurrency\Model\ResourceModel\Blockchain\Collection;
use Crypto\CryptoCurrency\Model\ResourceModel\CurrencyAddress\Collection as CurrencyAddressCollection;
use Crypto\CryptoPayment\Helper\ConfigReader;
use Magento\Checkout\Model\Session;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order;

class EtherProcessing implements ArgumentInterface
{
    public const REQUEST_INTERVAL_SECONDS = 5000;
    private CurrentCustomer $currentCustomer;
    private TimezoneInterface $localeDate;
    private ScopeConfigInterface $configManager;
    private ConfigReader $configReader;
    private Session $checkoutSession;
    private ?Order $order = null;
    private UrlInterface $urlBuilder;
    private Registry $registry;
    private bool $redirectToAccount = false;
    private CryptoCurrencyRepository $cryptoCurrencyRepository;
    private ?Collection $blockchainCollection = null;
    private ?CurrencyAddressCollection $currencyAddressCollection = null;
    private ?CryptoCurrencyInterface $cryptoCurrency = null;
    private ?CurrencyAddressInterface $currencyAddress = null;
    private ?CurrencyAddressInterfaceFactory $currencyAddressFactory = null;
    private string $currencyCode = '';
    private BlockchainInterfaceFactory $blockchainFactory;
    private CurrencyAddressRepositoryInterface $currencyAddressRepository;

    public function __construct(
        CurrentCustomer $currentCustomer,
        TimezoneInterface $localeDate,
        ScopeConfigInterface $configManager,
        ConfigReader $configReader,
        Session $checkoutSession,
        UrlInterface $urlBuilder,
        Registry $registry,
        CryptoCurrencyRepository $cryptoCurrencyRepository,
        CurrencyAddressInterfaceFactory $currencyAddressFactory,
        BlockchainInterfaceFactory $blockchainFactory,
        CurrencyAddressRepositoryInterface $currencyAddressRepository
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->localeDate = $localeDate;
        $this->configManager = $configManager;
        $this->configReader = $configReader;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->registry = $registry;
        $this->cryptoCurrencyRepository = $cryptoCurrencyRepository;
        $this->currencyAddressFactory = $currencyAddressFactory;
        $this->blockchainFactory = $blockchainFactory;
        $this->currencyAddressRepository = $currencyAddressRepository;
    }

    public function getNetworkVersions(): ?string
    {
        if ($this->isSingleBlockchain()) {
            return (string) $this->blockchainCollection->getFirstItem()->getNetworkId();
        }

        $networks = [];

        foreach ($this->blockchainCollection->getItems() as $blockchain) {
            /** @var BlockchainInterface $blockchain */
            $networks[] = $blockchain->getNetworkId();
        }

        return implode(',', $networks);
    }

    public function isSingleBlockchain(?string $currencyCode = null): bool
    {
        if ($this->getBlockchainCollection($this->getCurrencyCode($currencyCode))->count() == 1) {
            return true;
        }

        return false;
    }

    public function getBlockchain(?string $currencyCode = null): BlockchainInterface
    {
        if (!$this->isSingleBlockchain()) {
            return $this->blockchainFactory->create();
        }

        return $this->getBlockchainCollection($this->getCurrencyCode($currencyCode))->getFirstItem();
    }

    public function isToken(?string $currencyCode = null): bool
    {
        $currency = $this->getCurrency($this->getCurrencyCode($currencyCode));

        if ($currency->getIsToken()) {
            return true;
        }

        return false;
    }

    public function getBlockchains(?string $currencyCode = null): Collection
    {
        return $this->getBlockchainCollection($this->getCurrencyCode($currencyCode));
    }

    public function getCurrencyAbi(?string $currencyCode = null): string
    {
        /** @var CryptoCurrencyInterface $currency */
        $currency = $this->getCurrency($this->getCurrencyCode($currencyCode));

        if ($currency->getAbi()) {
            return $currency->getAbi();
        }

        return '';
    }

    public function getCurrencyAddress(?string $currencyCode = null): CurrencyAddressInterface
    {
        if ($this->currencyAddress) {
            return $this->currencyAddress;
        }

        $this->currencyAddress = $this->currencyAddressFactory->create();

        if ($this->isSingleBlockchain($currencyCode)) {
            $blockchain = $this->getBlockchain($this->getCurrencyCode($currencyCode));
            $currency = $this->getCurrency($this->getCurrencyCode($currencyCode));
            $this->currencyAddress = $this->cryptoCurrencyRepository->getCurrencyAddress((int)$currency->getId(), (int)$blockchain->getEntityId());
        }

        return $this->currencyAddress;
    }

    public function getTokenAddresses(?string $currencyCode = null): string
    {
        $currencyAddressCollection = $this->cryptoCurrencyRepository->getAllCurrencyAddressesByCode($this->getCurrencyCode($currencyCode));
        $currencyAddresses = [];

        if ($currencyAddressCollection->count()) {
            foreach ($currencyAddressCollection as $addressItem) {
                /** @var CurrencyAddressInterface $addressItem */
                $currencyAddresses[$addressItem->getBlockchainNetworkId()] = [
                    CurrencyAddressInterface::MERCHANT_ADDRESS => $addressItem->getMerchantAddress(),
                    CurrencyAddressInterface::TOKEN_ADDRESS => $addressItem->getTokenAddress(),
                    CurrencyAddressInterface::BLOCKCHAIN_NETWORK_ID => $addressItem->getBlockchainNetworkId()
                ];
            }
        }

        return json_encode($currencyAddresses);
    }

    public function getTokenAddressByBlockchain(string $blockchainCode, ?string $currencyCode = null): string
    {
        return $this->cryptoCurrencyRepository
            ->getCurrencyAddressByBlockchainCode($blockchainCode, $this->getCurrencyCode($currencyCode))
            ->getTokenAddress();
    }

    public function getMerchantAddressByBlockchain(string $blockchainCode, ?string $currencyCode = null): string
    {
        return $this->cryptoCurrencyRepository
            ->getCurrencyAddressByBlockchainCode($blockchainCode, $this->getCurrencyCode($currencyCode))
            ->getMerchantAddress();
    }

    public function getTokenAddress(?string $currencyCode = null): string
    {
        if (!$this->isSingleBlockchain($currencyCode)) {
            return '';
        }

        return (string) $this->getCurrencyAddress($this->getCurrencyCode($currencyCode))->getTokenAddress();
    }

    public function getBlockchainCollection(?string $currencyCode = null)
    {
        if ($this->blockchainCollection) {
            return $this->blockchainCollection;
        }

        $this->blockchainCollection = $this->cryptoCurrencyRepository
            ->getAllCurrencyBlockchainsByCode($this->getCurrencyCode($currencyCode));

        return $this->blockchainCollection;
    }

    public function getCurrencyCode(?string $currencyCode = ''): string
    {
        if ($this->currencyCode) {
            return $this->currencyCode;
        }

        if ($currencyCode) {
            $this->currencyCode = $currencyCode;

            return $this->currencyCode;
        }

        $this->currencyCode = $this->getOrder()->getOrderCurrencyCode();

        return $this->currencyCode;
    }

    public function getCurrency(?string $currencyCode = null): CryptoCurrencyInterface
    {
        if ($this->cryptoCurrency) {
            return $this->cryptoCurrency;
        }

        $this->cryptoCurrency = $this->cryptoCurrencyRepository->getByCode($this->getCurrencyCode($currencyCode));

        return $this->cryptoCurrency;
    }

    public function getOrder()
    {
        if (!$this->order) {
            $this->order = $this->checkoutSession->getLastRealOrder();
            if (!$this->order) {
                $this->registry->registry('current_order');
            }
        }

        return $this->order;
    }

    public function setOrder(?Order $order = null): self
    {
        $this->order = $order;

        return $this;
    }

    public function setRedirectToAccount(bool $redirect): self
    {
        $this->redirectToAccount = $redirect;

        return $this;
    }

    public function getRedirectToAccount(): bool
    {
        return $this->redirectToAccount;
    }

    public function getMerchantAddress(?string $currencyCode = ''): ?string
    {
        if (!$this->isSingleBlockchain($currencyCode)) {
            return '';
        }

        return $this->getCurrencyAddress($this->getCurrencyCode($currencyCode))->getMerchantAddress();
    }

    public function getOrderIncrement(): ?string
    {
        return (string) $this->getOrder()->getIncrementId();
    }

    public function getOrderHash(): ?string
    {
        return (string) $this->getOrder()->getOrderHash();
    }

    public function getOrderAmount(): float
    {
        return (float) $this->getOrder()->getGrandTotal();
    }

    public function getTxCheckAndConfirmUrl(): string
    {
        return $this->urlBuilder->getUrl('cryptopayment/payment/txCheckAndConfirm');
    }

    public function getAddTxUrl(): string
    {
        return $this->urlBuilder->getUrl('cryptopayment/payment/txAdd');
    }

    public function getSuccessUrl(): string
    {
        if ($this->getRedirectToAccount()) {
            return $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $this->getOrder()->getId()]);
        }

        return $this->urlBuilder->getUrl('checkout/onepage/success');
    }

    public function getRequestIntervalSeconds(): int
    {
        return self::REQUEST_INTERVAL_SECONDS;
    }
}
