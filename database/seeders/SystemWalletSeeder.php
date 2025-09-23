<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;

class SystemWalletSeeder extends Seeder
{
    public function run(): void
    {
        Wallet::firstOrCreate(
            ['name' => 'Platform system', 'is_system' => true],
            ['balance' => 0]
        );

        Wallet::firstOrCreate(
            ['name' => 'Points pool', 'is_system' => true],
            ['balance' => 0]
        );

        Wallet::firstOrCreate(
            ['name' => 'Platform main', 'is_system' => true],
            ['balance' => 0]
        );
    }
}
