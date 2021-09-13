# Mollie Payment Gateway for Merx

This plugin is [Mollie](https://www.mollie.com) payment gateway for [Kirby Merx plugin](https://github.com/wagnerwagner/merx).

## Installation

### Standalone

1. Download the latest release
2. Unzip downloaded file
3. Copy/paste unzipped folder in your `/site/plugins` folder

### With composer

```ssh
composer require clicktonext/merx-mollie
```

### Git submodule

```ssh
git submodule add https://github.com/clicktonext/merx-mollie.git site/plugins/merx-mollie
```

## Setup

### Step 1: Get API key

Get API key from [Mollie dashboard](https://www.mollie.com/dashboard/developers/api-keys).

### Step 2: Set API key from config

Set `apiKey` option from config.php like following usage:

**/site/config/config.php**

```php
<?php

return [
    'clicktonext.merx-mollie.apiKey' => 'YOUR_API_KEY'
];
```

### Step 3: Enable payment methods

You need to enable at least one payment method from [Mollie Dashboard](https://www.mollie.com/dashboard/settings/profiles).

### Step 4: Add payment method

Enter the Kirby panel with Merx installed. Go to the payment methods section from the settings tab and add a new one. Make sure you type `mollie``` in the value field.

### Step 5: Test

:tada: Done! Now you're ready for first order!

## Options

| Option | Type | Default | Description |
|:---|:--- |:---|:---|
| apiKey | string | null | API key for Mollie (required) |
| webhooks | boolean | false | Activates Mollie webhooks to process real-time status updates |

All the values can be updated in the `config.php` file and prefixed with `clicktonext.merx-mollie.`.
