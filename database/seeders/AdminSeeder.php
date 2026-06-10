<?php
namespace Database\Seeders;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@secondemain224.com'],
            [
                'name' => 'Admin Seconde Main',
                'phone' => '+224000000000',
                'password' => Hash::make('admin123@'),
                'role' => 'admin',
                'status' => 'actif',
            ]
        );
        
        Wallet::updateOrCreate(
            ['user_id' => $admin->id],
            ['balance' => 0, 'currency' => 'gnf']
        );
    }
}
