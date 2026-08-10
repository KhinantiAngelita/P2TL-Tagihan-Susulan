@extends('layouts.auth')

@section('title', 'Lupa Password - PLN Persero')

@section('form')
    <h2>Lupa Password?</h2>
    <p class="subtitle">Masukkan email terdaftar Anda. Kami akan mengirimkan tautan untuk membuat password baru.</p>

    @if (session('status'))
        <p style="background:#e6f7ea;color:#17643a;padding:10px 14px;border-radius:8px;font-size:13px;margin:0 0 20px;">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="field">
            <label for="email">Alamat Email</label>
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

        <button type="submit" class="btn-primary" style="margin-top:8px">
            Kirim Link Reset
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>
    </form>

    <div style="text-align:center">
        <a href="{{ route('login') }}" class="back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M11 18l-6-6 6-6"/>
            </svg>
            Kembali ke Login
        </a>
    </div>
@endsection