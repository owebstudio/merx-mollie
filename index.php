<?php

use Kirby\Cms\App as Kirby;
use ClickToNext\MollieGateway;

@include_once __DIR__ . '/vendor/autoload.php';

// Register the plugin
Kirby::plugin('clicktonext/merx-mollie', [
    'options' => [
        'apiKey' => null,
        'webhooks' => false
    ],
    'routes' => include __DIR__ . '/routes.php'
]);

// Register the gateway
MollieGateway::register();
