<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AbsenceRequestController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\OverTimeRequestsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\MacAddressController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SalarySheetController;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExampleMail;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\OnlineStatusController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\EmployeeStatisticsController;
use Spatie\Permission\Models\Role;

Route::get('/send-mail', function () {
    $data = [
        'name' => 'Recipient Name',
        'message' => 'This is a test email.'
    ];

    Mail::to('ahmeddfathy087@gmail.com')->send(new ExampleMail($data));

    return 'Email Sent Successfully!';
});




// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('/');

Route::get('/welcome', function () {
    return "hello";
})->name('welcome');

Route::get('/mac-addresses', [MacAddressController::class, 'getMacAddresses']);

// Authentication routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Employee routes
Route::middleware(['auth'])->group(function () {

    Route::post('/absence-requests/{absenceRequest}/status', [AbsenceRequestController::class, 'updateStatus'])
        ->name('absence-requests.updateStatus');
});

// Manager routes
Route::middleware(['auth', 'role:manager'])->group(function () {

    Route::get('/employee-statistics', [EmployeeStatisticsController::class, 'index'])
        ->name('employee-statistics.index');
    Route::get('/employee-statistics/{id}', [EmployeeStatisticsController::class, 'getEmployeeDetails'])
        ->name('employee-statistics.details');



    Route::post('/permission-requests/{permissionRequest}/update-status', [PermissionRequestController::class, 'updateStatus'])
        ->name('permission-requests.update-status');
    Route::patch('/permission-requests/{permissionRequest}/reset-status', [PermissionRequestController::class, 'resetStatus'])
        ->name('permission-requests.reset-status');
    Route::patch('/permission-requests/{permissionRequest}/modify', [PermissionRequestController::class, 'modifyResponse'])
        ->name('permission-requests.modify');
    Route::patch('/permission-requests/{permissionRequest}/return-status', [PermissionRequestController::class, 'updateReturnStatus'])
        ->name('permission-requests.update-return-status');


    Route::patch('/overtime-requests/{overTimeRequest}/respond', [OverTimeRequestsController::class, 'updateStatus'])
        ->name('overtime-requests.respond');

    Route::patch('/overtime-requests/{id}', [OverTimeRequestsController::class, 'update']);
    Route::delete('/overtime-requests/{overtimeRequest}', [OverTimeRequestsController::class, 'destroy'])->name('overtime-requests.destroy');


    Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/import', [AttendanceRecordController::class, 'import'])->name('attendance.import');


    Route::resource('/attendances', AttendanceController::class);
    Route::resource('/leaves', LeaveController::class);

    Route::resource('admin/notifications', AdminNotificationController::class, [
        'as' => 'admin'
    ]);
});

// Shared routes (Manager & Employee)
Route::middleware(['auth', 'role:manager,employee'])->group(function () {
    // Attendance
    Route::resource('overtime-requests', OverTimeRequestsController::class);

    // Leave
    Route::resource('/absence-requests', AbsenceRequestController::class);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::get('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.mark-as-read');
    Route::get('/user/{employee_id}/attendance-preview', [DashboardController::class, 'previewAttendance'])
        ->name('user.previewAttendance');
    Route::get('/user/{employee_id}/attendance-report', [DashboardController::class, 'generateAttendancePDF'])
        ->name('user.downloadAttendanceReport');
    Route::patch('/absence-requests/{absenceRequest}/reset-status', [AbsenceRequestController::class, 'resetStatus'])
        ->name('absence-requests.reset-status');
    Route::patch('/absence-requests/{id}/modify', [AbsenceRequestController::class, 'modifyResponse'])
        ->name('absence-requests.modify');

    Route::get('/salary-sheet/{userId}/{month}/{filename}', function ($employee_id, $month, $filename) {
        $user = Auth::user();
        if ($user->employee_id != $employee_id && $user->role != 'manager') {
            abort(403, 'Unauthorized access');
        }
        $filePath = storage_path("app/private/salary_sheets/{$employee_id}/{$month}/{$filename}");
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
        return response()->file($filePath);
    })->middleware('auth');



    Route::resource('/permission-requests', PermissionRequestController::class);
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages/{receiver}', [ChatController::class, 'getMessages']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::post('/chat/mark-seen', [ChatController::class, 'markAsSeen']);
    Route::post('/status/update', [OnlineStatusController::class, 'updateStatus']);
    Route::get('/status/user/{userId}', [OnlineStatusController::class, 'getUserStatus']);
});
Route::get('/salary-sheets', [SalarySheetController::class, 'index'])->name('salary-sheets.index');
Route::post('/salary-sheets/upload', [SalarySheetController::class, 'upload'])->name('salary-sheets.upload');

// إضافة راوت جديد لتصدير PDF
Route::get('/attendance/{employee_id}/pdf', [DashboardController::class, 'generateAttendancePDF'])
    ->name('attendance.pdf')
    ->middleware(['auth', 'role:manager']);



Route::resource('users', UserController::class);
Route::post('user/import', [UserController::class, 'import'])->name('user.import');;

Route::post('/users/{user}/roles-permissions', [UserController::class, 'updateRolesAndPermissions'])
    ->name('users.roles-permissions');
Route::get('/roles/{role}/permissions', function ($role) {
    $role = Role::findByName($role);
    if (!$role) {
        return response()->json([]);
    }
    return response()->json($role->permissions->pluck('name'));
});

Route::post('/users/{user}/remove-roles', [UserController::class, 'removeRolesAndPermissions'])
    ->name('users.remove-roles');
Route::post('/users/{user}/reset-to-employee', [UserController::class, 'resetToEmployee'])
    ->name('users.reset-to-employee');
Route::get('/users-without-role', [UserController::class, 'getEmployeesWithoutRole'])
    ->name('users.without-role');
Route::post('/assign-employee-role', [UserController::class, 'assignEmployeeRole'])
    ->name('users.assign-employee-role');

// Overtime Request Routes
Route::middleware(['auth'])->group(function () {
    // Basic CRUD routes
    Route::get('/overtime-requests', [OverTimeRequestsController::class, 'index'])->name('overtime-requests.index');
    Route::post('/overtime-requests', [OverTimeRequestsController::class, 'store'])->name('overtime-requests.store');
    Route::post('/overtime-requests/{overTimeRequest}', [OverTimeRequestsController::class, 'update'])->name('overtime-requests.update');
    Route::delete('/overtime-requests/{overTimeRequest}', [OverTimeRequestsController::class, 'destroy'])->name('overtime-requests.destroy');

    // Manager Response Routes
    Route::post('/overtime-requests/{overTimeRequest}/manager-status', [OverTimeRequestsController::class, 'updateManagerStatus'])
        ->name('overtime-requests.manager-status');
    Route::post('/overtime-requests/{overTimeRequest}/modify-manager-status', [OverTimeRequestsController::class, 'modifyManagerStatus'])
        ->name('overtime-requests.modify-manager-status');
    Route::post('/overtime-requests/{overTimeRequest}/reset-manager-status', [OverTimeRequestsController::class, 'resetManagerStatus'])
        ->name('overtime-requests.reset-manager-status');

    // HR Response Routes
    Route::post('/overtime-requests/{overTimeRequest}/hr-status', [OverTimeRequestsController::class, 'updateHrStatus'])
        ->name('overtime-requests.hr-status');
    Route::post('/overtime-requests/{overTimeRequest}/modify-hr-status', [OverTimeRequestsController::class, 'modifyHrStatus'])
        ->name('overtime-requests.modify-hr-status');
    Route::post('/overtime-requests/{overTimeRequest}/reset-hr-status', [OverTimeRequestsController::class, 'resetHrStatus'])
        ->name('overtime-requests.reset-hr-status');
});
