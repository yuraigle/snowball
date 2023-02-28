@extends('layouts.base')

@section('title') - Активы@endsection

@section('content')
    <h1 class="h3">Портфель</h1>

    <div id="app">
        <dashboard-page></dashboard-page>
    </div>
@endsection

@section('scripts')
    <script>
        window.stats = @json($stats);
    </script>
@endsection
