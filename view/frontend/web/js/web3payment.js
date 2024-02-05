/*
 * Copyright © Ihor Oleksiienko (https://github.com/torys877)
 * See LICENSE for license details.
 */
define([
    'ko',
    'uiComponent',
    'jquery',
    'web3'
], function (ko, Component, $, web3) {
    'use strict';

    return Component.extend({
        blockchainNetworkId: ko.observable(null),
        defaults: {
            merchantAddress: null,
            blockchainNetworkId: ko.observable(null),
            currentBlockchainNetworkId: null,
            blockchainName: null,
            tokenAddress: null,
            tokenAddresses: null,
            isToken: null,
            currencyTokenAbi: null,
            isSingleBlockchain: null,
            orderAmount: null,
            orderHash: null,
            addTxUrl: null,
            txCheckAndConfirmUrl: null,
            successUrl: null,
            requestIntervalSeconds: null,
            web3client: null,
            givenProvider: null,
            accounts: [],
            contract: null
        },
        /** connect provider **/
         initialize: async function () {
            // this.blockchainNetworkId(33);
            this._super();
            this.tokenAddresses = JSON.parse(this.tokenAddresses);
            //CHECK
            this.givenProvider = web3.givenProvider;

            //check if metamask is installed
            if (!this.checkMetamask()) {
                return;
            }

            //create web3 client with metamask provider
            this.web3client = new web3(web3.givenProvider);
            await this.web3client.eth.getChainId().then(function(networkId) {
                //set current customer metamask network ID
                this.currentBlockchainNetworkId = networkId;
            }.bind(this));

            this.checkBlockchainNetwork();

            if (this.isToken && this.isSingleBlockchain) {
                this.loadContract();
            }
        },
        checkMetamask: function() {
            if (!this.givenProvider || typeof this.givenProvider == 'undefined') {
                this.showMessage("Metamask is not installed. Or disabled");
                return false;
            }

            return true;
        },
        checkBlockchainNetwork: function() {
            if (this.isSingleBlockchain == false && $("#blockchains").length) {
                this.changeBlockchainOption();

                if ($("#blockchains option[value='" + this.currentBlockchainNetworkId + "']").length > 0) {
                    this.blockchainNetworkId = this.currentBlockchainNetworkId;
                    $("#blockchains").val(this.currentBlockchainNetworkId);
                    $('#connect_wallet_button').show();
                        this.tokenAddress = $("#blockchains option[value='" + this.currentBlockchainNetworkId + "']").data("token-address");
                        this.merchantAddress = $("#blockchains option[value='" + this.currentBlockchainNetworkId + "']").data("merchant-address");
                        this.createWeb3();
                        this.loadContract();

                    return true;
                }

                return false;
            } else if (this.isSingleBlockchain == true && this.blockchainNetworkId) {
                var chainIdValHex = web3.utils.toHex(this.blockchainNetworkId);
                var currentChainIdHex = web3.utils.toHex(this.currentBlockchainNetworkId);

                if (chainIdValHex != currentChainIdHex) {
                    this.showMessage("Please, change metamask network to " + this.blockchainName);
                    this.changeNetwork(this.blockchainNetworkId);
                    return false;
                }
            }

            return true;
        },
        changeBlockchainOption: function() {
             var self = this;
            $(document).ready(function() {
                $("#blockchains").change(function(e) {
                    $('#connect_wallet_button').hide();
                    var chainIdVal = $(this).find(":checked").val();

                    if (chainIdVal && window.ethereum) {
                        var chainIdValHex = web3.utils.toHex(chainIdVal);
                        var currentChainIdHex = web3.utils.toHex(self.currentBlockchainNetworkId);

                        if (currentChainIdHex != chainIdValHex) {
                            self.changeNetwork(chainIdVal);
                        } else {
                            $('#connect_wallet_button').show();
                        }
                    }
                }.bind(web3));
            });
        },
        changeNetwork: function(chainIdVal) {
            var chainIdValHex = web3.utils.toHex(chainIdVal);
            var currentChainIdHex = web3.utils.toHex(this.currentBlockchainNetworkId);

            window.ethereum.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: chainIdValHex }],
            })
                .then(function(changedChainIdRaw) {
                    var changedChainIdHex = web3.utils.toHex(changedChainIdRaw);
                    this.currentBlockchainNetworkId = changedChainIdRaw;

                    if (changedChainIdHex == chainIdValHex) {
                        this.showMessage('Network Changed.');
                        $('#connect_wallet_button').show();

                        if ($("#blockchains").length) {
                            this.tokenAddress = $(this).find(":checked").data("token-address");
                            this.merchantAddress = $(this).find(":checked").data("merchant-address");
                            this.createWeb3();
                            this.loadContract();
                        }

                    }
                }.bind(this, chainIdValHex));
        },
        showMessage: function(message) {
            $('.message').html(message);
            $('.message').show();
        },
        createWeb3: function() {
            this.givenProvider = web3.givenProvider;
            if (
                this.givenProvider &&
                typeof this.givenProvider != 'undefined'
            ) {
                this.web3client = new web3(web3.givenProvider);

                return true;
            }
        },
        /** connect metamask wallet to website **/
        connectWallet: function() {
            if (!this.isWeb3()) {
                return;
            }
            let self = this;
            this.web3client.eth.requestAccounts().then(
                function(accs) {
                    self.accounts = accs;
                    if (accs.length) {
                        $('#connect_wallet_button').hide();
                        $('#cryptopay_button').show();
                    }
                }.bind(this)
            );
        },
        loadContract: function() {
            var self = this;
            this.contract = new this.web3client.eth
                .Contract(
                    JSON.parse(self.currencyTokenAbi), //contract ABI
                    self.tokenAddress //contract address
                );
            this.showMessage('Contract is loaded');
        },
        pay: function() {
            if (this.isToken == true) {
                this.sendTokenCryptoTransaction();
            } else {
                this.sendRawCryptoTransaction();
            }
        },
        sendTokenCryptoTransaction: function () {
            // Use BigNumber
            let self = this;
            let ten = this.web3client.utils.toBN(10);
            let decimals = this.web3client.utils.toBN(18);
            let amount = this.web3client.utils.toBN(this.orderAmount);
            let value = amount.pow(decimals);

            this.contract.methods.decimals().call(function(error, d) {
                console.log("decimals:",error,d);

                //calculate actual tokens amounts based on decimals in token
                let tokens=web3.utils.toBN("0x"+(self.orderAmount*10**d).toString(16)).toString();

                //call mint function
                self.contract.methods
                    .transfer(self.merchantAddress, tokens)
                    .send(
                        {
                            from: self.getCurrentAccount()
                        }
                    )
                    .then(function(responseObj) {
                        self.showMessage('Transaction is sent. Txh = ' + responseObj.transactionHash);
                        console.log(responseObj);
                        self.addTransaction(self, responseObj.transactionHash, responseObj);
                    })
                    .catch(function(errObj) {
                        self.showMessage('Transaction is declined by client. ' + errObj.code + ': ' + errObj.message);
                    });

                // mint(address,tokens).send({from:address},function(error,transactionHash){
                //     //show result
                //     console.log(error,transactionHash);
                //     callback(transactionHash);
                // });
            });
        },
//         /** send metamask transaction **/
        sendRawCryptoTransaction: function() {
            if (!this.isWeb3()) {
                return;
            }
            let self = this;

            this.web3client.eth.sendTransaction({
                from: this.getCurrentAccount(),
                to: this.merchantAddress,
                value: web3.utils.toWei(this.orderAmount, "ether"),
            }, function(err, transactionHash) {
                if (err) {
                    self.showMessage(err.code + ' ' + err.message);
                } else {
                    //add transaction to magento with status isClosed = 0
                    self.addTransaction(self, transactionHash);
                }
            });
        },
        /** add transaction to magento with status isClosed = 0 **/
        addTransaction: function(currentComponentObject, transactionHash, transactionData) {
            let self = currentComponentObject;
            $.ajax({
                type: 'POST',
                url: self.addTxUrl,
                showLoader: true,
                data: {
                    "txhash": transactionHash,
                    "txData": transactionData,
                    "order_hash": self.orderHash
                }
            })
                .done(function(addRresult) {
                    //register transaction, not captured
                    self.showMessage(addRresult.message);
                    if (addRresult.error) {
                        return;
                    }
                    self.checkTransactionStatus(self, transactionHash)
                })
                .fail(function(result){
                    self.showMessage('Sorry, there was a problem saving the settings.');
                });
        },
        /** check transaction status through web3 metamask connection **/
        checkTransactionStatus: function(currentComponentObject, transactionHash) {
            let self = currentComponentObject;
            //check registered transaction and capture if it is processed in blockchain
            var intervalVar = setInterval(function () {
                self.web3client.eth.getTransactionReceipt(transactionHash, function(error, obj) {
                    if (error) {
                        self.showMessage(err.code + ' ' + error.message);
                    }
                    if (!obj) {
                        return;
                    }
                    if (obj.status == true) {
                        //confirm transaction in magento
                        self.checkAndConfirmTransaction(self, transactionHash, intervalVar)
                    }
                })
            }, self.requestIntervalSeconds);
        },
        /** check transaction on backend(if enabled), confirm transaction, create invoice for order **/
        checkAndConfirmTransaction: function(currentComponentObject, transactionHash, intervalVar) {
            let self = currentComponentObject;
            $.ajax({
                url: self.txCheckAndConfirmUrl,
                type: 'post',
                dataType: 'json',
                data: {
                    "txhash": transactionHash,
                    "order_hash": self.orderHash
                },
                success: function(checkResult) {
                    self.showMessage(checkResult.message);
                    if (!checkResult.error) {
                        clearInterval(intervalVar);
                        window.location.replace(self.successUrl);
                    }
                }
            });
        },
        /** check is provider exist **/
        isWeb3: function() {
            if (!this.web3client) {
                if (this.createWeb3()) {
                    return true;
                }

                return false;
            }

            return true;
        },
//
        /** check is wallet connected to website **/
        isWalletConnected: function() {
            if (!this.isWeb3()) {
                return;
            }
            var result = this.accounts.length ? true:false;
            return result;
        },
//         /** get all connected accounts **/
//         getAccounts: function() {
//             if (!this.isWeb3()) {
//                 return;
//             }
//             var self = this;
//             this.web3client.eth.requestAccounts().then(
//                 function(result) {
//                     self.accounts = result
//                 }
//             );
//         },
        /** get current account **/
        getCurrentAccount: function() {
            if (!this.isWeb3()) {
                return;
            }
            if (this.isWalletConnected()) {
                return this.accounts[0];
            }

            return false;
        }
    });
});
