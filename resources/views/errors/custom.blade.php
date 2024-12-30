@extends('layouts.app')

@section('content')
<div class="container py-5" data-aos="fade-up">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-danger text-white text-center p-4">
                    <h3>{{ $errorTitle }}</h3>
                </div>
                <div class="card-body p-4">
                    <div class="text-center">
                        <p class="text-muted mb-4">{{ $errorMessage }}</p>
                        <a href="{{ url()->previous() }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        transition: all 0.3s ease;
    }
</style>
@endpush
