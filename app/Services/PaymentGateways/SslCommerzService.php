<?php

namespace App\Services\PaymentGateways;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentGateways\PaymentGatewayInterface;
use App\Models\PaymentGateway;

class SslCommerzService implements PaymentGatewayInterface
{
    protected $client;
    protected $baseUrl;
    protected $credentials;

    public function __construct()
    {
        $gateway = PaymentGateway::where('code', 'sslcommerz')->first();

        if (!$gateway) {
            throw new \Exception('SSLCOMMERZ payment gateway not configured');
        }

        $this->credentials = $gateway->api_credentials;
        $this->baseUrl = $gateway->base_url;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30.0,
        ]);
    }

    /**
     * Initialize a payment with SSLCOMMERZ
     *
     * @param float $amount
     * @param string $currency
     * @param array $metadata
     * @return array
     */
    public function initiatePayment(float $amount, string $currency, array $metadata = [])
    {
        try {
            $data = [
                'store_id' => $this->credentials['store_id'],
                'store_passwd' => $this->credentials['store_password'],
                'total_amount' => $amount,
                'currency' => $currency,
                'tran_id' => $metadata['transaction_id'] ?? uniqid('TXN-'),
                'success_url' => route('payment.sslcommerz.success'),
                'fail_url' => route('payment.sslcommerz.fail'),
                'cancel_url' => route('payment.sslcommerz.cancel'),
                'cus_name' => $metadata['customer_name'] ?? 'Customer',
                'cus_email' => $metadata['customer_email'] ?? 'customer@example.com',
                'cus_phone' => $metadata['customer_phone'] ?? '01XXXXXXXXX',
                'cus_add1' => $metadata['customer_address'] ?? 'Dhaka',
                'cus_city' => $metadata['customer_city'] ?? 'Dhaka',
                'cus_country' => 'Bangladesh',
                'shipping_method' => 'NO',
                'product_name' => $metadata['product_name'] ?? 'Payment',
                'product_category' => $metadata['product_category'] ?? 'Payment',
                'product_profile' => 'general',
            ];

            $response = $this->client->post('gwprocess/v4/api.php', [
                'form_params' => $data
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('SSLCOMMERZ payment initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute a payment with SSLCOMMERZ
     * Note: SSLCOMMERZ doesn't require execute step as it's handled in their UI
     *
     * @param string $paymentId
     * @param array $data
     * @return array
     */
    public function executePayment(string $paymentId, array $data = [])
    {
        return [
            'success' => true,
            'message' => 'SSLCOMMERZ handles payment execution in their UI'
        ];
    }

    /**
     * Verify a payment with SSLCOMMERZ
     *
     * @param string $transactionId
     * @return array
     */
    public function verifyPayment(string $transactionId)
    {
        try {
            $data = [
                'store_id' => $this->credentials['store_id'],
                'store_passwd' => $this->credentials['store_password'],
                'val_id' => $transactionId
            ];

            $response = $this->client->get('validator/api/validationserverAPI.php', [
                'query' => $data
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('SSLCOMMERZ payment verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process a refund with SSLCOMMERZ
     *
     * @param string $transactionId
     * @param float $amount
     * @param string $reason
     * @return array
     */
    public function refundPayment(string $transactionId, float $amount, string $reason = '')
    {
        try {
            $data = [
                'store_id' => $this->credentials['store_id'],
                'store_passwd' => $this->credentials['store_password'],
                'refund_amount' => $amount,
                'refund_remarks' => $reason,
                'bank_tran_id' => $transactionId,
                'refund_date' => date('Y-m-d H:i:s')
            ];

            $response = $this->client->post('validator/api/merchantTransIDvalidationAPI.php', [
                'form_params' => $data
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('SSLCOMMERZ refund error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get payment status from SSLCOMMERZ
     *
     * @param string $transactionId
     * @return string
     */
    public function getPaymentStatus(string $transactionId)
    {
        $response = $this->verifyPayment($transactionId);

        if (isset($response['status'])) {
            return $response['status'];
        }

        return 'UNKNOWN';
    }
}
