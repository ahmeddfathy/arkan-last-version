@extends('layouts.app')
@section('content')
<head>
    <link href="{{ asset('css/attendances.css') }}" rel="stylesheet">
</head>
<div class="leave-details-section py-5" data-aos="fade-up">
    <div class="container">
        <div class="card shadow-lg border-0 rounded-lg">
            <div class="card-header  text-white p-4">
                <h3 class="mb-0 d-flex align-items-center">
                    <i class="bi bi-person-badge me-2"></i> Leave Details
                </h3>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-4 text-center">
                        <div class="avatar-circle-lg mb-3">
                            {{ substr($leave->user->name, 0, 1) }}
                        </div>
                        <h5 class="text-primary">{{ $leave->user->name }}</h5>
                        <p class="text-muted">{{ $leave->user->email }}</p>
                    </div>
                    <div class="col-md-8">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Leave ID:</span>
                                <span>{{ $leave->id }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">User:</span>
                                <span>{{ $leave->user->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Check-in Time:</span>
                                <span>{{ \Carbon\Carbon::parse($leave->check_in_time)->format('d M Y, H:i A') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-bold">Status:</span>
                                <span class="badge bg-success">Present</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <a href="{{ route('leaves.index') }}" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

