<?php

namespace Database\Seeders;

use App\Models\User;
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
        DB::table('user_types')->insert(['type' => 'estate_agent']);
        DB::table('user_types')->insert(['type' => 'customer']);

        User::create([
            'name' => 'Yusuf Umut',
            'surname' => 'Bulak',
            'email' => 'yusufumutbulak@gmail.com',
            'password' => Hash::make('deneme123'),
            'user_type' => 1,
        ]);
    }
}
