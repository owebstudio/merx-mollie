<?php

use Kirby\Cms\App as Kirby;
use Kirby\Exception\Exception;
use Mollie\Api\MollieApiClient;
use Wagnerwagner\Merx\Gateways;

@include_once __DIR__ . '/vendor/autoload.php';

// Register the plugin
Kirby::plugin('clicktonext/merx-mollie', [
    'options' => [
        'apiKey' => null
    ],
    'routes' => [
        [
            'pattern' => 'shop-api/mollie-webhook',
            'method' => 'post',
            'action' => function () {
                try {
                    $orderId = get('id');

                    if (empty($orderId) === true) {
                        throw new Exception('Invalid order id');
                    }

                    kirby()->impersonate('kirby');
                    $ordersPage = page(option('ww.merx.ordersPage', 'orders'));

                    if ($orderPage = $ordersPage->children()->findBy('orderId', $orderId)) {
                        // Create mollie client
                        $mollie = new MollieApiClient();
                        $mollie->setApiKey(option('clicktonext.merx-mollie.apiKey'));

                        // Get payment from mollie
                        $payment = $mollie->payments->get($orderId);
                        $status = $payment->status ?? 'failed';

                        $orderPage->update([
                            'paymentStatus' => $status
                        ]);
                    } else {
                        throw new Exception('Invalid order');
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        ]
    ]
]);

// Add mollie gateway to Merx
Gateways::$gateways['mollie'] = [
    'initializePayment' => function (OrderPage $virtualOrderPage): OrderPage {
        $cart = $virtualOrderPage->cart();

        // Create mollie client
        $mollie = new MollieApiClient();
        $mollie->setApiKey(option('clicktonext.merx-mollie.apiKey'));

        // Create payment request
        $data = [
            'amount' => [
                'currency' => option('ww.merx.currency'),
                'value' => number_format($cart->getSum(), 2, '.', '')
            ],
            'description' => 'Order Id # ' . $virtualOrderPage->uid() . ' (' . url() . ')',
            'redirectUrl' => url(option('ww.merx.successPage'))
        ];

        if (option('clicktonext.merx-mollie.webhooks', false) === true) {
            $data['webhookUrl'] = url('shop-api/mollie-webhook');
        }
        $payment = $mollie->payments->create($data);

        // Update the order
        $virtualOrderPage->content()->update([
            'orderId' => $payment->id,
            'redirect' => $payment->getCheckoutUrl(),
        ]);

        return $virtualOrderPage;
    },
    'completePayment' => function (OrderPage $virtualOrderPage, array $data): OrderPage {
        // Create mollie client
        $mollie = new MollieApiClient();
        $mollie->setApiKey(option('clicktonext.merx-mollie.apiKey'));

        // Get payment from mollie
        $payment = $mollie->payments->get($virtualOrderPage->orderId());
        $status = $payment->status ?? 'failed';

        // Check payment status success
        if ($status !== 'paid') {
            throw new Exception([
                'httpCode' => 400,
                'key' => 'merx.' . ($status === 'canceled' ? 'paymentCanceled' : 'completePayment')
            ]);
        }

        // Update payment as completed
        $virtualOrderPage->content()->update([
            'paymentMethod' => 'mollie:' . $payment->method,
            'paymentComplete' => true,
            'paymentStatus' => $status,
            'payedDate' => date('c'),
        ]);

        return $virtualOrderPage;
    }
];
