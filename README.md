# Crypto Payment Magento 2 Extension

Extension allows to receive crypto payments in USDT/USDC and native crypto currencies with Metamask wallet.

## Table of contents

* [Features](#features)
* [Installation](#installation)
* [Configuration](#configuration)
* [Author](#author)
* [License](#license)

## Features

- Accept Payments in EVM compatible blockchains using Metamask
- Accept Payments in stablecoins that implement `ERC20` standard like USDT/USDC etc.
- Check transaction status after payment
- Check transaction status in explorer - etherscan 

#### TODO:
- check transaction status in custom explorer (configured in blockchain networks) 

## Installation

Install module:

`composer require cryptom2/magento-crypto-payment:v1.0.0`

And run

```php
php bin/magento setup:upgrade
```

## Configuration

### Add New Currency And Blockchains

Follow Instructions Corresponding Modules:

### Enable Payment Method
- Go to `Stores->Configuration->Sales->Payment Methods`
- Go to `Crypto Payment`
- Set `Enable` => `Yes`
- Set `Title` as payment method name

![Magento 2 Crypto Payment](https://raw.githubusercontent.com/torys877/magento-crypto-payment/main/docs/Selection_006.png)

## Author

### Ihor Oleksiienko

* [Github](https://github.com/torys877)
* [Linkedin](https://www.linkedin.com/in/igor-alekseyenko-77613726/)
* [Facebook](https://www.facebook.com/torysua/)

## License

Extension is licensed under the MIT License - see the LICENSE file for details
