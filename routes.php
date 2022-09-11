<?php

use Kirby\Exception\Exception;
use KirbyNext\MollieGateway;

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
                $orderPage = $ordersPage->children()->findBy('orderId', $orderId);

                if ($orderPage && $orderPage->paymentComplete()->bool() === false) {
                    // Create gateway instance
                    $gateway = new MollieGateway();

                    // Get payment from provider
                    if ($payment = $gateway->getPayment($orderId)) {
                        $status = $payment->status ?? 'failed';

                        if ($status === 'paid') {
                            kirby()->impersonate('kirby');

                            $orderPage->update([
                                'paymentComplete' => true,
                                'payedDate' => date('c')
                            ]);
                        }
                    }
                } else {
                    throw new Exception('Invalid order');
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    ]
];

