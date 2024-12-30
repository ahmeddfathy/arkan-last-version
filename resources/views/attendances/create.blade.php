@extends('layouts.app')
@section('content')
<head>
    <link href="{{ asset('css/attendances.css') }}" rel="stylesheet">
</head>
<div class="card mt-5">
    <div class="card-header bg-primary text-white">
        <h3>Add New Attendance</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('attendances.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="user_id">Select User</label>
                <select name="user_id" id="user_id" class="form-control">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="check_in_time">Check-in Time</label>
                <input type="datetime-local" name="check_in_time" id="check_in_time" class="form-control" disabled>
            </div>
            <button type="submit" class="btn btn-primary">Save Attendance</button>
        </form>
    </div>
</div>
@endsection
