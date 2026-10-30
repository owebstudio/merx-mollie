<?php

namespace Oweb;

use Closure;
use Kirby\Cms\App;
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
     * Updates the content of the (virtual) order page.
     * Kirby 5 doesn't keep changes made with `content()->update()`.
     *
     * @param OrderPage $orderPage
     * @param array $data
     * @return OrderPage
     */
    public static function updateOrderPage(OrderPage $orderPage, array $data)
    {
        if (version_compare(App::version(), '5.0.0', '>=') === true) {
            $orderPage->version('latest')->update($data);
        } else {
            $orderPage->content()->update($data);
        }

        return $orderPage;
    }

    /**
     * Checks for local urls that Mollie can't reach
     * and doesn't accept as webhook url
     *
     * @param string $url
     * @return bool
     */
    public static function isLocalUrl(string $url)
    {
        $host = trim((string)parse_url($url, PHP_URL_HOST), '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        return $host === '' || $host === 'localhost' || preg_match('/\.(localhost|local|test)$/', $host) === 1;
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

                // Create payment data
                $data = [
                    'amount' => [
                        'currency' => option('ww.merx.currency'),
                        'value' => number_format($cart->getSum(), 2, '.', '')
                    ],
                    'description' => 'Order Id # ' . $virtualOrderPage->uid() . ' (' . url() . ')',
                    'redirectUrl' => url(option('ww.merx.successPage'))
                ];

                // Set test mode
                if (option('owebstudio.merx-mollie.testmode', false) === true) {
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
                $webhookUrl = url('shop-api/mollie-webhook');

                if (option('owebstudio.merx-mollie.webhooks', true) === true && MollieGateway::isLocalUrl($webhookUrl) === false) {
                    $data['webhookUrl'] = $webhookUrl;
                }

                // Custom initialize payment
                $initializePayment = option('owebstudio.merx-mollie.initializePayment');

                if ($initializePayment instanceof Closure) {
                    $data = $initializePayment($virtualOrderPage, $data);
                }

                // Create payment request
                $payment = $mollie->createPayment($data);

                // Update the order, overwriting values that came from the checkout form
                MollieGateway::updateOrderPage($virtualOrderPage, [
                    'orderId' => $payment->id,
                    'redirect' => $payment->getCheckoutUrl(),
                    'paymentComplete' => false,
                ]);

                return $virtualOrderPage;
            },
            'completePayment' => function (OrderPage $virtualOrderPage, array $data): OrderPage {
                // Create gateway instance
                $gateway = new MollieGateway();

                // Get payment from mollie
                $payment = $gateway->getPayment($virtualOrderPage->orderId());
                $status = $payment->status ?? 'failed';

                // Custom complete payment
                $completePayment = option('owebstudio.merx-mollie.completePayment');

                if ($completePayment instanceof Closure) {
                    $virtualOrderPage = $completePayment($virtualOrderPage, $data, $payment);
                } else {
                    // Bank transfers stay open until the money arrives,
                    // any other open payment was left by the buyer
                    $pending = $status === 'pending' || ($status === 'open' && $payment->method === 'banktransfer');

                    // Check payment status success
                    if ($status !== 'paid' && $pending === false) {
                        throw new Exception([
                            'httpCode' => 400,
                            'key' => 'merx.' . (in_array($status, ['open', 'canceled']) === true ? 'paymentCanceled' : 'completePayment')
                        ]);
                    }

                    if ($status === 'paid') {
                        // Update payment as completed
                        MollieGateway::updateOrderPage($virtualOrderPage, [
                            'paymentMethod' => 'mollie:' . $payment->method,
                            'paymentComplete' => true,
                            'paidDate' => date('c')
                        ]);
                    } else {
                        // The webhook completes pending payments
                        MollieGateway::updateOrderPage($virtualOrderPage, [
                            'paymentMethod' => 'mollie:' . $payment->method,
                            'paymentComplete' => false
                        ]);
                    }
                }

                return $virtualOrderPage;
            }
        ];
    }
}
