@extends('layouts.app')

@section('content')
<link href="{{ asset('css/permission-managment.css') }}" rel="stylesheet">
<div class="container-fluid py-4">
    

    @if(Auth::user()->role === 'manager')
    <div class="card-body ">
        <form method="GET" action="{{ route('permission-requests.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="employee_name" class="form-label">Search Employee</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        id="employee_name"
                        name="employee_name"
                        placeholder="Enter employee name..."
                        value="{{ request('employee_name') }}"
                        list="employee_names">
                </div>
                <datalist id="employee_names">
                    @foreach($users as $user)
                    <option value="{{ $user->name }}">
                        @endforeach
                </datalist>
            </div>


            <div class="col-md-4">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
                <a href="{{ route('permission-requests.index') }}" class="btn btn-light">
                    <i class="fas fa-undo me-2"></i>Reset
                </a>
            </div>
        </form>
    </div>
    @endif

    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white border-0 d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-clock fa-lg me-2"></i>
                        <h5 class="mb-0">Permission Requests</h5>
                    </div>
                    <div class="d-flex align-items-center">
                        @if(Auth::user()->role !== 'manager')
                        <div class="me-3">
                            <div class="d-flex align-items-center bg-opacity-25 rounded-pill px-3 py-1" style="background-color: red;">
                                <i class="fas fa-hourglass-half me-2"></i>
                                <span>Remaining: {{ $remainingMinutes }} minutes</span>
                            </div>
                        </div>
                        @endif
                        <button type="button" class="btn btn-light btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                            <i class="fas fa-plus me-2"></i>
                            <span>New Request</span>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Employee</th>
                                    <th class="border-0">Date & Time</th>
                                    <th class="border-0">Duration</th>
                                    <th class="border-0">Remaining</th>
                                    <th class="border-0">Reason</th>
                                    <th class="border-0">Rejected Reason</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Return Status</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                <tr class="request-row">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <span class="d-block">{{ $request->user->name ?? 'Unknown' }}</span>
                                                <small class="text-muted">
                                                    Remaining: {{ $request->remaining_minutes }} mins
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <small class="text-muted">Departure:</small>
                                            <span>{{ \Carbon\Carbon::parse($request->departure_time)->format('M d, Y H:i') }}</span>
                                            <small class="text-muted">Return:</small>
                                            <span>{{ \Carbon\Carbon::parse($request->return_time)->format('M d, Y H:i') }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $request->minutes_used }} mins</td>
                                    <td>{{ $request->remaining_minutes }} mins</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $request->reason }}">
                                            {{ $request->reason }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($request->status === 'rejected' && $request->rejection_reason)
                                        <div class="mt-1">
                                            <small class="text-danger">
                                                <i class="fas fa-info-circle me-1"></i>
                                                {{ $request->rejection_reason }}
                                            </small>
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                        $statusClass = [
                                        'pending' => 'bg-warning',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger'
                                        ][$request->status] ?? 'bg-secondary';
                                        @endphp
                                        <span class="badge {{ $statusClass }}">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </td>
                                    @if($request->status === 'approved')
                                    <td>
                                        <div class="btn-group" role="group">
                                            <input type="radio"
                                                class="btn-check return-status"
                                                name="return_status_{{ $request->id }}"
                                                id="return_ontime_{{ $request->id }}"
                                                value="1"
                                                {{ $request->returned_on_time === 1 ? 'checked' : '' }}
                                                data-request-id="{{ $request->id }}">
                                            <label class="btn btn-outline-success btn-sm" for="return_ontime_{{ $request->id }}">
                                                <i class="fas fa-check me-1"></i>On Time
                                            </label>

                                            <input type="radio"
                                                class="btn-check return-status"
                                                name="return_status_{{ $request->id }}"
                                                id="return_late_{{ $request->id }}"
                                                value="2"
                                                {{ $request->returned_on_time === 2 ? 'checked' : '' }}
                                                data-request-id="{{ $request->id }}">
                                            <label class="btn btn-outline-danger btn-sm" for="return_late_{{ $request->id }}">
                                                <i class="fas fa-times me-1"></i>Late
                                            </label>

                                            <input type="radio"
                                                class="btn-check return-status"
                                                name="return_status_{{ $request->id }}"
                                                id="return_reset_{{ $request->id }}"
                                                value="0"
                                                {{ $request->returned_on_time === 0 ? 'checked' : '' }}
                                                data-request-id="{{ $request->id }}">
                                            <label class="btn btn-outline-secondary btn-sm" for="return_reset_{{ $request->id }}">
                                                <i class="fas fa-undo me-1"></i>Reset
                                            </label>
                                        </div>
                                    </td>
                                    @else
                                    <td>
                                        <span class="badge bg-secondary">N/A</span>
                                    </td>
                                    @endif

                                    <td>
                                        <div class="btn-group">
                                            @if($request->status === 'pending')
                                            @if(Auth::user()->id === $request->user_id)
                                            <button class="btn btn-sm btn-outline-primary edit-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editPermissionModal"
                                                data-id="{{ $request->id }}"
                                                data-departure="{{ $request->departure_time }}"
                                                data-return="{{ $request->return_time }}"
                                                data-reason="{{ $request->reason }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('permission-requests.destroy', $request) }}"
                                                method="POST"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this request?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                            @if(Auth::user()->role === 'manager')
                                            <button class="btn btn-sm btn-outline-info respond-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#respondModal"
                                                data-request-id="{{ $request->id }}">
                                                <i class="fas fa-reply me-1"></i> Respond
                                            </button>
                                            @endif
                                            @endif
                                            @if(Auth::user()->role === 'manager' && $request->status !== 'pending')
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-warning modify-response-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modifyResponseModal"
                                                    data-request-id="{{ $request->id }}"
                                                    data-status="{{ $request->status }}"
                                                    data-reason="{{ $request->rejection_reason }}">
                                                    <i class="fas fa-edit me-1"></i> Modify
                                                </button>
                                                <form action="{{ route('permission-requests.reset-status', $request) }}"
                                                    method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        onclick="return confirm('Reset this request to pending status?')">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            @endif
                                        </div>
                                    </td>


                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-3"></i>
                                        <p class="mb-0">No permission requests found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('permission-requests.store') }}" method="POST" id="createPermissionForm">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title">New Permission Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if(Auth::user()->role === 'manager')
                    <div class="mb-4">
                        <label class="form-label fw-bold">Request Type</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="registration_type" id="self_registration" value="self" checked>
                            <label class="btn btn-outline-primary" for="self_registration">
                                <i class="fas fa-user me-2"></i>For Myself
                            </label>

                            <input type="radio" class="btn-check" name="registration_type" id="other_registration" value="other">
                            <label class="btn btn-outline-primary" for="other_registration">
                                <i class="fas fa-users me-2"></i>For Employee
                            </label>
                        </div>
                    </div>

                    <div class="mb-4" id="employee_select_container" style="display: none;">
                        <label for="user_id" class="form-label">Select Employee</label>
                        <select name="user_id" id="user_id" class="form-select">
                            <option value="" disabled selected>Choose an employee...</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="departure_time" class="form-label">Departure Time</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="departure_time"
                            name="departure_time"
                            required
                            min="{{ date('Y-m-d\TH:i') }}">
                    </div>

                    <div class="mb-3">
                        <label for="return_time" class="form-label">Return Time</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="return_time"
                            name="return_time"
                            required
                            min="{{ date('Y-m-d\TH:i') }}">
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control"
                            id="reason"
                            name="reason"
                            required
                            rows="3"
                            maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
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
                    <h5 class="modal-title">Edit Permission Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_departure_time" class="form-label">Departure Time</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="edit_departure_time"
                            name="departure_time"
                            required
                            min="{{ date('Y-m-d\TH:i') }}">
                    </div>
                    <div class="mb-3">
                        <label for="edit_return_time" class="form-label">Return Time</label>
                        <input type="datetime-local"
                            class="form-control"
                            id="edit_return_time"
                            name="return_time"
                            required
                            min="{{ date('Y-m-d\TH:i') }}">
                    </div>
                    <div class="mb-3">
                        <label for="edit_reason" class="form-label">Reason</label>
                        <textarea class="form-control"
                            id="edit_reason"
                            name="reason"
                            required
                            rows="3"
                            maxlength="255"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
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
                <div class="modal-header border-0">
                    <h5 class="modal-title">Respond to Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Response Status</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="status" id="status_approved" value="approved" checked>
                            <label class="btn btn-outline-success" for="status_approved">
                                <i class="fas fa-check me-2"></i>Approve
                            </label>

                            <input type="radio" class="btn-check" name="status" id="status_rejected" value="rejected">
                            <label class="btn btn-outline-danger" for="status_rejected">
                                <i class="fas fa-times me-2"></i>Reject
                            </label>
                        </div>
                    </div>

                    <div id="rejection_reason_container" style="display: none;">
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label required">Rejection Reason</label>
                            <textarea class="form-control"
                                id="rejection_reason"
                                name="rejection_reason"
                                rows="3"
                                maxlength="255"
                                placeholder="Please provide a reason for rejection..."></textarea>
                            <div class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                This reason will be visible to the employee
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modifyResponseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="modifyResponseForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0">
                    <h5 class="modal-title">Modify Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label">Update Status</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="status" id="modify_status_approved" value="approved">
                            <label class="btn btn-outline-success" for="modify_status_approved">
                                <i class="fas fa-check me-2"></i>Approved
                            </label>

                            <input type="radio" class="btn-check" name="status" id="modify_status_rejected" value="rejected">
                            <label class="btn btn-outline-danger" for="modify_status_rejected">
                                <i class="fas fa-times me-2"></i>Rejected
                            </label>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="modify_reason_container">
                        <label for="modify_reason" class="form-label required">Rejection Reason</label>
                        <textarea class="form-control"
                            id="modify_reason"
                            name="rejection_reason"
                            rows="3"
                            maxlength="255"
                            placeholder="Please provide a reason for rejection..."></textarea>
                        <div class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            This reason is required and will be shown to the employee
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Handle Edit Button Click
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Get data from button attributes
                const requestId = this.getAttribute('data-id');
                const departureTime = this.getAttribute('data-departure');
                const returnTime = this.getAttribute('data-return');
                const reason = this.getAttribute('data-reason');

                // Set form action URL
                const form = document.getElementById('editPermissionForm');
                form.action = `/permission-requests/${requestId}`;

                const formatDateTime = (dateTimeStr) => {

                    const date = new Date(dateTimeStr);


                    const localDateTime = new Date(date.toLocaleString('en-US', {
                        timeZone: 'Africa/Cairo'
                    }));


                    const year = localDateTime.getFullYear();
                    const month = String(localDateTime.getMonth() + 1).padStart(2, '0');
                    const day = String(localDateTime.getDate()).padStart(2, '0');
                    const hours = String(localDateTime.getHours()).padStart(2, '0');
                    const minutes = String(localDateTime.getMinutes()).padStart(2, '0');

                    return `${year}-${month}-${day}T${hours}:${minutes}`;
                };
                // Populate form fields
                document.getElementById('edit_departure_time').value = formatDateTime(departureTime);
                document.getElementById('edit_return_time').value = formatDateTime(returnTime);
                document.getElementById('edit_reason').value = reason;
            });
        });

        // Handle status radio changes for both respond and modify modals
        ['status', 'modify_status'].forEach(prefix => {
            document.querySelectorAll(`input[name="${prefix}"]`).forEach(radio => {
                radio.addEventListener('change', function() {
                    const containerId = `${prefix === 'status' ? 'rejection' : 'modify'}_reason_container`;
                    const container = document.getElementById(containerId);
                    const textarea = container.querySelector('textarea');

                    if (this.value === 'rejected') {
                        container.style.display = 'block';
                        textarea.setAttribute('required', 'required');
                    } else {
                        container.style.display = 'none';
                        textarea.removeAttribute('required');
                        textarea.value = '';
                    }
                });
            });
        });

        // Handle respond button clicks
        document.querySelectorAll('.respond-btn').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const form = document.getElementById('respondForm');
                form.action = `/permission-requests/${requestId}/update-status`;

                // Reset form
                form.reset();
                document.getElementById('rejection_reason_container').style.display = 'none';
                document.getElementById('rejection_reason').removeAttribute('required');
            });
        });

        // Handle modify response button clicks
        // Handle modify response button clicks
        document.querySelectorAll('.modify-response-btn').forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.dataset.requestId;
                const status = this.dataset.status;
                const reason = this.dataset.reason;

                const form = document.getElementById('modifyResponseForm');
                form.action = `/permission-requests/${requestId}/modify`;

                // Set the correct radio button
                document.getElementById(`modify_status_${status}`).checked = true;

                // Show/hide rejection reason based on status
                const container = document.getElementById('modify_reason_container');
                const textarea = document.getElementById('modify_reason');

                if (status === 'rejected') {
                    container.classList.remove('d-none');
                    textarea.setAttribute('required', 'required');
                    textarea.value = reason || '';
                } else {
                    container.classList.add('d-none');
                    textarea.removeAttribute('required');
                    textarea.value = '';
                }
            });
        });

        // Handle status change in modify response modal
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const container = document.getElementById('modify_reason_container');
                const textarea = document.getElementById('modify_reason');

                if (this.value === 'rejected') {
                    container.classList.remove('d-none');
                    textarea.setAttribute('required', 'required');
                } else {
                    container.classList.add('d-none');
                    textarea.removeAttribute('required');
                    textarea.value = '';
                }
            });
        });
        // Handle employee selection for manager requests
        if (document.getElementById('self_registration')) {
            document.querySelectorAll('input[name="registration_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const userSelect = document.getElementById('user_id');
                    const container = document.getElementById('employee_select_container');

                    if (this.value === 'self') {
                        container.style.display = 'none';
                        userSelect.value = '';
                    } else {
                        container.style.display = 'block';
                        userSelect.value = '';
                    }
                });
            });
        }

        // Initialize form validation
        document.querySelectorAll('.modal form').forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        document.querySelectorAll('.return-status').forEach(radio => {
            radio.addEventListener('change', function() {
                const requestId = this.dataset.requestId;
                const status = this.value;

                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/permission-requests/${requestId}/return-status`;

                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PATCH';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;

                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'return_status';
                statusInput.value = status;

                form.appendChild(methodInput);
                form.appendChild(csrfInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            });
        });
    });
</script>
@endpush
@endsection
