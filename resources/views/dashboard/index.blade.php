@extends('layouts.base')

@section('content')
    <h1>Мой портфель</h1>

    <div class="row">
        <div class="col-lg-4">
            Бублик
        </div>
        <div class="col-lg-8">
            <div class="mb-3">
                <button class="btn btn-sm btn-outline-secondary">&larr; Назад</button>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#new_cat">
                    &plus; Добавить категорию
                </button>
            </div>

            <table class="table">
                <thead class="table-light">
                <tr>
                    <th>Название</th>
                    <th>Стоимость</th>
                    <th>Доход</th>
                    <th>Доля</th>
                    <th></th>
                </tr>
                </thead>
                @foreach($categories as $c)
                    <tr>
                        <td>{{ $c->name }}</td>
                        <td></td>
                        <td></td>
                        <td>
                            {{ $c->target_weight }}%
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#edit_cat"
                                    onclick="editCat({{ $c->id }})">
                                <i class="fa-regular fw fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
                <tfoot class="table-light">
                <tr>
                    <td colspan="3"></td>
                    <td>100%</td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="modal fade" id="new_cat" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Новая категория</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="#">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="new_cat_name" class="form-label">Название</label>
                            <input type="text" class="form-control" name="new_cat[name]" id="new_cat_name">
                        </div>
                        <div class="mb-2">
                            <label for="new_cat_target_weight" class="form-label">Целевая доля</label>
                            <input type="text" class="form-control" name="new_cat[target_weight]" id="new_cat_target_weight">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit_cat" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Редактировать категорию</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="#">
                    @csrf
                    <input type="hidden" name="edit_cat[id]" id="edit_cat_id">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="edit_cat_name" class="form-label">Название</label>
                            <input type="text" class="form-control" name="edit_cat[name]" id="edit_cat_name">
                        </div>
                        <div class="mb-2">
                            <label for="edit_cat_target_weight" class="form-label">Целевая доля</label>
                            <input type="text" class="form-control" name="edit_cat[target_weight]" id="edit_cat_target_weight">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const cats = [];
        @foreach($categories as $c)
            cats[{{ $c->id }}] = @json($c);
        @endforeach

        function editCat(c) {
            document.getElementById('edit_cat_id').value = cats[c]['id'];
            document.getElementById('edit_cat_name').value = cats[c]['name'];
            document.getElementById('edit_cat_target_weight').value = cats[c]['target_weight'];
        }
    </script>
@endsection
