<?php

namespace App\Services\PaymentGateways;

interface PaymentGatewayInterface
{
    /**
     * Initialize a payment
     *
     * @param float $amount
     * @param string $currency
     * @param array $metadata
     * @return array
     */
    public function initiatePayment(float $amount, string $currency, array $metadata = []);

    /**
     * Execute a payment
     *
     * @param string $paymentId
     * @param array $data
     * @return array
     */
    public function executePayment(string $paymentId, array $data = []);

    /**
     * Verify a payment
     *
     * @param string $paymentId
     * @return array
     */
    public function verifyPayment(string $paymentId);

    /**
     * Process a refund
     *
     * @param string $paymentId
     * @param float $amount
     * @param string $reason
     * @return array
     */
    public function refundPayment(string $paymentId, float $amount, string $reason = '');

    /**
     * Get payment status
     *
     * @param string $paymentId
     * @return string
     */
    public function getPaymentStatus(string $paymentId);
}
