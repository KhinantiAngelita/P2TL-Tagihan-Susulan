@extends('layouts.app')
@section('title', 'Upload Laporan')
@section('content')
<div class="card">
    <h2>Upload Laporan Tagihan Susulan (.xls / .xlsx)</h2>
    <form action="{{ route('laporan.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file_excel" accept=".xls,.xlsx" required>
        <br><br>
        <button class="btn" type="submit">Upload &amp; Proses</button>
    </form>
</div>
@endsection
