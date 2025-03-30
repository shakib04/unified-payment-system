<?php

namespace Database\Seeders;

use App\Models\BillerCategory;
use Illuminate\Database\Seeder;

class BillerCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => 'Electricity',
                'code' => 'electricity',
                'icon' => 'fas fa-bolt'
            ],
            [
                'name' => 'Water',
                'code' => 'water',
                'icon' => 'fas fa-tint'
            ],
            [
                'name' => 'Gas',
                'code' => 'gas',
                'icon' => 'fas fa-fire'
            ],
            [
                'name' => 'Internet',
                'code' => 'internet',
                'icon' => 'fas fa-wifi'
            ],
            [
                'name' => 'Mobile',
                'code' => 'mobile',
                'icon' => 'fas fa-mobile-alt'
            ],
            [
                'name' => 'TV',
                'code' => 'tv',
                'icon' => 'fas fa-tv'
            ],
            [
                'name' => 'Education',
                'code' => 'education',
                'icon' => 'fas fa-graduation-cap'
            ],
            [
                'name' => 'Other',
                'code' => 'other',
                'icon' => 'fas fa-ellipsis-h'
            ]
        ];

        foreach ($categories as $category) {
            BillerCategory::create($category);
        }
    }
}

