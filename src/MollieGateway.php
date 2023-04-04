<?php

namespace Oweb;

use Closure;
use Kirby\Exception\Exception;
use Mollie\Api\MollieApiClient;
use OrderPage;
use Wagnerwagner\Merx\Gateways;

/**
 * Mollie payment gateway for Merx
 *
 * @package   Oweb
 * @author    oweb studio <contact@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
 */
class MollieGateway
{
    protected $client;

    public function __construct()
    {
        $this->client = new MollieApiClient();
        $this->client->setApiKey(option('owebstudio.merx-mollie.apiKey'));
    }

    /**
     * @return MollieApiClient
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * @param string $orderId
     * @return \Mollie\Api\Resources\Payment
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function getPayment(string $orderId)
    {
        return $this->client->payments->get($orderId);
    }

    /**
     * @param array $data
     * @return \Mollie\Api\Resources\Payment
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function createPayment(array $data)
    {
        return $this->client->payments->create($data);
    }

    /**
     * Register mollie gateway to Merx
     */
    public static function register()
    {
        Gateways::$gateways['mollie'] = [
            'initializePayment' => function (OrderPage $virtualOrderPage): OrderPage {
                $cart = $virtualOrderPage->cart();

                // Create gateway instance
                $mollie = new MollieGateway();

                // order description
                $orderDescription = option('owebstudio.merx-mollie.orderDescription');

                if ($orderDescription instanceof Closure) {
                    $orderDescription = $orderDescription($virtualOrderPage);
                }

                // Create payment data
                $data = [
                    'amount' => [
                        'currency' => option('ww.merx.currency'),
                        'value' => number_format($cart->getSum(), 2, '.', '')
                    ],
                    'description' => $orderDescription ?? 'Order Id # ' . $virtualOrderPage->uid() . ' (' . url() . ')',
                    'redirectUrl' => option('owebstudio.merx-mollie.redirectUrl', url(option('ww.merx.successPage')))
                ];

                // Set test mode
                if ($virtualOrderPage->testmode()->or(option('owebstudio.merx-mollie.testmode', false))->bool() === true) {
                    $data['testmode'] = true;
                }

                // Check and pass custom method option
                if ($virtualOrderPage->method()->isNotEmpty()) {
                    $method = $virtualOrderPage->method()->yaml();
                } else {
                    $method = option('owebstudio.merx-mollie.method', []);
                }

                if (empty($method) === false) {
                    $data['method'] = $method;
                }

                // Set webhook url if enabled
                if (option('owebstudio.merx-mollie.webhooks', false) === true) {
                    $data['webhookUrl'] = url('shop-api/mollie-webhook');
                }

                // Create payment request
                $payment = $mollie->createPayment($data);

                // Update the order
                $virtualOrderPage->content()->update([
                    'orderId' => $payment->id,
                    'redirect' => $payment->getCheckoutUrl(),
                ]);

                return $virtualOrderPage;
            },
            'completePayment' => function (OrderPage $virtualOrderPage, array $data): OrderPage {
                // Create gateway instance
                $gateway = new MollieGateway();

                // Get payment from mollie
                $payment = $gateway->getPayment($virtualOrderPage->orderId());
                $status = $payment->status ?? 'failed';

                // Check payment status success
                if (in_array($status, ['paid', 'pending', 'open']) === false) {
                    throw new Exception([
                        'httpCode' => 400,
                        'key' => 'merx.' . ($status === 'canceled' ? 'paymentCanceled' : 'completePayment')
                    ]);
                }

                // Update payment as completed
                if ($status === 'paid') {
                    $virtualOrderPage->content()->update([
                        'paymentMethod' => 'mollie:' . $payment->method,
                        'paymentComplete' => true,
                        'payedDate' => date('c')
                    ]);
                }

                return $virtualOrderPage;
            }
        ];
    }
}
