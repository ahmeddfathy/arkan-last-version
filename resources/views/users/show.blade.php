@extends('layouts.app')

@section('content')

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card user-profile-card">
                <!-- User Header -->
                <div class="profile-header">
                    <div class="profile-cover"></div>
                    <div class="profile-avatar">
                        <div class="avatar-circle">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    </div>
                    <div class="profile-info text-center">
                        <h2 class="mb-0">{{ $user->name }}</h2>
                        <p class="text-muted">{{ $user->department }}</p>
                    </div>
                </div>

                <!-- User Details -->
                <div class="card-body pt-0">
                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Email</label>
                                    <p>{{ $user->email }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Employee ID</label>
                                    <p>{{ $user->employee_id }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Phone Number</label>
                                    <p>{{ $user->phone_number }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Age</label>
                                    <p>{{ $user->age }} years old</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Date of Birth</label>
                                    <p>{{ $user->date_of_birth }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <div class="detail-info">
                                    <label>National ID</label>
                                    <p>{{ $user->national_id_number }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="detail-info">
                                    <label>Start Date</label>
                                    <p>{{ $user->start_date_of_employment }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card{
        opacity: 1 !important;
    }
    .user-profile-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .profile-header {
        position: relative;
        padding-bottom: 1rem;
    }

    .profile-cover {
        height: 200px;
        background: linear-gradient(135deg, #2152ff, #21d4fd);
    }

    .profile-avatar {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translate(-50%, 50%);
    }

    .avatar-circle {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #2152ff, #21d4fd);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
        font-weight: bold;
        border: 5px solid white;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .profile-info {
        margin-top: 4rem;
        padding: 1rem;
    }

    .profile-info h2 {
        color: #252f40;
        font-weight: 700;
    }

    .detail-card {
        display: flex;
        align-items: center;
        padding: 1.25rem;
        background: #f8f9fa;
        border-radius: 15px;
        transition: all 0.3s ease;
    }

    .detail-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        background: white;
    }

    .detail-icon {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #2152ff, #21d4fd);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }

    .detail-icon i {
        color: white;
        font-size: 1.2rem;
    }

    .detail-info {
        flex: 1;
    }

    .detail-info label {
        display: block;
        font-size: 0.875rem;
        color: #67748e;
        margin-bottom: 0.25rem;
    }

    .detail-info p {
        margin: 0;
        color: #252f40;
        font-weight: 600;
        font-size: 1rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn i {
        font-size: 1rem;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .col-md-6 {
            padding: 0 0.5rem;
        }

        .detail-card {
            padding: 1rem;
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            font-size: 2rem;
        }
    }
</style>
@endsection
