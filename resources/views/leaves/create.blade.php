@extends('layouts.app')
@section('content')
<head>
    <link href="{{ asset('css/attendances.css') }}" rel="stylesheet">
</head>
<div class="create-attendance-section py-5" data-aos="fade-up">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header  text-white p-4">
                        <h3 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-plus-circle me-2"></i>
                            New Attendance Record
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('leaves.store') }}" method="POST" class="needs-validation" novalidate>
                            @csrf
                            <div class="mb-4">
                                <label for="user_id" class="form-label">Select Employee</label>
                                <select name="user_id" id="user_id" class="form-select form-select-lg" required>
                                    <option value="">Choose an employee...</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    Please select an employee.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="check_in_time" class="form-label">Check-in Time</label>
                                <input type="datetime-local"
                                       name="check_in_time"
                                       id="check_in_time"
                                       class="form-control form-control-lg"
                                       disabled>
                                <small class="text-muted">Current time will be used automatically</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Save Leave
                                </button>
                                <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-arrow-left me-2"></i>Back to List
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
</script>
@endpush
