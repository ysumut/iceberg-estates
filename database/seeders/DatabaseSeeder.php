<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
            ['id' => 1, 'type' => 'estate_agent'],
            ['id' => 2, 'type' => 'customer'],
        ]);

        User::create([
            'name' => 'Yusuf Umut',
            'surname' => 'Bulak',
            'email' => 'yusufumutbulak@gmail.com',
            'password' => Hash::make('deneme123'),
            'user_type' => User::ESTATE_AGENT,
        ]);
    }
}
