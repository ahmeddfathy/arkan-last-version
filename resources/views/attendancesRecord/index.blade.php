@extends('layouts.app')
<head>
    <style>
        .card{
            opacity: 1 !important;
        }
    </style>
</head>
@section('content')
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="{{asset('css/user.css')}}">

<div class="container mt-5">
  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    @if(session('duplicate_count'))
    <br>
    <small>
      <i class="fas fa-info-circle"></i>
      تم تخطي {{ session('duplicate_count') }} من السجلات المتكررة.
      <a href="#" data-bs-toggle="modal" data-bs-target="#duplicatesModal">عرض التفاصيل</a>
    </small>
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @elseif(session('error'))
  <div class="alert alert-danger">
    {{ session('error') }}
  </div>
  @endif

  <!-- Employee Filter -->
  <div class="card mb-4">
    <div class="card-body">
      <form action="{{ route('attendance.index') }}" method="GET" class="form-inline">
        <div class="form-group mr-2">
          <label class="mr-2">الموظف:</label>
          <input type="text"
            list="employees-list"
            id="employee-search"
            class="form-control"
            placeholder="اختر الموظف..."
            value="{{ $selectedEmployeeName }}"
            onchange="updateEmployeeFilter(this)">

          <datalist id="employees-list">
            @foreach($employees as $employee)
            <option data-value="{{ $employee->employee_id }}" value="{{ $employee->name }}">
              @endforeach
          </datalist>

          <input type="hidden"
            name="employee_filter"
            id="employee_filter"
            value="{{ request('employee_filter') }}">
        </div>
        <div class="form-group mx-2">
          <label class="mr-2">الشهر:</label>
          <input type="month"
            name="month"
            class="form-control"
            value="{{ $selectedMonth }}"
            onchange="this.form.submit()">
        </div>
        <button type="submit" class="btn btn-primary mb-2 ml-2">بحث</button>
        @if(request('employee_filter'))
        <a href="{{ route('attendance.index') }}" class="btn btn-secondary mb-2 ml-2">مسح الفلتر</a>
        @endif
      </form>
    </div>
  </div>

  <!-- Statistics Cards -->
  @if(request('employee_filter'))
  <div class="statistics-section mb-4">
    <h4 class="mb-3">إحصائيات الحضور والغياب {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}</h4>
    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="card h-100 border-primary">
          <div class="card-body text-center">
            <h5 class="card-title text-primary">أيام الحضور</h5>
            <h2 class="display-4 text-primary">{{ $attendanceStats['present_days'] }}</h2>
            <p class="text-muted">يوم</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card h-100 border-danger">
          <div class="card-body text-center">
            <h5 class="card-title text-danger">أيام الغياب</h5>
            <h2 class="display-4 text-danger">{{ $attendanceStats['absent_days'] }}</h2>
            <p class="text-muted">يوم</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card h-100 border-warning">
          <div class="card-body text-center">
            <h5 class="card-title text-warning">أيام المخالفات</h5>
            <h2 class="display-4 text-warning">{{ $attendanceStats['violation_days'] }}</h2>
            <p class="text-muted">يوم</p>
          </div>
        </div>
      </div>
    </div>
    <div class="row mt-3">
      <div class="col-md-12">
        <div class="card border-info">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">إحصائيات التأخير</h5>
          </div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col-md-3">
                <div class="mb-3">
                  <h6 class="text-muted">عدد أيام التأخير</h6>
                  <h3 class="text-info">{{ $attendanceStats['late_days'] }}</h3>
                  <small class="text-muted">يوم</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="mb-3">
                  <h6 class="text-muted">إجمالي دقائق التأخير</h6>
                  <h3 class="text-info">{{ $attendanceStats['total_delay_minutes'] }}</h3>
                  <small class="text-muted">دقيقة</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="mb-3">
                  <h6 class="text-muted">متوسط التأخير اليومي</h6>
                  <h3 class="text-info">{{ $attendanceStats['avg_delay_minutes'] }}</h3>
                  <small class="text-muted">دقيقة</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="mb-3">
                  <h6 class="text-muted">أقصى تأخير</h6>
                  <h3 class="text-info">{{ $attendanceStats['max_delay_minutes'] }}</h3>
                  <small class="text-muted">دقيقة</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Import Button -->
  <div class="d-flex mb-4">
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#importModal1">
      استيراد سجلات الحضور
    </button>
  </div>

  <!-- Attendance Records Table -->
  <div class="card">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">سجلات الحضور</h4>
    </div>
    <div class="card-body">
      <table class="table table-hover table-bordered">
        <thead class="bg-light">
          <tr>
            <th>رقم الموظف</th>
            <th>تاريخ الحضور</th>
            <th>اليوم</th>
            <th>الحالة</th>
            <th>الوردية</th>
            <th>ساعات العمل</th>
            <th>وقت الدخول</th>
            <th>وقت الخروج</th>
            <th>دقائق التأخير</th>
            <th>دقائق الخروج المبكر</th>
            <th>ساعات العمل الفعلية</th>
            <th>ساعات العمل الإضافي</th>
            <th>الجزاء</th>
            <th>ملاحظات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($records as $record)
          <tr>
            <td>{{ $record->employee_id }}</td>
            <td>{{ \Carbon\Carbon::parse($record->attendance_date)->format('Y-m-d') }}</td>
            <td>{{ $record->day }}</td>
            <td>
              <span>
                {{ $record->status }}
              </span>
            </td>
            <td>{{ $record->shift }}</td>
            <td>{{ $record->shift_hours }}</td>
            <td>{{ $record->entry_time ?: 'لم يسجل' }}</td>
            <td>{{ $record->exit_time ?: 'لم يسجل' }}</td>
            <td>
              @if($record->delay_minutes > 0)
              <span class="text-danger">{{ $record->delay_minutes }}</span>
              @else
              {{ $record->delay_minutes }}
              @endif
            </td>
            <td>
              @if($record->early_minutes > 0)
              <span class="text-warning">{{ $record->early_minutes }}</span>
              @else
              {{ $record->early_minutes }}
              @endif
            </td>
            <td>{{ $record->working_hours }}</td>
            <td>
              @if($record->overtime_hours > 0)
              <span class="text-success">{{ $record->overtime_hours }}</span>
              @else
              {{ $record->overtime_hours }}
              @endif
            </td>
            <td>
              @if($record->penalty > 0)
              <span class="text-danger">{{ $record->penalty }}</span>
              @else
              {{ $record->penalty }}
              @endif
            </td>
            <td>{{ $record->notes ?: '-' }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="14" class="text-center">لا توجد سجلات حضور</td>
          </tr>
          @endforelse
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="d-flex justify-content-center mt-4">
        {{ $records->links() }}
      </div>
    </div>
  </div>
</div>

<!-- Modal 1 -->
<div class="modal fade" id="importModal1" tabindex="-1" role="dialog" aria-labelledby="importModal1Label" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModal1Label">Import Attendance Records</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('attendance.import') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <input type="file" name="file" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if(session('duplicates'))
<div class="modal fade" id="duplicatesModal" tabindex="-1" aria-labelledby="duplicatesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="duplicatesModalLabel">
          <i class="fas fa-exclamation-triangle text-warning"></i>
          السجلات المتكررة ({{ session('duplicate_count') }})
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
        <div class="alert alert-warning">
          تم تخطي السجلات التالية لأنها موجودة مسبقاً:
        </div>
        <div class="duplicate-records">
          @foreach(explode("\n", session('duplicates')) as $record)
          @if(!empty(trim($record)) && str_contains($record, 'الموظف'))
          <div class="duplicate-record-item p-2 border-bottom">
            {{ str_replace('- ', '', $record) }}
          </div>
          @endif
          @endforeach
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .duplicate-record-item {
    font-size: 0.9rem;
    background-color: #f8f9fa;
    transition: background-color 0.2s;
  }

  .duplicate-record-item:hover {
    background-color: #e9ecef;
  }
</style>
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var duplicatesModal = new bootstrap.Modal(document.getElementById('duplicatesModal'));
    duplicatesModal.show();
  });
</script>
@endpush
@endif

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
  function updateEmployeeFilter(input) {
    const datalist = document.getElementById('employees-list');
    const options = datalist.getElementsByTagName('option');
    const hiddenInput = document.getElementById('employee_filter');

    for (let option of options) {
      if (option.value === input.value) {
        hiddenInput.value = option.getAttribute('data-value');
        // Submit the form
        document.getElementById('filter-form').submit();
        break;
      }
    }

    // Clear filter if input is empty
    if (input.value === '') {
      hiddenInput.value = '';
      document.getElementById('filter-form').submit();
    }
  }
</script>

<style>
  .statistics-section .card {
    transition: transform 0.2s;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .statistics-section .card:hover {
    transform: translateY(-5px);
  }

  .statistics-section .display-4 {
    font-size: 2.5rem;
    font-weight: 600;
  }

  .statistics-section .card-title {
    font-size: 1.1rem;
    font-weight: 500;
  }

  .statistics-section .text-muted {
    font-size: 0.9rem;
    margin-bottom: 0;
  }

  .badge {
    padding: 5px 10px;
    font-size: 0.9rem;
  }

  .badge-success {
    background-color: #28a745;
    color: white;
  }

  .badge-danger {
    background-color: #dc3545;
    color: white;
  }

  table th {
    font-weight: 600;
    background-color: #f8f9fa;
  }

  table td {
    vertical-align: middle !important;
  }

  /* تحسين شكل المودال */
  .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
  }

  .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
  }


  /* تحسين شكل الفلتر */
  .form-control {
    border-radius: 4px;
  }

  .btn {
    border-radius: 4px;
    padding: 8px 16px;
  }
</style>

@endsection
