@extends('layouts.app')

@section('content')

<head>
  <link rel="stylesheet" href="{{asset('css/dashboard.css')}}">
  <style>
    a {
      text-decoration: none;
    }

    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: white;
      border-radius: 1rem;
      padding: 1.5rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      background: linear-gradient(45deg, #3b82f6, #2563eb);
    }

    .stat-value {
      font-size: 1.875rem;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 0.5rem;
    }

    .stat-label {
      color: #6b7280;
      font-size: 0.875rem;
    }

    .stat-sublabel {
      font-size: 0.75rem;
      color: #6b7280;
      margin-top: 0.25rem;
    }
  </style>
</head>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
  {{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="dashboard-container">
  <div class="dashboard-header">
    <h1 class="text-3xl font-bold mb-2">مرحباً، {{ Auth::user()->name }}</h1>
    <p class="text-white/80">
      إحصائيات الفترة من {{ $startDate->format('d/m/Y') }} إلى {{ $endDate->format('d/m/Y') }}
    </p>
    <p class="text-white/60 text-sm">
      شهر {{ $attendanceStats['period']['month'] }} {{ $attendanceStats['period']['year'] }}
    </p>
  </div>

  <div class="stats-container">
    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-check text-white text-xl"></i>
      </div>
      <div class="stat-value">{{ $attendanceStats['present_days'] }}</div>
      <div class="stat-label">أيام الحضور</div>
      <div class="stat-sublabel">من أصل {{ $attendanceStats['total_work_days'] }} يوم عمل</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-times text-white text-xl"></i>
      </div>
      <div class="stat-value">{{ $attendanceStats['absent_days'] }}</div>
      <div class="stat-label">أيام الغياب</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-clock text-white text-xl"></i>
      </div>
      <div class="stat-value">{{ $attendanceStats['late_days'] }}</div>
      <div class="stat-label">مرات التأخير</div>
      <div class="stat-sublabel">{{ $attendanceStats['total_delay_minutes'] }} دقيقة</div>
    </div>

    <div class="stat-card">
      <div class="stat-icon">
        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
      </div>
      <div class="stat-value">{{ $attendanceStats['violation_days'] }}</div>
      <div class="stat-label">المخالفات</div>
    </div>
  </div>

  <div class="action-cards">
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-file-alt text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">Overtime Requests</h3>
      <a href="{{ route('overtime-requests.index') }}" class="btn-dashboard">
        View Overtime Requests
      </a>
    </div>
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-user-clock text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">Permission Requests</h3>
      <a href="{{ route('permission-requests.index') }}" class="btn-dashboard">
        Manage Permissions
      </a>
    </div>
  </div>

  <div class="action-cards">
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-user-minus text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">Absence Requests</h3>
      <a href="{{ route('absence-requests.index') }}" class="btn-dashboard">
        Manage Absences
      </a>
    </div>
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-bell text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">Notifications</h3>
      <a href="{{ route('notifications') }}" class="btn-dashboard">
        View Notifications
      </a>
    </div>
  </div>

  <div class="action-cards">
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-file-pdf text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">تقرير الحضور</h3>
      <a href="{{ route('user.previewAttendance', ['employee_id' => Auth::user()->employee_id]) }}" class="btn-dashboard">
        عرض التقرير
      </a>
    </div>
    <div class="action-card">
      <div class="action-icon">
        <i class="fas fa-envelope text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-semibold mb-3">Chat</h3>
      <a href="{{ route('chat.index') }}" class="btn-dashboard">
        Open Chat
      </a>
    </div>
  </div>

  @if($salaryFiles->count() > 0)
  <div>

  </div>
  <div class="container mt-5 ">
    <h3 class="text-center mb-4 ">Your Salary Sheets</h3>
    <div class="row ">
      @foreach($salaryFiles as $file)

      <div class="col-md-4 mb-4">
        <div class="card shadow-lg h-100 border-0">
          <div class="card-body text-center position-relative">
            <div class="salary-icon bg-gradient-primary text-white mb-3 d-inline-flex align-items-center justify-content-center">
              <i class="fas fa-file-invoice-dollar fa-2x"></i>
            </div>
            <h5 class="card-title text-dark mb-3">{{ $file->month }}</h5>
            <a href="{{ url('/salary-sheet/' . $file->employee_id . '/' . $file->month . '/' . basename($file->file_path)) }}"
              class="btn btn-primary btn-sm px-4 rounded-pill" target="_blank">
              View Salary Sheet
            </a>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @else
  <div class="container mt-5">
    <div class="alert alert-info text-center" role="alert">
      <p class="mb-0">No salary sheets available at the moment. Please check back later!</p>
    </div>
  </div>
  @endif

  @endsection