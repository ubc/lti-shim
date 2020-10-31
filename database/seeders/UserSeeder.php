<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert the shim's tool side
        DB::table('users')->insert([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => '$2y$10$x2V77Mj8pv5BkEcQQeS83.HnlAA5Mw5wvuU7opDDhl1/7KRDL2OQW'
        ]);
    }
}
