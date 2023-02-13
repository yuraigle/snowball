@extends('layouts.base')

@section('content')
    <h1 class="h3">Категории</h1>

    <div id="app">
        <categories-table></categories-table>
    </div>
@endsection

@section('scripts')
    <script>
        window.stats = @json($stats);
    </script>
@endsection
