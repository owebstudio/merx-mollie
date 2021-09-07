<?php

use Kirby\Exception\Exception;
use ClickToNext\MollieGateway;

return [
    [
        'pattern' => 'shop-api/mollie-webhook',
        'method' => 'post',
        'action' => function () {
            try {
                $orderId = get('id');

                if (empty($orderId) === true) {
                    throw new Exception('Invalid order id');
                }

                $ordersPage = page(option('ww.merx.ordersPage', 'orders'));

                if ($orderPage = $ordersPage->children()->findBy('orderId', $orderId)) {
                    // Create mollie gateway instance
                    $mollie = new MollieGateway();

                    // Get payment from mollie
                    $payment = $mollie->getPayment($orderId);
                    $status = $payment->status ?? 'failed';

                    kirby()->impersonate('kirby');
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
];

