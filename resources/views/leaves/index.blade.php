@extends('layouts.app')
@section('content')
<head>
    <link href="{{ asset('css/attendances.css') }}" rel="stylesheet">
</head>
<div class="attendance-section py-5" data-aos="fade-up">
    <div class="container">
        <div class="card shadow-lg border-0 rounded-lg">
            <div class="card-header  text-white p-4">
                <h3 class="mb-0 d-flex align-items-center">
                    <i class="bi bi-calendar-check me-2"></i> Leaves Records
                </h3>
            </div>
            <div class="card-body p-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row mb-4">
                    <div class="col-md-6 d-flex align-items-center justify-content-start mb-2 mb-md-0">
                        <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-lg" data-aos="fade-right">
                            <i class="bi bi-plus-circle me-2"></i> Check Out
                        </a>
                    </div>

                </div>

                <div class="table-responsive" data-aos="fade-up">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th scope="col">#</th>
                <th scope="col">User</th>
                <th scope="col">Check-in Time</th>
                <th scope="col">Status</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leaves as $attendance)
                <tr class="align-middle">
                    <td>{{ $attendance->id }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-2">
                                {{ substr($attendance->user->name, 0, 1) }}
                            </div>
                            <div class="text-truncate" style="max-width: 150px;">{{ $attendance->user->name }}</div>
                        </div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i:s') }}</td>
                    <td>
                        <span class="badge bg-success">Present</span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('leaves.show', $attendance->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form action="{{ route('leaves.destroy', $attendance->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this record?')">
                                    <i class="bi bi-trash"></i>
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
