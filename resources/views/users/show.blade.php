@extends('layouts.app')

@section('content')
<div class="user-form-page mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-4">
        <h2 class="text-2xl font-semibold text-gray-900">Detail User</h2>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <div class="max-w-2xl">
            <div class="card rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="card-body p-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Nama Lengkap</label>
                        <p class="fw-bold">{{ $user->fullname }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Email</label>
                        <p class="fw-bold">{{ $user->email }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">No. Telepon</label>
                        <p class="fw-bold">{{ $user->phone_number ?? '-' }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Role</label>
                        <p>
                            @if ($user->role)
                                <span class="badge bg-info">{{ $user->role->role_name }}</span>
                            @else
                                <span class="badge bg-secondary">Belum Ditentukan</span>
                            @endif
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Terdaftar Sejak</label>
                        <p class="fw-bold">{{ $user->created_at->format('d-m-Y H:i') }}</p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
