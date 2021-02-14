<?php

use Illuminate\Database\Seeder;
use App\RefNoSetting;

class RefNoSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $setting = RefNoSetting::create([
            'start' => 1,
        	'active' => 'Y'
        ]);
    }
}
