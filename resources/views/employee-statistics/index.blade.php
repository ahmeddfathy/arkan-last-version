@extends('layouts.app')

<head>
    <style>
        .card {
            opacity: 1 !important;
        }
    </style>
</head>
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i> إحصائيات الموظفين
                    </h5>
                </div>

                <!-- Filters -->
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <!-- فلتر القسم للمدير فقط -->
                        @if(auth()->user()->role === 'manager')
                        <div class="col-md-3">
                            <label for="department" class="form-label">القسم</label>
                            <select class="form-select" id="department" name="department">
                                <option value="">كل الأقسام</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="col-md-4">
                            <label for="search" class="form-label">اختر الموظف</label>
                            <select class="form-select"
                                id="search"
                                name="search">
                                <option value="">كل الموظفين</option>
                                @foreach($allUsers as $emp)
                                <option value="{{ $emp->employee_id }}"
                                    {{ request('search') == $emp->employee_id ? 'selected' : '' }}>
                                    {{ $emp->employee_id }} - {{ $emp->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">من تاريخ</label>
                            <input type="date"
                                class="form-control"
                                id="start_date"
                                name="start_date"
                                value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">إلى تاريخ</label>
                            <input type="date"
                                class="form-control"
                                id="end_date"
                                name="end_date"
                                value="{{ $endDate }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> بحث
                            </button>
                            <a href="{{ route('employee-statistics.index') }}" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> إعادة تعيين
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>القسم</th>
                                    <th>الموظف</th>
                                    <th>أيام العمل</th>
                                    <th>أيام الحضور</th>
                                    <th>نسبة الحضور</th>
                                    <th>الغياب</th>
                                    <th>الأذونات</th>
                                    <th>الوقت الإضافي</th>
                                    <th>إجمالي التأخير</th>
                                    <th>التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $currentDepartment = ''; @endphp
                                @forelse($employees as $employee)
                                @if($employee->department !== $currentDepartment)
                                @php $currentDepartment = $employee->department; @endphp
                                <tr class="table-light">
                                    <td colspan="10" class="fw-bold">
                                        <i class="fas fa-building me-2"></i>
                                        {{ $currentDepartment ?? 'بدون قسم' }}
                                    </td>
                                </tr>
                                @endif
                                <tr class="request-row">
                                    <td>{{ $employee->department ?? 'غير محدد' }}</td>
                                    <td>
                                        <div>{{ $employee->name }}</div>
                                        <small class="text-muted">{{ $employee->employee_id }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $employee->total_working_days }} يوم
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $employee->actual_attendance_days }} يوم
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $employee->attendance_percentage >= 90 ? 'bg-success' : ($employee->attendance_percentage >= 75 ? 'bg-warning' : 'bg-danger') }}"
                                                role="progressbar"
                                                style="width: {{ $employee->attendance_percentage }}%"
                                                aria-valuenow="{{ $employee->attendance_percentage }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                                {{ $employee->attendance_percentage }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $employee->absences > 0 ? 'danger' : 'success' }}">
                                            {{ $employee->absences }} أيام
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $employee->permissions }} مرات
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $employee->overtimes }} ساعات
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $employee->delays > 0 ? 'warning' : 'success' }}">
                                            {{ $employee->delays }} دقيقة
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info"
                                            onclick="showDetails('{{ $employee->employee_id }}')">
                                            <i class="fas fa-eye"></i> التفاصيل
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>لا يوجد موظفين متاحين</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $employees->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل الموظف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    function showDetails(employeeId) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        fetch(`/employee-statistics/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => {
                const content = document.getElementById('modalContent');
                let html = `
                    <div class="text-center mb-4">
                        <h4>${data.employee.name}</h4>
                        <small class="text-muted">${data.employee.employee_id}</small>
                        <div class="mt-2">${data.employee.department || 'غير محدد'}</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted mb-2">أيام العمل</div>
                                <span class="badge bg-secondary">
                                    ${data.statistics.total_working_days} يوم
                                </span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted mb-2">أيام الحضور</div>
                                <span class="badge bg-primary">
                                    ${data.statistics.actual_attendance_days} يوم
                                </span>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="text-center">
                                <div class="text-muted mb-2">نسبة الحضور</div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar ${
                                        data.statistics.attendance_percentage >= 90 ? 'bg-success' :
                                        (data.statistics.attendance_percentage >= 75 ? 'bg-warning' : 'bg-danger')
                                    }"
                                    role="progressbar"
                                    style="width: ${data.statistics.attendance_percentage}%">
                                        ${data.statistics.attendance_percentage}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="text-center">
                                <div class="text-muted mb-2">الغياب</div>
                                <span class="badge bg-${data.statistics.absences > 0 ? 'danger' : 'success'}">
                                    ${data.statistics.absences} أيام
                                </span>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="text-center">
                                <div class="text-muted mb-2">الأذونات</div>
                                <span class="badge bg-info">
                                    ${data.statistics.permissions} مرات
                                </span>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="text-center">
                                <div class="text-muted mb-2">الوقت الإضافي</div>
                                <span class="badge bg-primary">
                                    ${data.statistics.overtimes} ساعات
                                </span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted mb-2">إجمالي التأخير</div>
                                <span class="badge bg-${data.statistics.delays > 0 ? 'warning' : 'success'}">
                                    ${data.statistics.delays} دقيقة
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- سجل الحضور التفصيلي -->
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2">
                            <i class="fas fa-calendar-check me-2"></i>سجل الحضور التفصيلي
                        </h6>
                        <div class="list-group mt-3">
                            ${data.statistics.attendance.map(record => `
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>${record.attendance_date}</span>
                                        <span class="badge ${
                                            record.status === 'حضـور' ? 'bg-success' :
                                            record.status === 'غيــاب' ? 'bg-danger' :
                                            record.status === 'عطله إسبوعية' ? 'bg-info' : 'bg-secondary'
                                        }">${record.status}</span>
                                    </div>
                                    ${record.entry_time ? `
                                        <div class="small mt-1">
                                            <span>الدول: ${record.entry_time}</span>
                                            ${record.exit_time ? `<span class="ms-2">الخروج: ${record.exit_time}</span>` : ''}
                                            ${record.delay_minutes > 0 ? `
                                                <span class="text-warning ms-2">
                                                    <i class="fas fa-clock"></i> تأخير: ${record.delay_minutes} دقيقة
                                                </span>
                                            ` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;

                content.innerHTML = html;
                new bootstrap.Modal(document.getElementById('detailsModal')).show();
            });
    }

    // Animation for new rows
    document.addEventListener('DOMContentLoaded', function() {
        gsap.from(".request-row", {
            duration: 0.5,
            opacity: 0,
            y: 20,
            stagger: 0.1
        });
    });

    // إزالة قيود التاريخ
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            // إزالة أي قيود
            input.removeAttribute('min');
            input.removeAttribute('max');

            // منع أي أحداث JavaScript تقيد اختيار التاريخ
            input.addEventListener('mousedown', function(e) {
                e.stopPropagation();
            }, true);
        });
    });

    // إضافة دالة لتعيين التواريخ الافتراضية
    function setDefaultDates() {
        const now = new Date();
        const saturday = new Date(now);
        saturday.setDate(now.getDate() - now.getDay() + 6); // السبت الماضي

        const thursday = new Date(saturday);
        thursday.setDate(saturday.getDate() + 5); // الخميس القادم

        document.getElementById('start_date').value = saturday.toISOString().split('T')[0];
        document.getElementById('end_date').value = thursday.toISOString().split('T')[0];
    }

    // تعيين التواريخ الافتراضية عند تحميل الصفحة إذا لم يتم تحديد تواريخ
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.getElementById('start_date').value || !document.getElementById('end_date').value) {
            setDefaultDates();
        }
    });
</script>
@endpush