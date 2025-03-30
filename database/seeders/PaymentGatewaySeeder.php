<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key constraints
        //DB::statement('TRUNCATE TABLE payment_gateways RESTART IDENTITY');

        // Define payment gateways
        $gateways = [
            // Mobile Financial Services (MFS)
            [
                'name' => 'bKash',
                'code' => 'bkash',
                'description' => 'Mobile financial service for easy payments',
                'logo_path' => 'gateways/bkash-logo.png',
                'base_url' => 'https://www.bkash.com/api',
                'supports_recurring' => false,
                'is_active' => true,
                'api_credentials' => [
                    'merchant_id' => null,
                    'merchant_key' => null
                ],
                'webhook_urls' => [
                    'payment_success' => null,
                    'payment_failed' => null
                ]
            ],
            [
                'name' => 'Rocket',
                'code' => 'rocket',
                'description' => 'Mobile banking and payment solution',
                'logo_path' => 'gateways/rocket-logo.png',
                'base_url' => 'https://www.rocket.com/api',
                'supports_recurring' => false,
                'is_active' => true,
                'api_credentials' => [
                    'merchant_id' => null,
                    'merchant_key' => null
                ],
                'webhook_urls' => [
                    'payment_success' => null,
                    'payment_failed' => null
                ]
            ],
            [
                'name' => 'Nagad',
                'code' => 'nagad',
                'description' => 'Digital financial service',
                'logo_path' => 'gateways/nagad-logo.png',
                'base_url' => 'https://www.nagad.com/api',
                'supports_recurring' => false,
                'is_active' => true,
                'api_credentials' => [
                    'merchant_id' => null,
                    'merchant_key' => null
                ],
                'webhook_urls' => [
                    'payment_success' => null,
                    'payment_failed' => null
                ]
            ],
            [
                'name' => 'upay',
                'code' => 'upay',
                'description' => 'Digital wallet and payment platform',
                'logo_path' => 'gateways/upay-logo.png',
                'base_url' => 'https://www.upay.com/api',
                'supports_recurring' => false,
                'is_active' => true,
                'api_credentials' => [
                    'merchant_id' => null,
                    'merchant_key' => null
                ],
                'webhook_urls' => [
                    'payment_success' => null,
                    'payment_failed' => null
                ]
            ],

            // Card Payment Gateways
            [
                'name' => 'SSLCommerz',
                'code' => 'sslcommerz',
                'description' => 'Secure online payment gateway',
                'logo_path' => 'gateways/sslcommerz-logo.png',
                'base_url' => 'https://www.sslcommerz.com/api',
                'supports_recurring' => true,
                'is_active' => true,
                'api_credentials' => [
                    'store_id' => null,
                    'store_password' => null
                ],
                'webhook_urls' => [
                    'payment_success' => null,
                    'payment_failed' => null
                ]
            ],
            [
                'name' => 'Stripe',
                'code' => 'stripe',
                'description' => 'Global online payment platform',
                'logo_path' => 'gateways/stripe-logo.png',
                'base_url' => 'https://api.stripe.com/v1',
                'supports_recurring' => true,
                'is_active' => true,
                'api_credentials' => [
                    'public_key' => null,
                    'secret_key' => null
                ],
                'webhook_urls' => [
                    'payment_success' => null,
                    'payment_failed' => null
                ]
            ]
        ];

        // Insert payment gateways using DB::table to bypass model events
        foreach ($gateways as $gateway) {
            DB::table('payment_gateways')->insert([
                'name' => $gateway['name'],
                'code' => $gateway['code'],
                'description' => $gateway['description'],
                'logo_path' => $gateway['logo_path'],
                'base_url' => $gateway['base_url'],
                'supports_recurring' => $gateway['supports_recurring'],
                'is_active' => $gateway['is_active'],
                'api_credentials' => json_encode($gateway['api_credentials']),
                'webhook_urls' => json_encode($gateway['webhook_urls']),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
