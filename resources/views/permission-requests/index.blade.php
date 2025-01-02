@extends('layouts.app')

@section('content')
<link href="{{ asset('css/permission-managment.css') }}" rel="stylesheet">
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

    <!-- قسم البحث والفلترة -->
    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('permission-requests.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="employee_name" class="form-label">بحث عن موظف</label>
                    <input type="text"
                        class="form-control"
                        id="employee_name"
                        name="employee_name"
                        value="{{ request('employee_name') }}"
                        placeholder="ادخل اسم الموظف"
                        list="employee_names">

                    <datalist id="employee_names">
                        @foreach($users as $user)
                        <option value="{{ $user->name }}">
                            @endforeach
                    </datalist>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label">تصفية حسب الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">كل الحالات</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>معلق</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>موافق عليه</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>تطبيق الفلتر
                    </button>
                    <a href="{{ route('permission-requests.index') }}" class="btn btn-secondary ms-2">
                        <i class="fas fa-undo me-2"></i>إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- جدول طلباتي -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i> طلباتي
                        <small class="ms-2">(الدقائق المتبقية: {{ $myRemainingMinutes }} دقيقة)</small>
                    </h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                        <i class="fas fa-plus"></i> طلب جديد
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>وقت المغادرة</th>
                                <th>وقت العودة</th>
                                <th>المدة</th>
                                <th>السبب</th>
                                <th>رد المدير</th>
                                <th>رد HR</th>
                                <th>الحالة النهائية</th>
                                <th>حالة العودة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myRequests as $request)
                            <tr class="request-row">
                                <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ $request->minutes_used }} دقيقة</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->manager_status === 'approved' ? 'موافق' : ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->hr_status === 'approved' ? 'موافق' : ($request->hr_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->status === 'approved' ? 'موافق' : ($request->status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->getReturnStatusLabel() }}</td>
                                <td>
                                    <div class="action-buttons">
                                        @if($request->status === 'pending')
                                        <button class="btn btn-sm btn-warning edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPermissionModal"
                                            data-id="{{ $request->id }}"
                                            data-departure="{{ $request->departure_time }}"
                                            data-return="{{ $request->return_time }}"
                                            data-reason="{{ $request->reason }}">
                                            <i class="fas fa-edit"></i> تعديل
                                        </button>

                                        <form action="{{ route('permission-requests.destroy', $request) }}"
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

                                        @if(!Auth::user()->hasRole('employee') && $request->status === 'approved')
                                        <div class="violation-buttons">
                                            <button class="btn violation-btn btn-success"
                                                onclick="updateReturnStatus('{{ $request->id }}', 1)"
                                                {{ $request->returned_on_time === 1 ? 'disabled' : '' }}>
                                                <i class="fas fa-check"></i> عاد
                                            </button>
                                            <button class="btn violation-btn btn-danger"
                                                onclick="updateReturnStatus('{{ $request->id }}', 2)"
                                                {{ $request->returned_on_time === 2 ? 'disabled' : '' }}>
                                                <i class="fas fa-times"></i> لم يعد
                                            </button>
                                            <button class="btn violation-btn btn-secondary"
                                                onclick="updateReturnStatus('{{ $request->id }}', 0)"
                                                {{ $request->returned_on_time === 0 ? 'disabled' : '' }}>
                                                <i class="fas fa-undo"></i> ريست
                                            </button>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">لا توجد طلبات استئذان</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $myRequests->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- جدول طلبات الفريق -->
    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> طلبات الفريق
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>وقت المغادرة</th>
                                <th>وقت العودة</th>
                                <th>المدة</th>
                                <th>الدقائق المتبقية</th>
                                <th>السبب</th>
                                <th>رد المدير</th>
                                <th>سبب رفض المدير</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>حالة العودة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teamRequests as $request)
                            <tr class="request-row">
                                <!-- بيانات الطلب -->
                                <td>{{ $request->user->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ $request->minutes_used }} دقيقة</td>
                                <td>{{ $remainingMinutes[$request->user_id] ?? 0 }} دقيقة</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->manager_status === 'approved' ? 'موافق' : ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
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
                                <td>{{ $request->getReturnStatusLabel() }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- أزرار الرد للمدراء و HR -->
                                        @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']) && $request->user->teams()->exists())
                                        @if($request->manager_status === 'pending')
                                        <button class="btn btn-sm btn-info respond-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#respondModal"
                                            data-request-id="{{ $request->id }}"
                                            data-response-type="manager">
                                            <i class="fas fa-reply"></i> رد المدير
                                        </button>
                                        @else
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-warning modify-response-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modifyResponseModal"
                                                data-request-id="{{ $request->id }}"
                                                data-response-type="manager"
                                                data-status="{{ $request->manager_status }}"
                                                data-reason="{{ $request->manager_rejection_reason }}">
                                                <i class="fas fa-edit"></i> تعديل الرد
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                onclick="resetStatus('{{ $request->id }}', 'manager')">
                                                <i class="fas fa-undo"></i> إعادة تعيين
                                            </button>
                                        </div>
                                        @endif
                                        @endif

                                        @if(Auth::user()->hasRole('hr'))
                                        @if($request->hr_status === 'pending')
                                        <button class="btn btn-sm btn-info respond-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#respondModal"
                                            data-request-id="{{ $request->id }}"
                                            data-response-type="hr">
                                            <i class="fas fa-reply"></i> رد HR
                                        </button>
                                        @else
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-warning modify-response-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modifyResponseModal"
                                                data-request-id="{{ $request->id }}"
                                                data-response-type="hr"
                                                data-status="{{ $request->hr_status }}"
                                                data-reason="{{ $request->hr_rejection_reason }}">
                                                <i class="fas fa-edit"></i> تعديل رد HR
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                onclick="resetStatus('{{ $request->id }}', 'hr')">
                                                <i class="fas fa-undo"></i> إعادة تعيين
                                            </button>
                                        </div>
                                        @endif
                                        @endif

                                        <!-- أزرار المخالفات -->
                                        @if(!Auth::user()->hasRole('employee') && $request->status === 'approved')
                                        <div class="violation-buttons">
                                            <button class="btn violation-btn btn-success"
                                                onclick="updateReturnStatus('{{ $request->id }}', 1)"
                                                {{ $request->returned_on_time === 1 ? 'disabled' : '' }}>
                                                <i class="fas fa-check"></i> عاد
                                            </button>
                                            <button class="btn violation-btn btn-danger"
                                                onclick="updateReturnStatus('{{ $request->id }}', 2)"
                                                {{ $request->returned_on_time === 2 ? 'disabled' : '' }}>
                                                <i class="fas fa-times"></i> لم يعد
                                            </button>
                                            <button class="btn violation-btn btn-secondary"
                                                onclick="updateReturnStatus('{{ $request->id }}', 0)"
                                                {{ $request->returned_on_time === 0 ? 'disabled' : '' }}>
                                                <i class="fas fa-undo"></i> ريست
                                            </button>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center">لا توجد طلبات استئذان</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $teamRequests->links() }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- جدول طلبات الموظفين بدون فريق (لل HR فقط) -->
    @if(Auth::user()->hasRole('hr'))
    <div class="row justify-content-center mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> طلبات الموظفين بدون فريق
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الموظف</th>
                                <th>وقت المغادرة</th>
                                <th>وقت العودة</th>
                                <th>المدة</th>
                                <th>الدقائق المتبقية</th>
                                <th>السبب</th>
                                <th>رد المدير</th>
                                <th>سبب رفض المدير</th>
                                <th>رد HR</th>
                                <th>سبب رفض HR</th>
                                <th>الحالة النهائية</th>
                                <th>حالة العودة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($noTeamRequests ?? [] as $request)
                            <tr class="request-row">
                                <td>{{ $request->user->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->departure_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($request->return_time)->format('Y-m-d H:i') }}</td>
                                <td>{{ $request->minutes_used }} دقيقة</td>
                                <td>{{ $noTeamRemainingMinutes[$request->user_id] ?? 0 }} دقيقة</td>
                                <td>{{ $request->reason }}</td>
                                <td>
                                    <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $request->manager_status === 'approved' ? 'موافق' : ($request->manager_status === 'rejected' ? 'مرفوض' : 'معلق') }}
                                    </span>
                                </td>
                                <td>{{ $request->manager_rejection_reason ?? '-' }}</td>
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
                                <td>{{ $request->getReturnStatusLabel() }}</td>
                                <td>
                                    <div class="action-buttons">
                                        @if($request->hr_status === 'pending')
                                        <button class="btn btn-sm btn-info respond-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#respondModal"
                                            data-request-id="{{ $request->id }}"
                                            data-response-type="hr">
                                            <i class="fas fa-reply"></i> رد HR
                                        </button>
                                        @else
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-warning modify-response-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modifyResponseModal"
                                                data-request-id="{{ $request->id }}"
                                                data-response-type="hr"
                                                data-status="{{ $request->hr_status }}"
                                                data-reason="{{ $request->hr_rejection_reason }}">
                                                <i class="fas fa-edit"></i> تعديل رد HR
                                            </button>

                                            <form action="{{ route('permission-requests.reset-hr-status', $request) }}"
                                                method="POST"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit"
                                                    class="btn btn-sm btn-secondary"
                                                    onclick="return confirm('هل أنت متأكد من إعادة تعيين الرد؟')">
                                                    <i class="fas fa-undo"></i> إعادة تعيين
                                                </button>
                                            </form>
                                        </div>
                                        @endif

                                        <!-- أزرار المخالفات -->
                                        @if(!Auth::user()->hasRole('employee') && $request->status === 'approved')
                                        <div class="violation-buttons">
                                            <button class="btn violation-btn btn-success"
                                                onclick="updateReturnStatus('{{ $request->id }}', 1)"
                                                {{ $request->returned_on_time === 1 ? 'disabled' : '' }}>
                                                <i class="fas fa-check"></i> عاد
                                            </button>
                                            <button class="btn violation-btn btn-danger"
                                                onclick="updateReturnStatus('{{ $request->id }}', 2)"
                                                {{ $request->returned_on_time === 2 ? 'disabled' : '' }}>
                                                <i class="fas fa-times"></i> لم يعد
                                            </button>
                                            <button class="btn violation-btn btn-secondary"
                                                onclick="updateReturnStatus('{{ $request->id }}', 0)"
                                                {{ $request->returned_on_time === 0 ? 'disabled' : '' }}>
                                                <i class="fas fa-undo"></i> ريست
                                            </button>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center">لا توجد طلبات استئذان</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($noTeamRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $noTeamRequests->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Create Modal -->
    <div class="modal fade" id="createPermissionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('permission-requests.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-0">
                        <h5 class="modal-title">طلب استئذان جديد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager']))
                        <div class="mb-4">
                            <label class="form-label fw-bold">نوع الطلب</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="registration_type" id="self_registration" value="self" checked>
                                <label class="btn btn-outline-primary" for="self_registration">
                                    <i class="fas fa-user me-2"></i>لنفسي
                                </label>

                                <input type="radio" class="btn-check" name="registration_type" id="other_registration" value="other">
                                <label class="btn btn-outline-primary" for="other_registration">
                                    <i class="fas fa-users me-2"></i>لموظف آخر
                                </label>
                            </div>
                        </div>

                        <div class="mb-4" id="employee_select_container" style="display: none;">
                            <label for="user_id" class="form-label">اختر الموظف</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="" disabled selected>اختر موظف...</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label for="departure_time" class="form-label">وقت المغادرة</label>
                            <input type="datetime-local"
                                class="form-control"
                                id="departure_time"
                                name="departure_time"
                                required
                                min="{{ date('Y-m-d\TH:i') }}">
                        </div>

                        <div class="mb-3">
                            <label for="return_time" class="form-label">وقت العودة</label>
                            <input type="datetime-local"
                                class="form-control"
                                id="return_time"
                                name="return_time"
                                required
                                min="{{ date('Y-m-d\TH:i') }}">
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">السبب</label>
                            <textarea class="form-control"
                                id="reason"
                                name="reason"
                                required
                                rows="3"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إرسال الطلب</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editPermissionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editPermissionForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-0">
                        <h5 class="modal-title">تعديل طلب الاستئذان</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_departure_time" class="form-label">وقت المغادرة</label>
                            <input type="datetime-local"
                                class="form-control"
                                id="edit_departure_time"
                                name="departure_time"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_return_time" class="form-label">وقت العودة</label>
                            <input type="datetime-local"
                                class="form-control"
                                id="edit_return_time"
                                name="return_time"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">السبب</label>
                            <textarea class="form-control"
                                id="edit_reason"
                                name="reason"
                                required
                                rows="3"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
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
                    <div class="modal-header border-0">
                        <h5 class="modal-title">الرد على الطلب</h5>
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

                        <div class="mb-3" id="rejection_reason_container" style="display: none;">
                            <label class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="rejection_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
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
                    <input type="hidden" name="response_type" id="modify_response_type">

                    <div class="modal-header border-0">
                        <h5 class="modal-title">تعديل الرد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">الحالة</label>
                            <select class="form-select" id="modify_status" name="status" required>
                                <option value="approved">موافق</option>
                                <option value="rejected">مرفوض</option>
                            </select>
                        </div>

                        <div class="mb-3" id="modify_reason_container" style="display: none;">
                            <label class="form-label">سبب الرفض</label>
                            <textarea class="form-control"
                                id="modify_reason"
                                name="rejection_reason"
                                maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
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
        // تحريكات GSAP
        gsap.from(".request-row", {
            duration: 0.5,
            opacity: 0,
            y: 20,
            stagger: 0.1
        });

        // معالجة أحداث التعديل
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.id;
                const departureTime = this.dataset.departure;
                const returnTime = this.dataset.return;
                const reason = this.dataset.reason;

                const form = document.getElementById('editPermissionForm');
                form.action = `{{ url('permission-requests') }}/${requestId}`;

                document.getElementById('edit_departure_time').value = formatDateTime(departureTime);
                document.getElementById('edit_return_time').value = formatDateTime(returnTime);
                document.getElementById('edit_reason').value = reason;
            });
        });

        // معالجة أحداث الرد
        document.querySelectorAll('.respond-btn').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const form = document.getElementById('respondForm');

                // تحديث مسار النموذج حسب نوع الرد
                const route = responseType === 'manager' ?
                    `{{ url('permission-requests') }}/${requestId}/manager-status` :
                    `{{ url('permission-requests') }}/${requestId}/hr-status`;

                form.action = route;
                document.getElementById('response_type').value = responseType;

                // إعادة تعيين النموذج
                form.reset();
                document.getElementById('rejection_reason_container').style.display = 'none';
                document.getElementById('rejection_reason').removeAttribute('required');
            });
        });

        // معالجة أحداث تعديل الرد
        document.querySelectorAll('.modify-response-btn').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const status = this.dataset.status;
                const reason = this.dataset.reason;
                const form = document.getElementById('modifyResponseForm');

                // تحديث مسار النموذج حسب نوع الرد
                const route = responseType === 'manager' ?
                    `{{ url('permission-requests') }}/${requestId}/modify-manager-status` :
                    `{{ url('permission-requests') }}/${requestId}/modify-hr-status`;

                form.action = route;
                document.getElementById('modify_response_type').value = responseType;
                document.getElementById('modify_status').value = status;
                document.getElementById('modify_reason').value = reason || '';

                toggleRejectionReason('modify_status', 'modify_reason_container', 'modify_reason');
            });
        });

        // معالجة تغيير الحالة
        ['response_status', 'modify_status'].forEach(id => {
            document.getElementById(id).addEventListener('change', function() {
                const containerId = id === 'response_status' ? 'rejection_reason_container' : 'modify_reason_container';
                const textareaId = id === 'response_status' ? 'rejection_reason' : 'modify_reason';
                toggleRejectionReason(id, containerId, textareaId);
            });
        });

        // معالجة نوع التسجيل
        if (document.getElementById('self_registration')) {
            document.querySelectorAll('input[name="registration_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const container = document.getElementById('employee_select_container');
                    container.style.display = this.value === 'self' ? 'none' : 'block';
                });
            });
        }

        // دالة مساعدة لتنسيق التاريخ والوقت
        function formatDateTime(dateTimeStr) {
            const date = new Date(dateTimeStr);
            return date.toISOString().slice(0, 16);
        }

        // دالة مساعدة لإظهار/إخفاء حقل سبب الرفض
        function toggleRejectionReason(selectId, containerId, textareaId) {
            const select = document.getElementById(selectId);
            const container = document.getElementById(containerId);
            const textarea = document.getElementById(textareaId);

            if (select.value === 'rejected') {
                container.style.display = 'block';
                textarea.required = true;
            } else {
                container.style.display = 'none';
                textarea.required = false;
                textarea.value = '';
            }
        }
    });
</script>
@endpush

@endsection