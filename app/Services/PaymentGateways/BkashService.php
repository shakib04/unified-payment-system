<?php

namespace App\Services\PaymentGateways;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentGateways\PaymentGatewayInterface;
use App\Models\PaymentGateway;

class BkashService implements PaymentGatewayInterface
{
    protected $client;
    protected $baseUrl;
    protected $headers;
    protected $credentials;

    public function __construct()
    {
        $gateway = PaymentGateway::where('code', 'bkash')->first();

        if (!$gateway) {
            throw new \Exception('bKash payment gateway not configured');
        }

        $this->credentials = $gateway->api_credentials;
        $this->baseUrl = $gateway->base_url;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30.0,
        ]);

        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get authorization token from bKash
     *
     * @return string
     */
    private function getToken()
    {
        try {
            $response = $this->client->post('checkout/token/grant', [
                'headers' => $this->headers,
                'json' => [
                    'app_key' => $this->credentials['app_key'],
                    'app_secret' => $this->credentials['app_secret']
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['id_token'])) {
                return $result['id_token'];
            }

            throw new \Exception('Failed to get token from bKash');
        } catch (\Exception $e) {
            Log::error('bKash token error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize a payment with bKash
     *
     * @param float $amount
     * @param string $currency
     * @param array $metadata
     * @return array
     */
    public function initiatePayment(float $amount, string $currency, array $metadata = [])
    {
        try {
            $token = $this->getToken();

            $this->headers['Authorization'] = $token;

            $response = $this->client->post('checkout/payment/create', [
                'headers' => $this->headers,
                'json' => [
                    'amount' => $amount,
                    'currency' => $currency,
                    'intent' => 'sale',
                    'merchantInvoiceNumber' => $metadata['invoice_number'] ?? uniqid('INV-'),
                    'callbackURL' => route('payment.bkash.callback')
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('bKash payment initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute a payment with bKash
     *
     * @param string $paymentId
     * @param array $data
     * @return array
     */
    public function executePayment(string $paymentId, array $data = [])
    {
        try {
            $token = $this->getToken();

            $this->headers['Authorization'] = $token;

            $response = $this->client->post('checkout/payment/execute/' . $paymentId, [
                'headers' => $this->headers
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('bKash payment execution error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify a payment with bKash
     *
     * @param string $paymentId
     * @return array
     */
    public function verifyPayment(string $paymentId)
    {
        try {
            $token = $this->getToken();

            $this->headers['Authorization'] = $token;

            $response = $this->client->get('checkout/payment/query/' . $paymentId, [
                'headers' => $this->headers
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('bKash payment verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process a refund with bKash
     *
     * @param string $paymentId
     * @param float $amount
     * @param string $reason
     * @return array
     */
    public function refundPayment(string $paymentId, float $amount, string $reason = '')
    {
        try {
            $token = $this->getToken();

            $this->headers['Authorization'] = $token;

            $response = $this->client->post('checkout/payment/refund', [
                'headers' => $this->headers,
                'json' => [
                    'paymentID' => $paymentId,
                    'amount' => $amount,
                    'trxID' => '', // This should be filled with the transaction ID from the payment response
                    'reason' => $reason ?: 'Customer requested refund'
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('bKash refund error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get payment status from bKash
     *
     * @param string $paymentId
     * @return string
     */
    public function getPaymentStatus(string $paymentId)
    {
        $response = $this->verifyPayment($paymentId);

        if (isset($response['transactionStatus'])) {
            return $response['transactionStatus'];
        }

        return 'UNKNOWN';
    }
}
