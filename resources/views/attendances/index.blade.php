@extends('layouts.app')
<head>
    <link href="{{ asset('css/attendances.css') }}" rel="stylesheet">
</head>
@section('content')
<div class="attendance-section py-5" data-aos="fade-up">
    <div class="container">
        <div class="card shadow-lg border-0 rounded-lg">
            <div class="card-header   p-4">
                <h3 class="mb-0 d-flex align-items-center">
                    <i class="bi bi-calendar-check me-2"></i> Attendance Records
                </h3>
            </div>
            <div class="card-body p-4">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="{{ route('attendances.create') }}" class="btn   btn-lg" style="background-color: #0284C7; color:white" data-aos="fade-right">
                        <i class="bi bi-plus-circle me-2"></i> Add New Attendance
                    </a>

                </div>

                <div class="table-responsive" data-aos="fade-up">
                    <table class="table  align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">User</th>
                                <th scope="col">Check-in Time</th>
                                <th scope="col">actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendances as $attendance)
                            <tr class="align-middle">
                                <td>{{ $attendance->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2">
                                            {{ substr($attendance->user->name, 0, 1) }}
                                        </div>
                                        {{ $attendance->user->name }}
                                    </div>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i:s') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('attendances.show', $attendance->id) }}" class="btn btn-info btn-sm">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <form action="{{ route('attendances.destroy', $attendance->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


