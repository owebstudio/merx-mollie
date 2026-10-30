<?php

use Kirby\Http\Response;
use Mollie\Api\Exceptions\ApiException;
use Oweb\MollieGateway;

return [
    [
        'pattern' => 'shop-api/mollie-webhook',
        'method' => 'post',
        'action' => function () {
            $paymentId = get('id');

            if (is_string($paymentId) === false || preg_match('/^tr_[a-zA-Z0-9]+$/', $paymentId) !== 1) {
                return new Response('Invalid payment id', 'text/plain', 400);
            }

            try {
                // Get payment from provider, the webhook only sends the id
                $gateway = new MollieGateway();
                $payment = $gateway->getPayment($paymentId);
            } catch (ApiException $e) {
                // Mollie recommends a 200 for unknown ids to not leak which ids exist
                if ($e->getCode() === 404) {
                    return new Response('', 'text/plain', 200);
                }

                error_log('[merx-mollie] Webhook for payment ' . $paymentId . ' failed: ' . $e->getMessage());

                // Any other status than 200 makes Mollie retry the webhook later
                return new Response('Mollie API error', 'text/plain', 503);
            }

            $ordersPage = page(option('ww.merx.ordersPage', 'orders'));
            $orderPage = $ordersPage?->children()->findBy('orderId', $payment->id);

            if ($orderPage === null) {
                // The order page is created when the buyer returns to the shop,
                // so let Mollie retry until it exists
                if ($payment->isPaid() === true) {
                    error_log('[merx-mollie] Payment ' . $payment->id . ' is paid, but there is no order for it (yet)');

                    return new Response('Order not found', 'text/plain', 404);
                }

                return new Response('', 'text/plain', 200);
            }

            if ($orderPage->paymentComplete()->bool() === false) {
                if ($payment->isPaid() === true) {
                    kirby()->impersonate('kirby');

                    $orderPage->update([
                        'paymentComplete' => true,
                        'paidDate' => date('c')
                    ]);
                } elseif ($payment->isFailed() === true || $payment->isExpired() === true || $payment->isCanceled() === true) {
                    error_log('[merx-mollie] Payment ' . $payment->id . ' of order ' . $orderPage->id() . ' is ' . $payment->status);
                }
            }

            if ($payment->hasRefunds() === true) {
                error_log('[merx-mollie] Payment ' . $payment->id . ' of order ' . $orderPage->id() . ' has refunds: ' . $payment->getAmountRefunded() . ' ' . $payment->amount->currency);
            }

            if ($payment->hasChargebacks() === true) {
                error_log('[merx-mollie] Payment ' . $payment->id . ' of order ' . $orderPage->id() . ' has chargebacks: ' . $payment->getAmountChargedBack() . ' ' . $payment->amount->currency);
            }

            return new Response('', 'text/plain', 200);
        }
    ]
];

