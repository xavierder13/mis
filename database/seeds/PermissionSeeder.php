<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'project-list',
            'project-create',
            'project-edit',
            'project-delete',
            'programmer-projects',
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            'project-log-list',
            'project-log-create',
            'project-log-edit',
            'project-log-delete',
            'permission-list',
            'permission-create',
            'permission-edit',
            'permission-delete',
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'department-list',
            'department-create',
            'department-edit',
            'department-delete',
            'manager-list',
            'manager-create',
            'manager-edit',
            'manager-delete',
            'holiday-list',
            'holiday-create',
            'holiday-edit',
            'holiday-delete',
            'ref-no-setting',
            'print-preview',
            'import-project',
            'export-project',
            
         ];
 
 
         foreach ($permissions as $permission) {
              Permission::create(['name' => $permission]);
         }
    }
}
