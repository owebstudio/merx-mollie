# Mollie Gateway for Merx Kirby Plugin

Mollie payment gateway for [Kirby Merx plugin](https://github.com/wagnerwagner/merx).

## Installation

### Installation with composer

```ssh
composer require clicktonext/merx-mollie
```

### Add as git submodule

```ssh
git submodule add https://github.com/clicktonext/merx-mollie.git site/plugins/merx-mollie
```

## Options

| Option | Type | Default | Description |
|:---|:--- |:---|:---|
| clicktonext.merx-mollie.apiKey | string | null | API key for Mollie (required) |

All the values can be updated in the `config.php` file. 

To test it, you need to enable at least one payment method. You can enable payment methods using the [Mollie Dashboard](https://www.mollie.com/dashboard/settings/profiles).
