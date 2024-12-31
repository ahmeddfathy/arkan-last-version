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
