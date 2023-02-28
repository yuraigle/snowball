@extends('layouts.base')

@section('title') - Рекоммендации@endsection

@section('content')
    <h1 class="h3">Рекоммендации</h1>

    <div class="container" style="width: 650px; margin: 0 auto">
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
                <th class="text-center" style="width: 75px">1D</th>
                <th class="text-center" style="width: 75px">7D</th>
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
                    <td></td>
                    <td></td>
                    <td>
                        @if($stat->toBuy > 0)
                            <div>{{ $stat->toBuy * $stat->lot }} шт.</div>
                            <div class="small text-muted">
                                ₽ {{ number_format(floatval($stat->price * $stat->toBuy * $stat->lot), 2) }}
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
            <tfoot>
            <tr class="bg-light">
                <td colspan="5">Сумма покупок:</td>
                <td>₽ {{ number_format(floatval($sumToSpend), 2) }}</td>
            </tr>
            </tfoot>
        </table>

        <button type="button" class="btn btn-outline-secondary" onclick="onClick()">Купил</button>
    </div>

    <style>
        .advice-table td, .advice-table th {
            padding: 4px 8px;
            vertical-align: middle;
            height: 55px;
        }
    </style>

    <script type="text/javascript">
        const stats = @json($stats);
        function onClick() {
            axios.post("/advice/ok", stats)
                .then(res => {
                    window.location.reload();
                })
                .catch(err => {
                    console.error(err);
                })
        }
    </script>
@endsection
