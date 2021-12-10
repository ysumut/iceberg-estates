<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        UserType::insert([
            ['id' => 1, 'type' => 'boss'],
            ['id' => 2, 'type' => 'staff'],
        ]);

        User::create([
            'name' => 'Yusuf Umut',
            'surname' => 'Bulak',
            'email' => 'yusufumutbulak@gmail.com',
            'phone_number' => '5051234567',
            'password' => Hash::make('deneme123'),
            'user_type' => User::BOSS_ID,
        ]);
    }
}
