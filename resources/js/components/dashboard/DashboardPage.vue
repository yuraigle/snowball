<template>

    <div class="row">
        <div class="col-4">Бублик</div>
        <div class="col-8">
            <table class="table border">
                <thead class="table-light">
                <tr>
                    <th class="ps-2 text-secondary">
                        <span role="button" v-if="parent !== null" @click="parent=null">
                            <i class="fa-solid fa-fw fa-arrow-left"></i> Назад
                        </span>
                    </th>
                    <th style="width: 130px">Стоимость</th>
                    <th style="width: 130px">Доход</th>
                    <th style="width: 130px">Доля</th>
                    <th style="width: 50px"></th>
                </tr>
                </thead>
                <tr class="border-bottom" v-for="c in categories_filtered">
                    <td class="ps-2">
                        <a v-if="c['ticker']" :href="`asset/${c['ticker']}`">{{ c['name'] }}</a>
                        <span v-else role="button" @click="parent=c.id">{{ c['name'] }}</span>
                    </td>
                    <td>
                        <div class="p-0 small fw-bold">10 685,20 ₽</div>
                        <div class="p-0 small text-muted">10 380,80 ₽</div>
                    </td>
                    <td class="text-success">
                        <div class="p-0 small">+305,40 ₽</div>
                        <div class="p-0 small">
                            <i class="fa-solid fa-fw fa-chevron-up"></i>
                            {{ formatPercent(2.4564) }}
                        </div>
                    </td>
                    <td>
                        <div class="p-0 small">{{ formatPercent(20.76) }}</div>
                        <div class="p-0 small fw-bold">
                            {{ formatPercent(c['target_weight']) }}
                        </div>
                    </td>
                    <td class="text-end pe-2">
                        <button class="btn btn-sm btn-link" type="button" title="Редактировать">
                            <i class="fa-regular fw fa-pen-to-square"></i>
                        </button>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            categories: window.categories,
            parent: null,
        }
    },
    computed: {
        categories_filtered() {
            return this.categories.filter(c => c['parent_id'] === this.parent);
        }
    },
    methods: {
        formatPercent(x) {
            return Math.round(x * 100) / 100 + "%";
        }
    }
}
</script>
