@extends('layouts.auth')

@section('title', 'Masuk - PLN Persero')

@section('form')
    <h2>Selamat Datang</h2>
    <p class="subtitle">Masuk ke akun Anda untuk melanjutkan</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <label for="email">Email</label>
            <div class="input-wrap">
                <svg class="leading-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                    <path d="m3 7 9 6 9-6"/>
                </svg>
                <input type="email" id="email" name="email" placeholder="nama@pln.co.id"
                       value="{{ old('email') }}" required autofocus>
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
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                <button type="button" class="toggle-eye" onclick="pln_toggle('password', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            @error('password') <small style="color:#dc2626">{{ $message }}</small> @enderror
        </div>

        <div class="row-between">
            <label class="remember">
                <input type="checkbox" name="remember"> Ingat saya
            </label>
            <a href="{{ route('password.request') }}" class="link">Lupa password?</a>
        </div>

        <button type="submit" class="btn-primary">
            Masuk
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>
    </form>
@endsection

@push('scripts')
<script>
    function pln_toggle(id, btn) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>
@endpush