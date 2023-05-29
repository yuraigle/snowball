@extends('layouts.base')

@section('content')
    <div class="d-flex">
        <div class="pt-1 pe-2">
            <img src="/layout/asset-{{ $asset->ticker }}.png" width="48" alt="{{ $asset->ticker }}"/>
        </div>
        <div class="p-0">
            <h1 class="h3 p-0 m-0">{{ $asset->name }}</h1>
            <div class="text-muted small">{{ $asset->ticker }}</div>
        </div>
    </div>

    @if($stats && $stats->cnt)
        <div class="row my-4">
            <h2 class="h4">В моём портфеле</h2>
            <div class="col-lg-4">
                <div class="border-bottom p-2">
                    <span class="text-muted small">Кол-во:</span>
                    <span class="float-end">{{ floatval($stats->cnt) }} шт.</span>
                </div>
                <div class="border-bottom p-2">
                    <span class="text-muted small">Текущая стоимость:</span>
                    <span class="float-end">
                        @if($asset->currency == 'USD')
                            $ {{ number_format($stats->ttl_now_rub / $usd) }}
                        @endif
                        @if($asset->currency == 'RUB')
                            {{ number_format($stats->ttl_now_rub) }} ₽
                        @endif
                    </span>
                </div>
                <div class="border-bottom p-2">
                    <span class="text-muted small">Вложено:</span>
                    <span class="float-end">
                        @if($asset->currency == 'USD')
                            $
                        @endif
                        {{ number_format($stats->ttl_spent) }}
                        @if($asset->currency == 'RUB')
                            ₽
                        @endif
                    </span>
                </div>
                <div class="border-bottom p-2">
                    <span class="text-muted small">Средняя цена:</span>
                    <span class="float-end">
                        @if($asset->currency == 'USD')
                            $
                        @endif
                        {{ number_format($stats->ttl_spent / $stats->cnt, 2) }}
                        @if($asset->currency == 'RUB')
                            ₽
                        @endif
                    </span>
                </div>
                <div class="border-bottom p-2">
                    <span class="text-muted small">Доля в портфеле:</span>
                    <span class="float-end">{{ number_format($stats->ttl_now_rub / $ttlByUser * 100, 2) }}%</span>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border-bottom p-2">
                    <span class="text-muted small">Прибыль:</span>
                    <span
                        class="float-end {{ $stats->ttl_now_rub > $stats->ttl_spent_rub ? 'text-success' : 'text-danger' }}">
                        {{ number_format($stats->ttl_now_rub - $stats->ttl_spent_rub) }} ₽
                        ( {{ number_format(($stats->ttl_now_rub - $stats->ttl_spent_rub) / $stats->ttl_spent_rub * 100, 2) }}% )
                    </span>
                </div>
                <div class="border-bottom p-2">
                    <span class="text-muted small">Рост цены:</span>
                    <span
                        class="float-end {{ $stats->ttl_now_rub > $stats->ttl_spent_rub ? 'text-success' : 'text-danger' }}">
                        {{ number_format($stats->ttl_now_rub - $stats->ttl_spent_rub) }} ₽
                        ( {{ number_format(($stats->ttl_now_rub - $stats->ttl_spent_rub) / $stats->ttl_spent_rub * 100, 2) }}% )
                    </span>
                </div>
                <div class="border-bottom p-2">
                    <span class="text-muted small">Дивиденды:</span>
                    <span class="float-end">0 ₽</span>
                </div>
            </div>
        </div>
    @endif

    <div id="app">
        <asset-graph></asset-graph>
        <transactions-table></transactions-table>
    </div>

@endsection

@section('scripts')
    <script>
        window.transactions = @json($transactions);
        window.asset_id = {{ $asset->id }};
        window.asset_price = {{ $asset->price }};
        window.asset_currency = @json($asset->currency);
        window.series = @json($series);
        window.avgPrice = {{ $stats->ttl_spent / $stats->cnt }};
    </script>
@endsection
