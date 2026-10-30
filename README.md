# Mollie Payment Gateway for Merx

This plugin is [Mollie](https://www.mollie.com) payment gateway for [Kirby Merx plugin](https://github.com/wagnerwagner/merx).

## Requirements

- Kirby 3.9, 4 or 5
- Merx 1.10
- PHP 8.1 or newer

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

Enter the Kirby panel with Merx installed. Go to the payment methods section from the settings tab and add a new one. Make sure you type `mollie` in the value field.

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
$merx->completePayment();
```

## Payment statuses

When the buyer comes back from Mollie, the plugin checks the payment status:

| Status                                           | Result                                                                                                                |
|:-------------------------------------------------|:----------------------------------------------------------------------------------------------------------------------|
| `paid`                                           | The order is created with `paymentComplete: true` and `paidDate`.                                                     |
| `pending`, or `open` for bank transfers          | The order is created with `paymentComplete: false`. The webhook completes it once Mollie reports the payment as paid. |
| `open` (buyer left the payment page), `canceled` | No order, the buyer gets the `merx.paymentCanceled` error. The cart is kept, so the buyer can try again.              |
| `failed`, `expired`, `authorized`                | No order, the buyer gets the `merx.completePayment` error.                                                            |

## Webhooks

Mollie calls `/shop-api/mollie-webhook` on your site whenever the status of a payment changes. The plugin then fetches the payment from Mollie and:

- completes pending orders once the payment is paid;
- answers with an error when the order doesn't exist yet or the Mollie API can't be reached, so Mollie tries again later;
- logs failed, expired and canceled payments of pending orders, refunds and chargebacks with `error_log()`.

Webhooks are enabled by default. Mollie doesn't accept webhook URLs it can't reach, so the plugin doesn't send one on `localhost`, `*.localhost`, `*.local`, `*.test` and private IP addresses. In production, make sure Mollie can reach the URL (no basic auth or maintenance page in front of it).

To turn webhooks off:

```php
<?php

return [
    'owebstudio.merx-mollie.webhooks' => false
];
```

Without webhooks, pending orders stay at `paymentComplete: false`, and you have to check them in the Mollie dashboard.

## Options

| Option            | Type                | Default | Description                                                                                                                          |
|:------------------|:--------------------|:--------|:-------------------------------------------------------------------------------------------------------------------------------------|
| apiKey            | string              | null    | API key for Mollie (required)                                                                                                        |
| webhooks          | boolean             | true    | Activates Mollie webhooks to process real-time status updates, see [Webhooks](#webhooks)                                             |
| method            | string &#124; array | []      | Choose a specific payment method and your customer will skip the selection screen and is sent directly to the chosen payment method. |
| testmode          | boolean             | false   | Set to true to make payments a test payment. Only read from the config, never from checkout data.                                    |
| initializePayment | Closure             | null    | Sets custom initialize payment before payment. Be careful! This is advanced configuration.                                           |
| completePayment   | Closure             | null    | Sets the custom complete method after payment. It replaces the status check above. Be careful! This is advanced configuration.       |

All the values can be updated in the `config.php` file and prefixed with `owebstudio.merx-mollie.`.

### Possible `method` option values

Possible values: `applepay`, `bancontact`, `banktransfer`, `belfius`, `creditcard`, `directdebit`, `eps`, `giftcard`, `giropay`, `ideal`, `kbc`, `mybank`, `paypal`, `paysafecard`, `przelewy24`, `sofort`

## Sample options

A custom `completePayment` replaces the plugin's own status check, so make sure it only completes paid payments. Change the order with `Oweb\MollieGateway::updateOrderPage()`, because Kirby 5 doesn't keep changes made with `content()->update()`.

```php
<?php

use Kirby\Exception\Exception;
use Oweb\MollieGateway;

// /site/config/config.php
return [
    'owebstudio.merx-mollie' => [
        'apiKey'             => 'YOUR_API_KEY',
        'completePayment'    => function ($virtualOrderPage, $data, $payment) {
            if ($payment->status !== 'paid') {
                throw new Exception([
                    'httpCode' => 400,
                    'key' => 'merx.completePayment'
                ]);
            }

            return MollieGateway::updateOrderPage($virtualOrderPage, [
                'paymentMethod' => 'mollie:' . $payment->method,
                'paymentComplete' => true,
                'paidDate' => date('c')
            ]);
        },
        'initializePayment'  => function ($virtualOrderPage, $data) {
            $data['redirectUrl'] = 'https://yourshopdomain.com/return';
            $data['description'] = 'Order Id # ' . $virtualOrderPage->uid();
            
            return $data;
        }
    ]
];
```
