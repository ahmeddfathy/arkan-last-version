@extends('layouts.app')

@section('content')

<div class="container">
<link href="{{ asset('css/overtime-managment.css') }}" rel="stylesheet">


    {{-- Main Content --}}
    @if(Auth::user()->role === 'manager')
<div class="card-body mt-5">
    <form method="GET" action="{{ route('overtime-requests.index') }}" class="row g-3">
   <!-- Employee Name -->
   <div class="col-md-4">
            <label for="employee_name" class="form-label">Employee Name</label>
            <input type="text" class="form-control " style="height: 60%; border-style:dashed"  id="employee_name" name="employee_name"
                value="{{ request('employee_name') }}" placeholder="Search by name..." list="employees_list">

            <!-- Datalist for employee names -->
            <datalist id="employees_list">
                @foreach ($users as $employee)
                    <option value="{{ $employee->name }}"></option>
                @endforeach
            </datalist>
        </div>

        <!-- Status -->
        <div class="col-md-4">
            <label for="status" class="form-label">Status</label>
            <select class="form-select h-auto" id="status" name="status">
                <option value="">All Statuses</option>
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption }}" {{ request('status') === $statusOption ? 'selected' : '' }}>
                        {{ ucfirst($statusOption) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">
                <i class="fas fa-search"></i> Filter
            </button>
            <a href="{{ route('overtime-requests.index') }}" class="btn btn-secondary">
                <i class="fas fa-undo"></i> Reset
            </a>
        </div>
    </form>
</div>
@endif

    <div class="row justify-content-center mt-5">
        <div class="col-md-12">
            <div class="card shadow-sm">
                {{-- Card Header --}}
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i> Overtime Requests
                    </h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createOvertimeModal">
                        <i class="fas fa-plus"></i> New Overtime Request
                    </button>
                </div>

                {{-- Card Body --}}
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Overtime Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Rejection Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                <tr class="request-row">
                                    <td>{{ $request->user->name ?? 'Unknown User' }}</td>
                                    <td>{{ $request->overtime_date }}</td>
                                    <td>{{ $request->start_time }}</td>
                                    <td>{{ $request->end_time }}</td>
                                    <td>{{ $request->reason }}</td>
                                    <td>
                                        <span class="badge bg-{{ $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $request->rejection_reason }}</td>
                                    <td>
                                        {{-- Action Buttons --}}

                                        @if($request->status === 'pending')
                                        @if(Auth::user()->id === $request->user_id)
                                        <button class="btn btn-sm btn-primary edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editOvertimeModal"
                                            data-request="{{ json_encode($request) }}">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form action="{{ route('overtime-requests.destroy', $request->id) }}"
                                            method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this overtime request?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif

                                        @endif

                                        @if(Auth::user()->role === 'manager')
                                        @if($request->status === 'pending')
                                        <button class="btn btn-sm btn-info respond-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#respondModal"
                                            data-request-id="{{ $request->id }}">
                                            <i class="fas fa-reply"></i> Respond
                                        </button>
                                        @endif

                                        @if($request->status !== 'pending')
                                        <form action="{{ route('overtime-requests.reset-status', $request) }}"
                                            method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn btn-sm btn-secondary"
                                                onclick="return confirm('Are you sure you want to reset this request to pending?')">
                                                <i class="fas fa-undo"></i> Reset
                                            </button>
                                        </form>
                                        @endif

                                        @if($request->status === 'approved' || $request->status === 'rejected')
                                        <button class="btn btn-sm btn-warning modify-response-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modifyResponseModal"
                                            data-request-id="{{ $request->id }}"
                                            data-status="{{ $request->status }}"
                                            data-reason="{{ $request->rejection_reason }}">
                                            <i class="fas fa-edit"></i> Modify
                                        </button>
                                        @endif
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No overtime requests found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Create Overtime Modal --}}
    <div class="modal fade" id="createOvertimeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('overtime-requests.store') }}" method="POST" id="createOvertimeForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">New Overtime Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if(Auth::user()->role === 'manager')
                        <div class="mb-4">
                            <label class="form-label">Request Type</label>
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

                        <div class="mb-4 d-none" id="employee_select_container">
                            <label for="user_id" class="form-label required">Select Employee</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="" disabled selected>Choose an employee...</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="user_id" id="hidden_user_id" value="{{ Auth::id() }}">
                        @endif


                        <div class="mb-3">
                            <label for="overtime_date" class="form-label">Overtime Date</label>
                            <input type="date" class="form-control" id="overtime_date" name="overtime_date" required
                                min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                        </div>
                        <div class="mb-3">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="reason" name="reason" required maxlength="255"></textarea>
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

    {{-- Edit Overtime Modal --}}
    <div class="modal fade" id="editOvertimeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editOvertimeForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Overtime Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_overtime_date" class="form-label">Overtime Date</label>
                            <input type="date" class="form-control" id="edit_overtime_date" name="overtime_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="edit_reason" name="reason" required></textarea>
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

    {{-- Respond Modal --}}
    <div class="modal fade" id="respondModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="respondForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Respond to Overtime Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="status" value="approved" id="approveOption" required>
                            <label class="form-check-label" for="approveOption">Approve</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="status" value="rejected" id="rejectOption">
                            <label class="form-check-label" for="rejectOption">Reject</label>
                        </div>

                        <div id="rejection_reason_div" style="display: none;">
                            <label for="rejection_reason" class="form-label">Rejection Reason</label>
                            <textarea name="rejection_reason" id="rejection_reason" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modify Response Modal --}}
    <div class="modal fade" id="modifyResponseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="modifyResponseForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Modify Response</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="modify_status" class="form-label">Status</label>
                            <select name="status" id="modify_status" class="form-select" required>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3" id="modify_reason_container" style="display: none;">
                            <label for="modify_reason" class="form-label">Rejection Reason</label>
                            <textarea name="rejection_reason" id="modify_reason" class="form-control" rows="3" maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Response</button>
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


        // Toggle rejection reason visibility
        function toggleRejectionReason(statusElement, reasonContainer) {
            const rejectionContainer = document.getElementById(reasonContainer);
            if (rejectionContainer) {
                const rejectionTextarea = rejectionContainer.querySelector('textarea');
                const isRejected = statusElement.value === 'rejected' ||
                    (statusElement.type === 'radio' && statusElement.id === 'rejectOption' && statusElement.checked);

                rejectionContainer.style.display = isRejected ? 'block' : 'none';
                if (rejectionTextarea) {
                    rejectionTextarea.required = isRejected;
                    if (!isRejected) {
                        rejectionTextarea.value = '';
                    }
                }
            }
        }

        // Initialize form handlers
        function initializeFormHandlers() {
            // Edit request handler
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const request = JSON.parse(this.dataset.request);

                    console.log(request); // This will show the request object in the browser console
                    if (!request) {
                        alert('No data passed to modal');
                    }
                    const form = document.getElementById('editOvertimeForm');
                    form.action = `/overtime-requests/${request.id}`;

                    document.getElementById('edit_overtime_date').value = request.overtime_date;
                    document.getElementById('edit_start_time').value = request.start_time;
                    document.getElementById('edit_end_time').value = request.end_time;
                    document.getElementById('edit_reason').value = request.reason;
                });
            });



            // Respond handler
            document.querySelectorAll('.respond-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.dataset.requestId;
                    const form = document.getElementById('respondForm');
                    form.action = `/overtime-requests/${requestId}/respond`;
                });
            });

            // Modify response handler
            document.querySelectorAll('.modify-response-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.dataset.requestId;
                    const form = document.getElementById('modifyResponseForm');
                    form.action = `/overtime-requests/${requestId}/modify`;

                    const status = this.dataset.status;
                    const reason = this.dataset.reason;

                    document.getElementById('modify_status').value = status;
                    const reasonTextarea = document.getElementById('modify_reason');
                    if (reasonTextarea) {
                        reasonTextarea.value = reason || '';
                    }

                    toggleRejectionReason(document.getElementById('modify_status'), 'modify_reason_container');
                });
            });
        }

        // Initialize status change handlers
        function initializeStatusHandlers() {

            const responseStatusInputs = document.querySelectorAll('input[name="status"]');
            responseStatusInputs.forEach(input => {
                input.addEventListener('change', function() {
                    toggleRejectionReason(this, 'rejection_reason_div');
                });
            });

            // Modify response modal status change
            const modifyStatus = document.getElementById('modify_status');
            if (modifyStatus) {
                modifyStatus.addEventListener('change', function() {
                    toggleRejectionReason(this, 'modify_reason_container');
                });
            }
        }

        // Form validation
        function initializeFormValidation() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!this.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    this.classList.add('was-validated');
                });
            });
        }

        // Time validation


        // Initialize all handlers
        initializeFormHandlers();
        initializeStatusHandlers();
        initializeFormValidation();


        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
    // Previous script content remains...

    // Handle registration type change
    const registrationTypeHandler = () => {
        const selfRegistration = document.getElementById('self_registration');
        const otherRegistration = document.getElementById('other_registration');
        const employeeContainer = document.getElementById('employee_select_container');
        const userSelect = document.getElementById('user_id');
        const hiddenUserId = document.getElementById('hidden_user_id');

        if (!selfRegistration || !otherRegistration || !employeeContainer || !userSelect || !hiddenUserId) {
            return;
        }

        const updateUserSelection = () => {
            if (selfRegistration.checked) {
                employeeContainer.classList.add('d-none');
                userSelect.removeAttribute('required');
                userSelect.value = '';
                hiddenUserId.value = '{{ Auth::id() }}';
            } else {
                employeeContainer.classList.remove('d-none');
                userSelect.setAttribute('required', 'required');
                hiddenUserId.value = userSelect.value;
            }
        };

        // Initial setup
        updateUserSelection();

        // Add event listeners
        selfRegistration.addEventListener('change', updateUserSelection);
        otherRegistration.addEventListener('change', updateUserSelection);

        // Handle employee selection change
        userSelect.addEventListener('change', function() {
            if (otherRegistration.checked) {
                hiddenUserId.value = this.value;
            }
        });
    };

    // Initialize the registration type handler
    registrationTypeHandler();
});
</script>
@endpush
@endsection
