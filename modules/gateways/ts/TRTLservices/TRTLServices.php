<?php

namespace TRTLServices;

use TRTLServices\Http\Response;
use TRTLServices\Http\Client;

class TRTLServices extends Client
{
    // Create Address
    public function createAddress():Response
    {
        $params = [];

        return $this->_post('address', $params);
    }


    // Get Address
    public function getAddress(string $address):Response
    {
        return $this->_get('address/' . $address);
    }


    // Delete Address
    public function deleteAddress(string $address):Response
    {
        return $this->_delete('address/' . $address);
    }


    // Get Addresses
    public function getAddresses():Response
    {
        return $this->_get('address/all');
    }


    // Scan Address
    public function scanAddress(string $address, int $blockIndex):Response
    {
        return $this->_get('address/scan/' . $address  . '/' .  $blockIndex);
    }


    // Get Address Keys
    public function getAddressKeys(string $address):Response
    {
        return $this->_get('address/keys/' . $address);
    }


    // Integrate Address
    public function integrateAddress(string $address):Response
    {
        $params = [
            'address' => $address
        ];

        return $this->_post('address/integrate', $params);
    }


    // Get Integrated Addresses
    public function getIntegratedAddresses(string $address):Response
    {
        return $this->_get('address/integrate/' . $address);
    }

    public function getFee(float $amount):Response
    {
        return $this->_get('transfer/fee/' . $amount);
    }

    // Create Transfer
    public function createTransfer(
        string $from,
        string $to,
        float $amount,
        float $fee,
        string $paymentId = null,
        string $extra = null
    ):Response {
        $params = [
            'to'        => $to,
            'from'      => $from,
            'amount'    => $amount,
            'fee'       => $fee,
        ];

        if (!is_null($extra)) $params['extra'] = $extra;
        if (!is_null($paymentId)) $params['paymentId'] = $paymentId;

        return $this->_post('transfer', $params);
    }

    // Get Transfer
    public function getTransfer(string $transactionHash):Response {
        return $this->_get('transfer/' . $transactionHash);
    }

    //Get Wallet
    public function getWallet():Response
    {
        return $this->_get('wallet');
    }

    //Get Status
    public function getStatus():Response
    {
        return $this->_get('status');
    }
}