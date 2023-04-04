# Mollie Payment Gateway for Merx

This plugin is [Mollie](https://www.mollie.com) payment gateway for [Kirby Merx plugin](https://github.com/wagnerwagner/merx).

## Installation

1. Download the latest release
2. Unzip downloaded file
3. Copy/paste unzipped folder in your `/site/plugins` folder

## Setup from Panel

### Step 1: Get API key

Get API key from [Mollie dashboard](https://www.mollie.com/dashboard/developers/api-keys).

### Step 2: Set API key from config

Set `apiKey` option from config.php like following usage:

**/site/config/config.php**

```php
<?php

return [
    'owebstudio.merx-mollie.apiKey' => 'YOUR_API_KEY'
];
```

### Step 3: Enable payment methods

You need to enable at least one payment method from [Mollie Dashboard](https://www.mollie.com/dashboard/settings/profiles).

### Step 4: Add payment method

Enter the Kirby panel with Merx installed. Go to the payment methods section from the settings tab and add a new one. Make sure you type `mollie``` in the value field.

### Step 5: Test

:tada: Done! Now you're ready for first order!

## Manual checkout

### Initialize payment

```php
$redirect = $merx->initializePayment([
    'paymentMethod' => 'mollie',
    'method' => ['creditcard', 'paypal'],
]);
go($redirect);
```

### Complete payment

```php
$merx->completePayment():
```

## Options

| Option           | Type                | Default | Description                                                                                                                          |
|:-----------------|:--------------------|:--------|:-------------------------------------------------------------------------------------------------------------------------------------|
| apiKey           | string              | null    | API key for Mollie (required)                                                                                                        |
| webhooks         | boolean             | false   | Activates Mollie webhooks to process real-time status updates                                                                        |
| method           | string &#124; array | []      | Choose a specific payment method and your customer will skip the selection screen and is sent directly to the chosen payment method. |
| redirectUrl      | string              | null    | Sets custom redirect url after payment.                                                                                              |
| orderDescription | string              | null    | Sets the custom description sent to Mollie.                                                                                          |
| testmode         | boolean             | false   | Set to true to make payments a test payment.                                                                                         |

All the values can be updated in the `config.php` file and prefixed with `owebstudio.merx-mollie.`.

### Possible `method` option values

Possible values: `applepay`, `bancontact`, `banktransfer`, `belfius`, `creditcard`, `directdebit`, `eps`, `giftcard`, `giropay`, `ideal`, `kbc`, `mybank`, `paypal`, `paysafecard`, `przelewy24`, `sofort`

## Sample options

```php
<?php

// /site/config/config.php
return [
    'owebstudio.merx-mollie' => [
        'apiKey'            => 'YOUR_API_KEY',
        'redirectUrl'       => 'https://yourshopdomain.com/return'
        'orderDescription'  => function ($virtualOrderPage) {
            retturn 'Order Id # ' . $virtualOrderPage->uid();
        }
    ]
];
```
