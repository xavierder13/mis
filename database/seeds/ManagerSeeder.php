<?php

use Illuminate\Database\Seeder;
use App\Manager;
class ManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $managers = [
            'Joanalyn Chavez',
            'Sheryl Lorenzo',
            'Pamela Mallorca',
            'Mariel Quitaleg',
            'Nover Velasco',
            'Venus Venterez',
            'Vanessa Conmigo',
            'Neil Galvez',
            'Jenelyn Lim',
            'Lady Rose Lutriana',
            'Argie Ong',
            'Malou Baltazar',
        ];

        foreach ($managers as $manager) {
            Manager::create(
                [
                    'name' => $manager,
                    'department_id' => 0,
                    'active' => 'Y',
                ]
            );
       } 
    }
}
