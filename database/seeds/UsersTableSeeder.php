<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->delete();

        DB::table('users')->insert([
            'email' => 'chriseen313@gmail.com',
            'password' => bcrypt('12345678'),
            'first_name' => 'Lionel',
            'last_name' => 'Messi',
            'mobile' => '1234567890',
        ]);
    }
}
