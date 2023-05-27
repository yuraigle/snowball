@extends('layouts.base')

@section('title') - Рекоммендации@endsection

@section('content')
    <h1 class="h3">Рекоммендации</h1>

    <div class="container" style="width: 800px; margin: 0 auto">
        <form class="d-flex mb-3">
            <div class="me-2">
                <label for="x" class="col-form-label">Введите сумму пополнения:</label>
            </div>
            <div class="me-2 flex-grow-1">
                <input type="text" class="form-control" id="x" name="x"
                       placeholder="15000" value="{{ $add }}"/>
            </div>
            <div class="">
                <button class="btn btn-primary" type="submit">Рассчитать</button>
            </div>
        </form>

        <table class="advice-table table table-bordered">
            <thead>
            <tr class="bg-light">
                <th class="text-center" style="width: 30px">#</th>
                <th>Актив</th>
                <th>Категория</th>
                <th class="text-center" style="width: 50px">1D</th>
                <th class="text-center" style="width: 50px">7D</th>
                <th class="text-center" style="width: 50px">30D</th>
                <th>Купить</th>
            </tr>
            </thead>
            @php $n = 0 @endphp
            @foreach($stats as $stat)
                <tr class="border-bottom">
                    <td class="text-end">{{ ++$n }}</td>
                    <td>
                        <img src="/layout/asset-{{ $stat->ticker }}.png" width="32" height="32" alt="" class="me-1"/>
                        <a href="/asset/{{ $stat->ticker }}" target="_blank" class="text-decoration-none text-muted">
                            {{ $stat->name }}
                        </a>
                    </td>
                    <td>
                        <span class="small text-muted">{{ $stat->parent_name }}</span>
                    </td>
                    <td>
                        @if(floatval($stat->{'1D'}) > 0)
                            <span class="small text-success">
                                +{{ number_format(floatval($stat->{'1D'}) * 100.0, 2) }}%
                            </span>
                        @elseif(floatval($stat->{'1D'}) < 0)
                            <span class="small text-danger">
                                {{ number_format(floatval($stat->{'1D'}) * 100.0, 2) }}%
                            </span>
                        @else
                            <span class="small text-muted">0.00%</span>
                        @endif
                    </td>
                    <td>
                        @if(floatval($stat->{'7D'}) > 0)
                            <span class="small text-success">
                                +{{ number_format(floatval($stat->{'7D'}) * 100.0, 2) }}%
                            </span>
                        @elseif(floatval($stat->{'7D'}) < 0)
                            <span class="small text-danger">
                                {{ number_format(floatval($stat->{'7D'}) * 100.0, 2) }}%
                            </span>
                        @else
                            <span class="small text-muted">0.00%</span>
                        @endif
                    </td>
                    <td>
                        @if(floatval($stat->{'30D'}) > 0)
                            <span class="small text-success">
                                +{{ number_format(floatval($stat->{'30D'}) * 100.0, 2) }}%
                            </span>
                        @elseif(floatval($stat->{'30D'}) < 0)
                            <span class="small text-danger">
                                {{ number_format(floatval($stat->{'30D'}) * 100.0, 2) }}%
                            </span>
                        @else
                            <span class="small text-muted">0.00%</span>
                        @endif
                    </td>
                    <td>
                        @if($stat->to_add > 0)
                            <div>{{ number_format(floatval($stat->to_add)) }} ₽</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <style>
        .advice-table td, .advice-table th {
            padding: 4px 8px;
            vertical-align: middle;
            height: 55px;
        }
    </style>
@endsection
