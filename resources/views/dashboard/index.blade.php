@extends('layouts.base')

@section('content')
    <h1 class="h3">Мой портфель</h1>

    <div id="app">
        <dashboard-page></dashboard-page>
    </div>
@endsection

@section('scripts')
    <script>
        window.categories = @json($categories);
    </script>
@endsection
