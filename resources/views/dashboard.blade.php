@extends('layouts.app')

@section('content')

<head>
  <link rel="stylesheet" href="{{asset('css/dashboard.css')}}">
</head>

<div class="dashboard-container">
  <div class="dashboard-header">
    <h1 class="text-3xl font-bold text-white mb-2">مرحباً، {{ Auth::user()->name }}</h1>
    <p class="text-white/80">
      @if(Auth::user()->role === 'manager')
      إدارة حضور وأداء الفريق
      @else
      متابعة الحضور والانصراف
      @endif
    </p>
  </div>

  @if(Auth::user()->role === 'manager')
  <!-- إحصائيات اليوم للمدير -->
  <div class="stats-container">
    <h2 class="text-xl font-semibold mb-4">إحصائيات اليوم</h2>
    <div class="row">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon bg-primary">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-details">
            <h3>{{ $todayStats['totalEmployees'] }}</h3>
            <p>إجمالي الموظفين</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon bg-success">
            <i class="fas fa-user-check"></i>
          </div>
          <div class="stat-details">
            <h3>{{ $todayStats['presentToday'] }}</h3>
            <p>الحضور اليوم</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- طلبات اليوم -->
  <div class="row mt-4">
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar-times"></i>
        </div>
        <div class="stat-value">{{ $todayRequests['absenceRequests'] }}</div>
        <div class="stat-label">طلبات الغياب</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-door-open"></i>
        </div>
        <div class="stat-value">{{ $todayRequests['permissionRequests'] }}</div>
        <div class="stat-label">طلبات الاستئذان</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-business-time"></i>
        </div>
        <div class="stat-value">{{ $todayRequests['overtimeRequests'] }}</div>
        <div class="stat-label">طلبات العمل الإضافي</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-value">{{ $todayRequests['violations'] }}</div>
        <div class="stat-label">المخالفات</div>
      </div>
    </div>
  </div>

  <!-- إحصائيات الشهر للمدير -->
  <div class="stats-container mt-4">
    <h2 class="text-xl font-semibold mb-4">إحصائياتي الشهرية</h2>
    <div class="row">
      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-check text-white text-xl"></i>
          </div>
          <div class="stat-value">{{ $attendanceStats['present_days'] }}</div>
          <div class="stat-label">أيام الحضور</div>
          <div class="stat-sublabel">من أصل {{ $attendanceStats['total_work_days'] }} يوم</div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-times text-white text-xl"></i>
          </div>
          <div class="stat-value">{{ $attendanceStats['absent_days'] }}</div>
          <div class="stat-label">أيام الغياب</div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-clock text-white text-xl"></i>
          </div>
          <div class="stat-value">{{ $attendanceStats['late_days'] }}</div>
          <div class="stat-label">مرات التأخير</div>
          <div class="stat-sublabel">{{ $attendanceStats['total_delay_minutes'] }} دقيقة</div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-exclamation-triangle text-white text-xl"></i>
          </div>
          <div class="stat-value">{{ $attendanceStats['violation_days'] }}</div>
          <div class="stat-label">المخالفات</div>
        </div>
      </div>
    </div>
  </div>

  @endif

  <div class="quick-actions">
    @if(Auth::user()->role === 'manager')
    <a href="{{ route('users.index') }}" class="quick-action-btn">
      <i class="fas fa-users mb-2"></i>
      <span>إدارة الموظفين</span>
    </a>
    <a href="{{ route('salary-sheets.index') }}" class="quick-action-btn">
      <i class="fas fa-file-invoice-dollar mb-2"></i>
      <span>كشوف المرتبات</span>
    </a>
    @endif

    @if(Auth::user()->role === 'employee')
    <a href="{{ route('attendances.create') }}" class="quick-action-btn">
      <i class="fas fa-clock mb-2"></i>
      <span>تسجيل الحضور</span>
    </a>
    <a href="{{ route('leaves.create') }}" class="quick-action-btn">
      <i class="fas fa-calendar-plus mb-2"></i>
      <span>طلب إجازة</span>
    </a>
    @endif
  </div>

  <div class="requests-summary">
    <h2 class="text-xl font-semibold mb-4">Pending Requests</h2>
    <div class="request-stat">
      <i class="fas fa-clock mr-3"></i>
      <a href="{{ route('overtime-requests.index') }}" class="flex-1">
        Overtime Requests
      </a>
    </div>
    <div class="request-stat">
      <i class="fas fa-user-clock mr-3"></i>
      <a href="{{ route('absence-requests.index') }}" class="flex-1">
        Absence Requests
      </a>
    </div>
    <div class="request-stat">
      <i class="fas fa-door-open mr-3"></i>
      <a href="{{ route('permission-requests.index') }}" class="flex-1">
        Permission Requests
      </a>
    </div>
  </div>

  <div class="action-cards">
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-clock text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">Mark Attendance</h3>
      <a href="/attendances" class="btn-dashboard">
        Mark Attendance
      </a>
    </div>
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-calendar-plus text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">Mark Leave</h3>
      <a href="/leaves" class="btn-dashboard">
        Request Leave
      </a>
    </div>
  </div>

  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-file-import text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">Import Data</h3>
      <p class="text-gray-600 mb-4">Upload attendance records and user data</p>
      <div class="row g-2">
        <div class="col-6">
          <a href="/attendance" class="btn-dashboard">Import Attendance</a>
        </div>
        <div class="col-6">
          <a href="/users" class="btn-dashboard">Import Users</a>
        </div>
      </div>
    </div>
  </div>

  <div class="action-cards mt-5">
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-file-alt text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">Reports & Documents</h3>
      <p class="text-gray-600 mb-4">Access and manage important documents</p>
      <div class="space-y-2">
        <div class="row">
          <div class="col-6">
            <a href="{{ route('attendance.index') }}" class="btn-dashboard btn-block">Attendance Records</a>
          </div>
          <div class="col-6">
            <a href="{{ route('salary-sheets.index') }}" class="btn-dashboard btn-block">Salary Sheets</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .stat-sublabel {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
  }
</style>
@endpush
@endsection
