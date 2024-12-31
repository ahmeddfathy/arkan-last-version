@extends('layouts.app')

@section('content')
<link href="{{ asset('css/absence-management.css') }}" rel="stylesheet">
<div class="container">
    @if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    @if(Auth::user()->hasRole('team_leader') || Auth::user()->hasRole('hr'))
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('absence-requests.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="employee_name" class="form-label">Search Employee</label>
                    <input
                        type="text"
                        class="form-control"
                        id="employee_name"
                        name="employee_name"
                        value="{{ request('employee_name') }}"
                        placeholder="Enter employee name"
                        list="employee_names">

                    <datalist id="employee_names">
                        @foreach($users as $user)
                        <option value="{{ $user->name }}">
                            @endforeach
                    </datalist>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('absence-requests.index') }}" class="btn btn-secondary ms-2">Reset</a>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- جدول طلبات المستخدم الشخصية (للجميع) -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> طلباتي
                        <small class="ms-2">(عدد أيام الغياب المعتمدة: {{ $myAbsenceDays }})</small>
                    </h5>
                    @if($canCreateAbsence)
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createAbsenceModal">
                        <i class="fas fa-plus"></i> طلب جديد
                    </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>تاريخ الغياب</th>
                                <th>السبب</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myRequests as $request)
                            <tr class="request-row">
                                <td>{{ $request->absence_date }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->status === 'approved' ? 'success' :
                                        ($request->status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->status === 'approved' ? 'موافق عليه' :
                                            ($request->status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>
                                    @if($request->status === 'pending')
                                    @if($canUpdateAbsence)
                                    <button class="btn btn-sm btn-warning edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAbsenceModal"
                                        data-request="{{ json_encode($request) }}">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    @endif

                                    @if($canDeleteAbsence)
                                    <form action="{{ route('absence-requests.destroy', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </form>
                                    @endif
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">لا توجد طلبات غياب</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول طلبات الفريق (للمدراء و HR فقط) -->
    @if(Auth::user()->hasRole(['hr', 'team_leader', 'department_manager', 'company_manager']))
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i> طلبات الغياب
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الموظف</th>
                                <th>أيام الغياب المعتمدة</th>
                                <th>تاريخ الغياب</th>
                                <th>السبب</th>
                                <th>رد المدير</th>
                                <th>سبب رفض المدير</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teamRequests as $request)
                            <tr class="request-row">
                                <td>{{ $request->user->name }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $absenceDaysCount[$request->user_id] ?? 0 }} أيام
                                    </span>
                                </td>
                                <td>{{ $request->absence_date }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->manager_status === 'approved' ? 'success' :
                                        ($request->manager_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->manager_status === 'approved' ? 'موافق' :
                                            ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->hr_status === 'approved' ? 'success' :
                                        ($request->hr_status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                            $request->hr_status === 'approved' ? 'موافق' :
                                            ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{
                                        $request->status === 'approved' ? 'success' :
                                        ($request->status === 'rejected' ? 'danger' : 'warning')
                                    }}">
                                        {{
                                                $request->status === 'approved' ? 'موافق' :
                                            ($request->status === 'rejected' ? 'مرفوض' : 'معلق')
                                        }}
                                    </span>
                                </td>
                                <td>
                                    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']))
                                    @if($request->manager_status === 'pending')
                                    @if($canRespondAsManager)
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager">
                                        <i class="fas fa-reply"></i> رد المدير
                                    </button>
                                    @endif
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager"
                                        data-status="{{ $request->manager_status }}"
                                        data-reason="{{ $request->manager_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد المدير
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="manager">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                    @endif

                                    @if(Auth::user()->hasRole('hr'))
                                    @if($request->hr_status === 'pending')
                                    @if($canRespondAsHR)
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @endif
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="hr">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                    @endif

                                    @if(Auth::id() === $request->user_id && $request->status === 'pending' && $canUpdateAbsence)
                                    <button class="btn btn-sm btn-warning edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAbsenceModal"
                                        data-request="{{ json_encode($request) }}">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    @endif

                                    @if(Auth::id() === $request->user_id && $request->status === 'pending' && $canDeleteAbsence)
                                    <form action="{{ route('absence-requests.destroy', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center">لا توجد طلبات غياب</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($teamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $teamRequests->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- جدول طلبات الموظفين بدون فريق (يظهر فقط لل HR) -->
    @if(Auth::user()->hasRole('hr') && $noTeamRequests->count() > 0)
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> طلبات الموظفين بدون فريق
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الموظف</th>
                                <th>أيام الغياب المعتمدة</th>
                                <th>تاريخ الغياب</th>
                                <th>السبب</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($noTeamRequests as $request)
                            <tr class="request-row">
                                <td>{{ $request->user->name }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $noTeamAbsenceDaysCount[$request->user_id] ?? 0 }} أيام
                                    </span>
                                </td>
                                <td>{{ $request->absence_date }}</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->hr_status === 'approved' ? 'موافق' : ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->hr_rejection_reason ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->status === 'approved' ? 'موافق' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>
                                    @if($request->hr_status === 'pending')
                                    @if($canRespondAsHR)
                                    <button class="btn btn-sm btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i> رد HR
                                    </button>
                                    @endif
                                    @else
                                    <button class="btn btn-sm btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-status="{{ $request->hr_status }}"
                                        data-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i> تعديل رد HR
                                    </button>

                                    <form action="{{ route('absence-requests.reset-status', $request) }}"
                                        method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="response_type" value="hr">
                                        <button type="submit"
                                            class="btn btn-sm btn-secondary"
                                            onclick="return confirm('هل أنت متأكد من إعادة تعيين الحالة؟')">
                                            <i class="fas fa-undo"></i> إعادة تعيين
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $noTeamRequests->appends(['employee_name' => request('employee_name'), 'status' => request('status')])->links() }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Create Modal -->
    <div class="modal fade" id="createAbsenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('absence-requests.store') }}" method="POST" id="createAbsenceForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">New Absence Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if(Auth::user()->role === 'manager')
                        <div class="mb-4">
                            <label for="registration_type" class="form-label fw-bold">Choose Registration Type:</label>
                            <div>
                                <input type="radio" id="self_registration" name="registration_type" value="self" checked onclick="toggleEmployeeSelect(true)">
                                <label for="self_registration" class="form-label">Register for Yourself</label>
                            </div>
                            <div>
                                <input type="radio" id="other_registration" name="registration_type" value="other" onclick="toggleEmployeeSelect(false)">
                                <label for="other_registration" class="form-label">Register for Another Employee</label>
                            </div>
                        </div>

                        <div class="mb-4" id="employee_select_container">
                            <label for="user_id" class="form-label fw-bold">Select Employee:</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="" disabled selected>Select an employee</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ auth()->user()->id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <script>
                            function toggleEmployeeSelect(isSelf) {
                                const userSelect = document.getElementById('user_id');
                                if (isSelf) {
                                    userSelect.disabled = true;
                                    userSelect.value = "{{ auth()->user()->id }}";
                                } else {
                                    userSelect.disabled = false;
                                    userSelect.value = "";
                                }
                            }


                            toggleEmployeeSelect(document.getElementById('self_registration').checked);
                        </script>



                        @endif
                        <div class="mb-3">
                            <label for="absence_date" class="form-label">Absence Date</label>
                            <input type="date"
                                class="form-control"
                                id="absence_date"
                                name="absence_date"
                                required
                                min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control"
                                id="reason"
                                name="reason"
                                required
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editAbsenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editAbsenceForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Absence Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_absence_date" class="form-label">Absence Date</label>
                            <input type="date"
                                class="form-control"
                                id="edit_absence_date"
                                name="absence_date"
                                required
                                min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">Reason</label>
                            <textarea class="form-control"
                                id="edit_reason"
                                name="reason"
                                required
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Respond Modal -->
    <div class="modal fade" id="respondModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="respondForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="responseTitle">الرد على الطلب</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="response_type" id="response_type">

                        <div class="mb-3">
                            <label class="form-label">الحالة</label>
                            <select class="form-select" id="response_status" name="status" required>
                                <option value="approved">موافق</option>
                                <option value="rejected">مرفوض</option>
                            </select>
                        </div>

                        <div class="mb-3" id="response_reason_container" style="display: none;">
                            <label class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="response_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ الرد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modify Response Modal -->
    <div class="modal fade" id="modifyResponseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="modifyResponseForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="response_type" id="modify_response_type">

                    <div class="modal-header">
                        <h5 class="modal-title">تعديل الرد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modify_status" class="form-label">الحالة</label>
                            <select class="form-select" id="modify_status" name="status" required>
                                <option value="approved">موافق</option>
                                <option value="rejected">مرفوض</option>
                            </select>
                        </div>

                        <div class="mb-3" id="modify_reason_container" style="display: none;">
                            <label for="modify_reason" class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="modify_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation for new rows
        gsap.from(".request-row", {
            duration: 0.5,
            opacity: 0,
            y: 20,
            stagger: 0.1
        });

        // Edit request handling
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const request = JSON.parse(this.dataset.request);
                const form = document.getElementById('editAbsenceForm');
                form.action = `/absence-requests/${request.id}`;
                document.getElementById('edit_absence_date').value = request.absence_date;
                document.getElementById('edit_reason').value = request.reason;
            });
        });

        // Response handling
        document.querySelectorAll('.respond-btn').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const form = document.getElementById('respondForm');

                // تحديث عنوان المودال
                document.getElementById('responseTitle').textContent =
                    responseType === 'manager' ? 'رد المدير' : 'رد HR';

                // تحديث نوع الرد
                document.getElementById('response_type').value = responseType;

                // تحديث مسار الفورم
                form.action = `/absence-requests/${requestId}/status`;
            });
        });

        // Show/hide rejection reason field
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const rejectionContainer = document.getElementById('rejection_reason_container');
                const rejectionTextarea = document.getElementById('rejection_reason');

                if (this.value === 'rejected') {
                    rejectionContainer.style.display = 'block';
                    rejectionTextarea.required = true;
                } else {
                    rejectionContainer.style.display = 'none';
                    rejectionTextarea.required = false;
                }
            });
        });

        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    toastr.error('Please fill in all required fields.');
                }
                form.classList.add('was-validated');
            });
        });
    });

    document.querySelectorAll('.modify-response-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const responseType = this.dataset.responseType;
            const form = document.getElementById('modifyResponseForm');

            form.action = `/absence-requests/${requestId}/modify`;

            // تحديث نوع الرد في النموذج
            document.getElementById('modify_response_type').value = responseType;

            // تحديث الحالة وسبب الرفض
            const requestStatus = this.dataset.status;
            const requestReason = this.dataset.reason;

            document.getElementById('modify_status').value = requestStatus;
            document.getElementById('modify_reason').value = requestReason || '';

            // عرض/إخفاء حقل سبب الرفض
            const reasonContainer = document.getElementById('modify_reason_container');
            if (requestStatus === 'rejected') {
                reasonContainer.style.display = 'block';
                document.getElementById('modify_reason').required = true;
            } else {
                reasonContainer.style.display = 'none';
                document.getElementById('modify_reason').required = false;
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {

        document.getElementById('response_status').addEventListener('change', function() {
            const rejectionContainer = document.getElementById('response_reason_container');
            const rejectionTextarea = document.getElementById('response_reason');

            if (this.value === 'rejected') {
                rejectionContainer.style.display = 'block';
                rejectionTextarea.required = true;
            } else {
                rejectionContainer.style.display = 'none';
                rejectionTextarea.required = false;
            }
        });


        document.getElementById('modify_status').addEventListener('change', function() {
            const rejectionContainer = document.getElementById('modify_reason_container');
            const rejectionTextarea = document.getElementById('modify_reason');

            if (this.value === 'rejected') {
                rejectionContainer.style.display = 'block';
                rejectionTextarea.required = true;
            } else {
                rejectionContainer.style.display = 'none';
                rejectionTextarea.required = false;
            }
        });


        if (document.getElementById('response_status').value === 'rejected') {
            document.getElementById('response_reason_container').style.display = 'block';
        }

        if (document.getElementById('modify_status').value === 'rejected') {
            document.getElementById('modify_reason_container').style.display = 'block';
        }
    });
</script>

@endpush

@endsection