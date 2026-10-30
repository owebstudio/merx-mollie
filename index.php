<?php

use Kirby\Cms\App as Kirby;
use Oweb\MerxMollie\License\License;
use Oweb\MollieGateway;

@include_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/license/autoload.php';

// Only shown in the Panel's System view, the checkout is never blocked
$license = new License(
    plugin: 'owebstudio/merx-mollie',
    products: [15542],
    name: 'Mollie for Merx',
    url: 'https://owebstudio.com/kirby/plugins/mollie-merx'
);

// Register the plugin
Kirby::plugin('owebstudio/merx-mollie', $license->extend([
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
]), ...$license->args());
