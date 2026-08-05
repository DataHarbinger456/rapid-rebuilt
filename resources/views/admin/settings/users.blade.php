@extends('layouts.admin.master')

@section('title')User Management @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>User Management</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
        <li class="breadcrumb-item active">Users</li>
    @endcomponent

    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Users</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                            <i data-feather="user-plus"></i> Add User
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="row mb-4">
                            <div class="col-md-3">
                                <select name="role" class="form-select">
                                    <option value="">All Roles</option>
                                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Customer</option>
                                    <option value="inspector" {{ request('role') === 'inspector' ? 'selected' : '' }}>Inspector</option>
                                    <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">Search</button>
                            </div>
                        </form>

                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td>
                                                <strong>{{ $user->name }}</strong>
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge badge-{{
                                                    $user->role === 'admin' ? 'danger' :
                                                    ($user->role === 'staff' ? 'warning' :
                                                    ($user->role === 'inspector' ? 'info' : 'secondary'))
                                                }}">
                                                    {{ ucfirst($user->role) }}
                                                </span>
                                            </td>
                                            <td>{{ $user->created_at->format('M j, Y') }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editUser({{ $user->id }})">
                                                    <i data-feather="edit-2"></i>
                                                </button>
                                                @if($user->id !== auth()->id())
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser({{ $user->id }})">
                                                        <i data-feather="trash-2"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No users found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($users->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $users->withQueryString()->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.settings.users.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select name="user_type" class="form-select" required>
                                <option value="user">Customer</option>
                                <option value="dealer">Dealer</option>
                                <option value="inspector">Inspector</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function editUser(userId) {
    // Find the row and get user data
    const row = document.querySelector(`button[onclick="editUser(${userId})"]`).closest('tr');
    const name = row.cells[0].querySelector('strong').textContent.trim();
    const email = row.cells[1].textContent.trim();
    const role = row.cells[2].querySelector('.badge').textContent.trim().toLowerCase();

    // Parse first and last name
    const nameParts = name.split(' ');
    const firstName = nameParts[0] || '';
    const lastName = nameParts.slice(1).join(' ') || '';

    // Create edit modal
    const modalHtml = `
        <div class="modal fade" id="editUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editUserForm">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" class="form-control" value="${firstName}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name" class="form-control" value="${lastName}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="${email}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password (leave blank to keep current)</label>
                                <input type="password" name="password" class="form-control" minlength="8">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">User Type</label>
                                <select name="user_type" class="form-select" required>
                                    <option value="user" ${role === 'customer' ? 'selected' : ''}>Customer</option>
                                    <option value="dealer" ${role === 'dealer' ? 'selected' : ''}>Dealer</option>
                                    <option value="inspector" ${role === 'inspector' ? 'selected' : ''}>Inspector</option>
                                    <option value="staff" ${role === 'staff' ? 'selected' : ''}>Staff</option>
                                    <option value="admin" ${role === 'admin' ? 'selected' : ''}>Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`;

    // Remove existing edit modal if any
    const existingModal = document.getElementById('editUserModal');
    if (existingModal) existingModal.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();

    document.getElementById('editUserForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => {
            if (value) data[key] = value;
        });

        fetch(`{{ url('admin/settings/users') }}/${userId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                modal.hide();
                location.reload();
            } else {
                alert(result.message || 'Error updating user');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating user');
        });
    };
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch(`{{ url('admin/settings/users') }}/${userId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                location.reload();
            } else {
                alert(result.message || 'Error deleting user');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting user');
        });
    }
}
</script>
@endpush
