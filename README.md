# TRTL Service WHMCS Merchant Gateway

Work In Progress

**DON'T USE IN PRODUCTION**


# Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Intialization](#intialization)

# Requirements

- PHP 7.1+
- WHCMS
- TRTL Services Access Token


# Installation

* Put the plugin in the correct directory: You will need to copy `monero.php` and the folder named `monero` from this repo/unzipped release into the WHMCS Payment Gateways directory. This can be found at `whmcspath/modules/gateways/`

* Activate the plugin from the WHMCS admin panel: Once you login to the admin panel in WHMCS, click on "Setup -> Payments -> Payment Gateways". Click on "All Payment Gateways". Then click on the "Monero" gateway to activate it.

* Enter a Module Secret Key.  This can be any random text and is used to verify payments.  

* Enter the values for Wallet RPC Host, Wallet RPC Port, Username, and Password (these are from monero-wallet-rpc below).  Optionally enter a percentage discount for all invoices paid via Monero.

* Optionally install the addon module to disable WHMCS fraud checking when using Monero. You will need to copy the folder `addons/moneroenable/` from this repo/unzipped release into the WHMCS Addons directory. This can be found at `whmcspath/addons/`.  

* Activate the Monero Enabler addon from the WHMCS admin panel: Click on "Setup -> Addon Modules". Find "Monero Enabler" and click on "Activate". Click "Configure" and choose the Monero Payment Gateway in the drop down list. Check the box for "Enable checking for payment method by module" and click "Save Changes".


# Intialization


# Documentation


# Credits
* This project is forked from [monerowhmcs](https://github.com/monero-integrations/monerowhmcs)