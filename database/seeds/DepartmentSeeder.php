<?php

use Illuminate\Database\Seeder;
use App\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departments = [
            'Inventory',
            'Management System',
            'Information System',
            'Motorpool',
            'Sales',
            'C&C',
            'Purchasing',
            'Treasury',
            'Trade Payables',
            'Engineering',
            'Audit',
            'Human Resource',
            'Marketing',
            'Payroll',
            'Government Compliance'
        ];

        foreach ($departments as $department) {
            Department::create(
                [
                    'name' => $department,
                    'active' => 'Y',
                ]
            );
       }    
    }
}
