<?php

namespace Database\Seeders;

use App\Models\Biller;
use App\Models\BillerCategory;
use Illuminate\Database\Seeder;

class BillerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get category IDs
        $electricity = BillerCategory::where('code', 'electricity')->first()->id;
        $water = BillerCategory::where('code', 'water')->first()->id;
        $gas = BillerCategory::where('code', 'gas')->first()->id;
        $internet = BillerCategory::where('code', 'internet')->first()->id;
        $mobile = BillerCategory::where('code', 'mobile')->first()->id;
        $tv = BillerCategory::where('code', 'tv')->first()->id;

        $billers = [
            // Electricity
            [
                'name' => 'Bangladesh Power Development Board',
                'code' => 'bpdb',
                'category_id' => $electricity,
                'logo_url' => '/img/billers/bpdb.png',
                'description' => 'Bangladesh Power Development Board (BPDB)',
                'is_active' => true
            ],
            [
                'name' => 'Dhaka Electric Supply Company',
                'code' => 'desco',
                'category_id' => $electricity,
                'logo_url' => '/img/billers/desco.png',
                'description' => 'Dhaka Electric Supply Company (DESCO)',
                'is_active' => true
            ],
            [
                'name' => 'Dhaka Power Distribution Company',
                'code' => 'dpdc',
                'category_id' => $electricity,
                'logo_url' => '/img/billers/dpdc.png',
                'description' => 'Dhaka Power Distribution Company (DPDC)',
                'is_active' => true
            ],

            // Water
            [
                'name' => 'Dhaka WASA',
                'code' => 'dwasa',
                'category_id' => $water,
                'logo_url' => '/img/billers/dwasa.png',
                'description' => 'Dhaka Water Supply and Sewerage Authority',
                'is_active' => true
            ],
            [
                'name' => 'Chattogram WASA',
                'code' => 'cwasa',
                'category_id' => $water,
                'logo_url' => '/img/billers/cwasa.png',
                'description' => 'Chattogram Water Supply and Sewerage Authority',
                'is_active' => true
            ],

            // Gas
            [
                'name' => 'Titas Gas',
                'code' => 'titas',
                'category_id' => $gas,
                'logo_url' => '/img/billers/titas.png',
                'description' => 'Titas Gas Transmission and Distribution Company Limited',
                'is_active' => true
            ],
            [
                'name' => 'Karnaphuli Gas',
                'code' => 'karnaphuli',
                'category_id' => $gas,
                'logo_url' => '/img/billers/karnaphuli.png',
                'description' => 'Karnaphuli Gas Distribution Company Limited',
                'is_active' => true
            ],

            // Internet
            [
                'name' => 'Link3 Technologies',
                'code' => 'link3',
                'category_id' => $internet,
                'logo_url' => '/img/billers/link3.png',
                'description' => 'Link3 Technologies Ltd.',
                'is_active' => true
            ],
            [
                'name' => 'Grameenphone Internet',
                'code' => 'gp_internet',
                'category_id' => $internet,
                'logo_url' => '/img/billers/grameenphone.png',
                'description' => 'Grameenphone Internet Services',
                'is_active' => true
            ],

            // Mobile
            [
                'name' => 'Grameenphone',
                'code' => 'grameenphone',
                'category_id' => $mobile,
                'logo_url' => '/img/billers/grameenphone.png',
                'description' => 'Grameenphone Mobile Services',
                'is_active' => true
            ],
            [
                'name' => 'Robi Axiata',
                'code' => 'robi',
                'category_id' => $mobile,
                'logo_url' => '/img/billers/robi.png',
                'description' => 'Robi Axiata Limited',
                'is_active' => true
            ],
            [
                'name' => 'Banglalink',
                'code' => 'banglalink',
                'category_id' => $mobile,
                'logo_url' => '/img/billers/banglalink.png',
                'description' => 'Banglalink Digital Communications Ltd.',
                'is_active' => true
            ],

            // TV
            [
                'name' => 'Akash DTH',
                'code' => 'akash',
                'category_id' => $tv,
                'logo_url' => '/img/billers/akash.png',
                'description' => 'Akash Digital TV',
                'is_active' => true
            ],
            [
                'name' => 'Dish TV',
                'code' => 'dish',
                'category_id' => $tv,
                'logo_url' => '/img/billers/dish.png',
                'description' => 'Dish TV Bangladesh',
                'is_active' => true
            ]
        ];

        foreach ($billers as $biller) {
            Biller::create($biller);
        }
    }
}
