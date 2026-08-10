@extends('layouts.auth')

@section('title', 'Daftar - PLN Persero')

@section('form')
    <h2>Buat Akun Baru</h2>
    <p class="subtitle">Lengkapi data di bawah untuk mendaftar</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="field">
            <label for="name">Nama Lengkap</label>
            <div class="input-wrap">
                <input type="text" id="name" name="name" placeholder="Nama lengkap Anda"
                       style="padding-left:14px" value="{{ old('name') }}" required autofocus>
            </div>
            @error('name') <small style="color:#dc2626">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="email">Email</label>
            <div class="input-wrap">
                <svg class="leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                    <path d="m3 7 9 6 9-6"/>
                </svg>
                <input type="email" id="email" name="email" placeholder="nama@pln.co.id"
                       value="{{ old('email') }}" required>
            </div>
            @error('email') <small style="color:#dc2626">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <svg class="leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="10" width="16" height="10" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>
                <input type="password" id="password" name="password" placeholder="Min. 8 karakter" required>
                <button type="button" class="toggle-eye" onclick="pln_toggle('password')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            @error('password') <small style="color:#dc2626">{{ $message }}</small> @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Konfirmasi Password</label>
            <div class="input-wrap">
                <input type="password" id="password_confirmation" name="password_confirmation"
                       placeholder="Ulangi password Anda" style="padding-left:14px" required>
            </div>
        </div>

        <button type="submit" class="btn-primary">
            Daftar
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>
    </form>

    <p class="auth-footer-text">Sudah punya akun? <a href="{{ route('login') }}" class="link">Masuk</a></p>
@endsection

@push('scripts')
<script>
    function pln_toggle(id) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>
@endpush