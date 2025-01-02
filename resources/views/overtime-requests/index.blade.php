@extends('layouts.app')

<head>
    <link rel="stylesheet" href="{{asset('css/overtime-managment.css')}}">
</head>
@section('content')
<div class="container">
    @include('shared.alerts')

    <!-- إحصائيات للمدراء و HR -->
    @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Pending Requests</h6>
                    <h2 class="card-title mb-0">{{ $pendingCount }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Team Members</h6>
                    <h2 class="card-title mb-0">{{ $users->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Average Overtime Hours</h6>
                    <h2 class="card-title mb-0">
                        {{ number_format($users->count() > 0 ? array_sum($overtimeHoursCount) / $users->count() : 0, 1) }}
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول طلبات الفريق -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Team Overtime Requests</h5>
            <span class="badge bg-primary ms-2">{{ $myOvertimeHours }} hours approved</span>
        </div>
        <div class="card-body">
            <!-- فورم البحث -->
            <form method="GET" action="{{ route('overtime-requests.index') }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="employee_name" name="employee_name"
                        value="{{ $filters['employeeName'] }}" placeholder="Employee Name">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $filters['status'] === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('overtime-requests.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <!-- جدول طلبات الفريق -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                            <th>Employee</th>
                            @endif
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Reason</th>
                            <th>Manager Status</th>
                            <th>Manager Rejection</th>
                            <th>HR Status</th>
                            <th>HR Rejection</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teamRequests as $request)
                        <tr>
                            @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                            <td>{{ $request->user->name }}</td>
                            @endif
                            <td>{{ $request->overtime_date->format('Y-m-d') }}</td>
                            <td>
                                {{ Carbon\Carbon::parse($request->start_time)->format('H:i') }} -
                                {{ Carbon\Carbon::parse($request->end_time)->format('H:i') }}
                            </td>
                            <td>{{ $request->getFormattedDuration() }}</td>
                            <td>{{ Str::limit($request->reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->manager_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->manager_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->hr_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->hr_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($request->canUpdate(Auth::user()))
                                    <button type="button" class="btn btn-primary edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editOvertimeModal"
                                        data-request="{{ json_encode($request) }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif

                                    @if($request->canDelete(Auth::user()))
                                    <form action="{{ route('overtime-requests.destroy', $request->id) }}"
                                        method="POST"
                                        class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif

                                    @if($canRespondAsManager)
                                    @if($request->manager_status === 'pending')
                                    <button type="button" class="btn btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondOvertimeModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="manager"
                                        data-current-status="{{ $request->manager_status }}"
                                        data-current-reason="{{ $request->manager_rejection_reason }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary reset-btn"
                                        onclick="resetStatus('{{ $request->id }}', 'manager')">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    @endif
                                    @endif

                                    @if($canRespondAsHR)
                                    @if($request->hr_status === 'pending')
                                    <button type="button" class="btn btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondOvertimeModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-current-status="{{ $request->hr_status }}"
                                        data-current-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary reset-btn"
                                        onclick="resetStatus('{{ $request->id }}', 'hr')">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']) ? '11' : '10' }}"
                                class="text-center">
                                No overtime requests found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $teamRequests->links() }}
            </div>
        </div>
    </div>
    @endif

    <!-- جدول طلباتي -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">My Overtime Requests</h5>
            @if($canCreateOvertime)
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createOvertimeModal">
                <i class="fas fa-plus"></i> New Request
            </button>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Reason</th>
                            <th>Manager Status</th>
                            <th>Manager Rejection</th>
                            <th>HR Status</th>
                            <th>HR Rejection</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myRequests as $request)
                        <tr>
                            <td>{{ $request->overtime_date->format('Y-m-d') }}</td>
                            <td>
                                {{ Carbon\Carbon::parse($request->start_time)->format('H:i') }} -
                                {{ Carbon\Carbon::parse($request->end_time)->format('H:i') }}
                            </td>
                            <td>{{ $request->getFormattedDuration() }}</td>
                            <td>{{ Str::limit($request->reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->manager_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->manager_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->hr_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->hr_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($request->canUpdate(Auth::user()))
                                    <button type="button" class="btn btn-primary edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editOvertimeModal"
                                        data-request="{{ json_encode($request) }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif

                                    @if($request->canDelete(Auth::user()))
                                    <form action="{{ route('overtime-requests.destroy', $request->id) }}"
                                        method="POST"
                                        class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No overtime requests found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {{ $myRequests->links() }}
            </div>
        </div>
    </div>

    <!-- جدول الموظفين بدون فريق - لل HR -->
    @if(Auth::user()->hasRole('hr') && $noTeamRequests->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Requests from Employees Without Team</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Reason</th>
                            <th>Manager Status</th>
                            <th>Manager Rejection</th>
                            <th>HR Status</th>
                            <th>HR Rejection</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($noTeamRequests as $request)
                        <tr>
                            <td>{{ $request->user->name }}</td>
                            <td>{{ $request->overtime_date->format('Y-m-d') }}</td>
                            <td>
                                {{ Carbon\Carbon::parse($request->start_time)->format('H:i') }} -
                                {{ Carbon\Carbon::parse($request->end_time)->format('H:i') }}
                            </td>
                            <td>{{ $request->getFormattedDuration() }}</td>
                            <td>{{ Str::limit($request->reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->manager_status === 'approved' ? 'success' : ($request->manager_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->manager_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->manager_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->hr_status === 'approved' ? 'success' : ($request->hr_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->hr_status) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($request->hr_rejection_reason, 30) }}</td>
                            <td>
                                <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($canRespondAsHR)
                                    @if($request->hr_status === 'pending')
                                    <button type="button" class="btn btn-info respond-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#respondOvertimeModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    @else
                                    <button type="button" class="btn btn-warning modify-response-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modifyResponseModal"
                                        data-request-id="{{ $request->id }}"
                                        data-response-type="hr"
                                        data-current-status="{{ $request->hr_status }}"
                                        data-current-reason="{{ $request->hr_rejection_reason }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-secondary reset-btn"
                                        onclick="resetStatus('{{ $request->id }}', 'hr')">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-4">
                    {{ $noTeamRequests->links() }}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Create Modal -->
<div class="modal fade" id="createOvertimeModal" tabindex="-1" aria-labelledby="createOvertimeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createOvertimeModalLabel">New Overtime Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('overtime-requests.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                @if(Auth::user()->hasRole(['team_leader', 'department_manager', 'company_manager', 'hr']))
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="registration_type" id="self_registration" value="self" checked>
                            <label class="form-check-label" for="self_registration">For Myself</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="registration_type" id="other_registration" value="other">
                            <label class="form-check-label" for="other_registration">For Employee</label>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="employee_select_container">
                        <label for="user_id" class="form-label">Select Employee</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">Select Employee</option>
                            @foreach($users as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select an employee.</div>
                    </div>
                </div>
                @endif

                <input type="hidden" id="hidden_user_id" name="user_id" value="{{ Auth::id() }}">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="overtime_date" class="form-label">Overtime Date</label>
                        <input type="date" class="form-control" id="overtime_date" name="overtime_date" required min="{{ date('Y-m-d') }}">
                        <div class="invalid-feedback">Please select a valid date.</div>
                    </div>

                    <div class="mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                        <div class="invalid-feedback">Please select a start time.</div>
                    </div>

                    <div class="mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                        <div class="invalid-feedback">Please select an end time that is after the start time.</div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        <div class="invalid-feedback">Please provide a reason.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editOvertimeModal" tabindex="-1" aria-labelledby="editOvertimeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOvertimeModalLabel">Edit Overtime Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editOvertimeForm" method="POST" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_overtime_date" class="form-label">Overtime Date</label>
                        <input type="date" class="form-control" id="edit_overtime_date" name="overtime_date" required>
                        <div class="invalid-feedback">Please select a valid date.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                        <div class="invalid-feedback">Please select a start time.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        <div class="invalid-feedback">Please select an end time that is after the start time.</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="edit_reason" name="reason" rows="3" required></textarea>
                        <div class="invalid-feedback">Please provide a reason.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div class="modal fade" id="respondOvertimeModal" tabindex="-1" aria-labelledby="respondOvertimeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="respondOvertimeModalLabel">Respond to Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="respondOvertimeForm" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="response_type" name="response_type">

                    <div class="mb-3">
                        <label class="form-label d-block">Response</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="status" id="approve" value="approved" required>
                            <label class="btn btn-outline-success" for="approve">
                                <i class="fas fa-check"></i> Approve
                            </label>

                            <input type="radio" class="btn-check" name="status" id="reject" value="rejected" required>
                            <label class="btn btn-outline-danger" for="reject">
                                <i class="fas fa-times"></i> Reject
                            </label>
                        </div>
                        <div class="invalid-feedback">Please select a response.</div>
                    </div>

                    <div class="mb-3 d-none" id="rejection_reason_container">
                        <label for="rejection_reason" class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3"></textarea>
                        <div class="invalid-feedback">Please provide a reason for rejection.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modify Response Modal -->
<div class="modal fade" id="modifyResponseModal" tabindex="-1" aria-labelledby="modifyResponseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyResponseModalLabel">Modify Response</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="modifyResponseForm" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="modify_response_type" name="response_type">

                    <div class="mb-3">
                        <label class="form-label d-block">New Response</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="status" id="modify_approve" value="approved" required>
                            <label class="btn btn-outline-success" for="modify_approve">
                                <i class="fas fa-check"></i> Approve
                            </label>

                            <input type="radio" class="btn-check" name="status" id="modify_reject" value="rejected" required>
                            <label class="btn btn-outline-danger" for="modify_reject">
                                <i class="fas fa-times"></i> Reject
                            </label>
                        </div>
                        <div class="invalid-feedback">Please select a response.</div>
                    </div>

                    <div class="mb-3 d-none" id="modify_rejection_reason_container">
                        <label for="modify_rejection_reason" class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="modify_rejection_reason" name="rejection_reason" rows="3"></textarea>
                        <div class="invalid-feedback">Please provide a reason for rejection.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form Validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Create Modal Handler
        const registrationTypeInputs = document.querySelectorAll('input[name="registration_type"]');
        const employeeSelectContainer = document.getElementById('employee_select_container');
        const hiddenUserId = document.getElementById('hidden_user_id');
        const userIdSelect = document.getElementById('user_id');

        if (registrationTypeInputs) {
            registrationTypeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'other') {
                        employeeSelectContainer.classList.remove('d-none');
                        userIdSelect.required = true;
                        hiddenUserId.disabled = true;
                    } else {
                        employeeSelectContainer.classList.add('d-none');
                        userIdSelect.required = false;
                        hiddenUserId.disabled = false;
                    }
                });
            });
        }

        // Edit Modal Handler
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const request = JSON.parse(this.dataset.request);
                const form = document.getElementById('editOvertimeForm');
                form.action = `/overtime-requests/${request.id}`;

                document.getElementById('edit_overtime_date').value = request.overtime_date;
                document.getElementById('edit_start_time').value = request.start_time;
                document.getElementById('edit_end_time').value = request.end_time;
                document.getElementById('edit_reason').value = request.reason;
            });
        });

        // Response Modal Handler
        const responseButtons = document.querySelectorAll('.respond-btn');
        responseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const form = document.getElementById('respondOvertimeForm');

                form.action = `/overtime-requests/${requestId}/${responseType}-status`;
                document.getElementById('response_type').value = responseType;
            });
        });

        // Status Change Handler
        const statusInputs = document.querySelectorAll('input[name="status"]');
        const rejectionContainer = document.getElementById('rejection_reason_container');
        const rejectionInput = document.getElementById('rejection_reason');

        if (statusInputs) {
            statusInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'rejected') {
                        rejectionContainer.classList.remove('d-none');
                        rejectionInput.required = true;
                    } else {
                        rejectionContainer.classList.add('d-none');
                        rejectionInput.required = false;
                    }
                });
            });
        }

        // Delete Confirmation
        const deleteForms = document.querySelectorAll('.delete-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this request?')) {
                    e.preventDefault();
                }
            });
        });

        // Modify Response Handler
        const modifyResponseButtons = document.querySelectorAll('.modify-response-btn');
        modifyResponseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const responseType = this.dataset.responseType;
                const currentStatus = this.dataset.currentStatus;
                const currentReason = this.dataset.currentReason;
                const form = document.getElementById('modifyResponseForm');

                form.action = `/overtime-requests/${requestId}/modify-${responseType}-status`;
                document.getElementById('modify_response_type').value = responseType;

                if (currentStatus === 'approved') {
                    document.getElementById('modify_approve').checked = true;
                    document.getElementById('modify_rejection_reason_container').classList.add('d-none');
                    document.getElementById('modify_rejection_reason').required = false;
                } else {
                    document.getElementById('modify_reject').checked = true;
                    document.getElementById('modify_rejection_reason_container').classList.remove('d-none');
                    document.getElementById('modify_rejection_reason').value = currentReason;
                    document.getElementById('modify_rejection_reason').required = true;
                }
            });
        });

        // Modify Status Change Handler
        const modifyStatusInputs = document.querySelectorAll('#modifyResponseForm input[name="status"]');
        const modifyRejectionContainer = document.getElementById('modify_rejection_reason_container');
        const modifyRejectionInput = document.getElementById('modify_rejection_reason');

        if (modifyStatusInputs) {
            modifyStatusInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'rejected') {
                        modifyRejectionContainer.classList.remove('d-none');
                        modifyRejectionInput.required = true;
                    } else {
                        modifyRejectionContainer.classList.add('d-none');
                        modifyRejectionInput.required = false;
                        modifyRejectionInput.value = '';
                    }
                });
            });
        }
    });

    function resetStatus(requestId, type) {
        if (confirm('Are you sure you want to reset this response?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/overtime-requests/${requestId}/reset-${type}-status`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endpush

@endsection