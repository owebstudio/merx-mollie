<?php

use Kirby\Cms\App as Kirby;
use Oweb\MollieGateway;

@include_once __DIR__ . '/vendor/autoload.php';

// Register the plugin
Kirby::plugin('owebstudio/merx-mollie', [
    'options' => [
        'apiKey' => null,
        'webhooks' => true
    ],
    'hooks' => [
        // Merx may be loaded after this plugin
        'system.loadPlugins:after' => function () {
            MollieGateway::register();
        }
    ],
    'routes' => include __DIR__ . '/routes.php'
]);
