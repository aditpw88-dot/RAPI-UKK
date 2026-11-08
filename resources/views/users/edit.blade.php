@extends('layouts.app')

@section('content')
<div class="user-form-page mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-4">
        <h2 class="text-2xl font-semibold text-gray-900">Edit User</h2>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <div class="max-w-2xl">
            <div class="card rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="card-body p-6">
                    <form action="{{ route('users.update', $user->id) }}" method="POST" data-confirm-action="memperbarui akun pengguna" data-confirm-item="Pengguna: {{ $user->fullname }} ({{ $user->email }})">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="fullname" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('fullname') is-invalid @enderror" id="fullname" name="fullname" value="{{ old('fullname', $user->fullname) }}" required>
                            @error('fullname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password (Kosongkan jika tidak ingin diubah)</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}">
                            @error('phone_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="gender" class="form-label">Jenis Kelamin</label>
                            <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender">
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <option value="Laki-laki" {{ old('gender', $user->gender) == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="Perempuan" {{ old('gender', $user->gender) == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="mapel" class="form-label">Mata Pelajaran (Khusus Guru)</label>
                            <input type="text" class="form-control @error('mapel') is-invalid @enderror" id="mapel" name="mapel" value="{{ old('mapel', $user->mapel) }}" placeholder="Contoh: BAHASA INDONESIA, MATEMATIKA, IPAS">
                            @error('mapel')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                                <option value="">-- Pilih Role --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                        {{ $role->role_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
