<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'طبي', 'description' => 'مساعدات طبية وعلاجية'],
            ['name' => 'تعليمي', 'description' => 'دعم التعليم والمدارس'],
            ['name' => 'غذائي', 'description' => 'سلال غذائية ومساعدات تموينية'],
            ['name' => 'إيواء', 'description' => 'توفير السكن والمأوى'],
            ['name' => 'مالي', 'description' => 'مساعدات مالية مباشرة'],
            ['name' => 'أخرى', 'description' => 'مساعدات عامة أخرى'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description'], 'status' => 'active']
            );
        }
    }
}
