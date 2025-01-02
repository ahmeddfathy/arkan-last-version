<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // إنشاء الصلاحيات إذا لم تكن موجودة بالفعل
        $permissions = [
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'hr_respond_absence_request',
            'manager_respond_absence_request',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'hr_respond_permission_request',
            'manager_respond_permission_request',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'hr_respond_overtime_request',
            'manager_respond_overtime_request',
            'view_own_data',
            'view_team_data',
            'view_department_data',
            'view_all_data', // صلاحيات عرض البيانات
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // إنشاء الأدوار إذا لم تكن موجودة بالفعل
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $teamLeader = Role::firstOrCreate(['name' => 'team_leader']);
        $departmentManager = Role::firstOrCreate(['name' => 'department_manager']);
        $hr = Role::firstOrCreate(['name' => 'hr']);
        $companyManager = Role::firstOrCreate(['name' => 'company_manager']);

        // تخصيص الصلاحيات للأدوار
        $employee->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'view_own_data',
        ]);

        $teamLeader->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'manager_respond_absence_request',
            'manager_respond_permission_request',
            'manager_respond_overtime_request',
            'view_own_data',
            'view_team_data',
        ]);

        $departmentManager->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'manager_respond_absence_request',
            'manager_respond_permission_request',
            'manager_respond_overtime_request',
            'view_own_data',
            'view_department_data',
        ]);

        $hr->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'hr_respond_absence_request',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'hr_respond_permission_request',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'hr_respond_overtime_request',
            'view_all_data', // عرض جميع البيانات
        ]);

        $companyManager->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'hr_respond_absence_request',
            'manager_respond_absence_request',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'hr_respond_permission_request',
            'manager_respond_permission_request',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'hr_respond_overtime_request',
            'manager_respond_overtime_request',
            'view_all_data',
        ]);

        // إضافة دور الموظف لجميع المستخدمين الذين ليس لديهم أدوار
        $users = User::whereDoesntHave('roles')->get();
        foreach ($users as $user) {
            $user->assignRole('employee');
        }

        // إضافة صلاحيات الموظف لجميع المستخدمين
        $employeePermissions = [
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'view_own_data',
        ];

        User::all()->each(function ($user) use ($employeePermissions) {
            $user->givePermissionTo($employeePermissions);
        });

        // إضافة الصلاحيات الجديدة

        // تحديد الصلاحيات الإلزامية لكل دور
        $mandatoryPermissions = [
            'employee' => ['view_own_data'],
            'manager' => ['view_own_data', 'view_team_data'],
            'hr' => ['view_own_data', 'view_all_data']
        ];
    }
}
