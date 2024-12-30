@extends('layouts.app')

<head>
    <style>
        .card {
            opacity: 1 !important;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/user.css') }}">
</head>
@section('content')
<div class="container-fluid px-4">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Search Form -->
    <div class="card search-card mb-4">
        <div class="card-body">
            <form action="{{ route('users.index') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Employee Name</label>
                        <input type="text" class="form-control search-input" name="employee_name"
                            value="{{ request('employee_name') }}" placeholder="Search by name..." list="employees_list">
                        <datalist id="employees_list">
                            @foreach ($employees as $employee)
                            <option value="{{ $employee->name }}">
                                @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select class="form-select search-input" name="department">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->department }}" {{ request('department') == $dept->department ? 'selected' : '' }}>
                                {{ $dept->department }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select search-input" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">User Information</h4>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-file-import"></i> Import Users
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Roles</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->employee_id }}</td>
                            <td>{{ $user->department }}</td>
                            <td>{{ $user->phone_number }}</td>
                            <td>
                                <span class="badge bg-{{ $user->employee_status == 'active' ? 'success' : 'danger' }}">
                                    {{ $user->employee_status }}
                                </span>
                            </td>
                            <td>
                                @foreach($user->roles as $role)
                                <span class="badge bg-info me-1">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-primary"
                                        onclick="openRolesModal({{ $user->id }}, '{{ $user->name }}')"
                                        data-roles='{{ $user->roles ? $user->roles->pluck('name') : '[]' }}'
                                        data-permissions='{{ $user->permissions ? $user->permissions->pluck('name') : '[]' }}'>
                                        <i class="fas fa-user-shield"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning"
                                        onclick="resetToEmployee({{ $user->id }})">
                                        <i class="fas fa-user-tie"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger"
                                        onclick="removeRoles({{ $user->id }})">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No users found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('user.import') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Choose Excel File</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Roles Modal -->
<div class="modal fade" id="rolesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إدارة الأدوار والصلاحيات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rolesForm">
                    <input type="hidden" id="userId">

                    <div class="mb-3">
                        <label class="form-label">الأدوار</label>
                        <select class="form-select" id="roleSelect" onchange="updatePermissionsByRole()">
                            <option value="">اختر دور...</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الصلاحيات</label>
                        <div id="permissionsContainer" class="border p-3 rounded">
                            @foreach($permissions as $permission)
                            <div class="form-check">
                                <input class="form-check-input permission-checkbox"
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->name }}"
                                    id="perm_{{ $permission->name }}">
                                <label class="form-check-label" for="perm_{{ $permission->name }}">
                                    {{ $permission->name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" onclick="saveRolesAndPermissions()">حفظ التغييرات</button>
            </div>
        </div>
    </div>
</div>

<script>
    function removeRoles(userId) {
        if (confirm('هل أنت متأكد من إزالة جميع الأدوار والصلاحيات؟')) {
            $.ajax({
                url: `/users/${userId}/remove-roles`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    }
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء إزالة الأدوار والصلاحيات');
                }
            });
        }
    }

    function resetToEmployee(userId) {
        if (confirm('هل أنت متأكد من إعادة تعيين المستخدم كموظف؟')) {
            $.ajax({
                url: `/users/${userId}/reset-to-employee`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    }
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء إعادة التعيين');
                }
            });
        }
    }

    function openRolesModal(userId, userName) {
        $('#userId').val(userId);
        $('#rolesModal').modal('show');

        try {
            // تحديث الأدوار والصلاحيات الحالية
            const userRoles = JSON.parse($(`button[onclick="openRolesModal(${userId}, '${userName}')"]`).attr('data-roles') || '[]');
            const userPermissions = JSON.parse($(`button[onclick="openRolesModal(${userId}, '${userName}')"]`).attr('data-permissions') || '[]');

            // تحديد الأدوار الحالية
            if (userRoles.length > 0) {
                $('#roleSelect').val(userRoles[0]); // نأخذ أول دور فقط
            } else {
                $('#roleSelect').val('');
            }

            // تحديد الصلاحيات الحالية
            $('.permission-checkbox').prop('checked', false);
            userPermissions.forEach(permission => {
                $(`#perm_${permission}`).prop('checked', true);
            });
        } catch (error) {
            console.error('Error parsing roles/permissions:', error);
            toastr.error('حدث خطأ في تحميل البيانات');
        }
    }

    function updatePermissionsByRole() {
        const selectedRole = $('#roleSelect').val();
        if (!selectedRole) return;

        // طلب AJAX لجلب صلاحيات الدور المحدد
        $.get(`/roles/${selectedRole}/permissions`, function(permissions) {
            $('.permission-checkbox').prop('checked', false);
            permissions.forEach(permission => {
                $(`#perm_${permission}`).prop('checked', true);
            });
        });
    }

    function saveRolesAndPermissions() {
        const userId = $('#userId').val();
        const selectedRole = $('#roleSelect').val();
        const selectedPermissions = $('.permission-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        $.ajax({
            url: `/users/${userId}/roles-permissions`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                roles: [selectedRole],
                permissions: selectedPermissions
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#rolesModal').modal('hide');
                    // تحديث الصفحة لعرض التغييرات
                    location.reload();
                }
            },
            error: function() {
                toastr.error('حدث خطأ أثناء حفظ التغييرات');
            }
        });
    }
</script>@endsection