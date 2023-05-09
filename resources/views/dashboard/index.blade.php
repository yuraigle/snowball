@extends('layouts.base')

@section('title') - Активы@endsection

@section('content')
    <div class="mb-4 p-2 fw-bold d-flex flex-row justify-content-center">
        <div class="text-primary">USD: {{ number_format($indexes['USDFIX'], 2) }}</div>
        <div class="text-info ms-4">S&P: {{ number_format($indexes['SP500']) }}</div>
        <div class="text-success ms-4">IMOEX: {{ number_format($indexes['IMOEX']) }}</div>
        <div class="text-warning ms-4">BTC: {{ number_format($indexes['BTC']) }}</div>
    </div>
    <div id="app">
        <dashboard-page></dashboard-page>
    </div>
@endsection

@section('scripts')
    <script>
        window.stats = @json($stats);
    </script>
@endsection
