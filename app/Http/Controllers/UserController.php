<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;

use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
  public function index(Request $request)
  {
    $query = User::with(['roles', 'permissions']);

    // Search by employee name
    if ($request->has('employee_name') && !empty($request->employee_name)) {
      $query->where('name', 'like', "%{$request->employee_name}%");
    }

    // Search by department
    if ($request->has('department') && !empty($request->department)) {
      $query->where('department', $request->department);
    }

    // Search by employee status
    if ($request->has('status') && !empty($request->status)) {
      $query->where('employee_status', $request->status);
    }

    $users = $query->latest()->paginate(10);
    $employees = User::select('name')->distinct()->get();
    $departments = User::select('department')->distinct()->whereNotNull('department')->get();
    $roles = Role::all();
    $permissions = Permission::all();

    return view('users.index', compact('users', 'employees', 'departments', 'roles', 'permissions'));
  }

  public function show($id)
  {
    $user = User::findOrFail($id);
    return view('users.show', compact('user'));
  }

  public function destroy($id)
  {
    $user = User::findOrFail($id);
    $user->delete();

    return redirect()->route('users.index')
      ->with('success', 'User deleted successfully');
  }

  public function updateRolesAndPermissions(Request $request, $id)
  {
    $user = User::findOrFail($id);

    // تحديث الأدوار
    if ($request->has('roles')) {
      $user->syncRoles($request->roles);
    }

    // تحديث الصلاحيات
    if ($request->has('permissions')) {
      $user->syncPermissions($request->permissions);
    }

    return response()->json([
      'success' => true,
      'message' => 'تم تحديث الأدوار والصلاحيات بنجاح'
    ]);
  }

  public function import(Request $request)
  {
    Excel::import(new UsersImport, $request->file('file'));

    // إضافة دور الموظف تلقائياً للمستخدمين الجدد
    $employeeRole = Role::findByName('employee');
    User::whereDoesntHave('roles')->each(function ($user) use ($employeeRole) {
      $user->assignRole($employeeRole);
    });

    return redirect()->route('users.index')
      ->with('success', 'تم استيراد المستخدمين وتعيين الأدوار بنجاح');
  }

  public function removeRolesAndPermissions($id)
  {
    $user = User::findOrFail($id);
    $user->roles()->detach();
    $user->permissions()->detach();

    return response()->json([
      'success' => true,
      'message' => 'تم إزالة جميع الأدوار والصلاحيات بنجاح'
    ]);
  }

  public function resetToEmployee($id)
  {
    $user = User::findOrFail($id);
    $employeeRole = Role::findByName('employee');

    // إزالة جميع الأدوار والصلاحيات الحالية
    $user->roles()->detach();
    $user->permissions()->detach();

    // إضافة دور الموظف
    $user->assignRole($employeeRole);

    // إضافة صلاحيات الموظف
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
      'view_own_data'
    ];

    $user->syncPermissions($employeePermissions);

    return response()->json([
      'success' => true,
      'message' => 'تم إعادة تعيين المستخدم كموظف بنجاح'
    ]);
  }

  public function getEmployeesWithoutRole()
  {
    $usersWithoutRole = User::whereDoesntHave('roles')->get();
    return view('users.without_roles', compact('usersWithoutRole'));
  }

  public function assignEmployeeRole(Request $request)
  {
    $employeeRole = Role::findByName('employee');

    if ($request->has('user_ids')) {
      foreach ($request->user_ids as $userId) {
        $user = User::find($userId);
        if ($user) {
          $user->assignRole($employeeRole);
        }
      }
    }

    return redirect()->back()->with('success', 'تم تعيين دور الموظف بنجاح');
  }
}
