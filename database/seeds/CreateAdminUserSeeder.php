<?php

use Illuminate\Database\Seeder;
use App\User;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
        	'name' => 'MIS Administrator', 
            'email' => 'admin@mis.ac',
        	'password' => bcrypt('12345678'),
            'type' => 'Admin',
            'active' => 'Y',
        ]);
    }
}
