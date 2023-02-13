<template>

    <div class="row">
        <div class="col-4">Бублик</div>
        <div class="col-8">
            <table class="table border">
                <thead class="table-light">
                <tr>
                    <th class="ps-2 text-secondary">
                        <span v-if="parent" role="button" @click="parent=null">
                            <i class="fa-solid fa-fw fa-arrow-left"></i> Назад
                        </span>
                        <a v-else href="/categories" class="text-secondary text-decoration-none">
                            <i class="fa-regular fa-fw fa-edit"></i> Редактировать
                        </a>
                    </th>
                    <th style="width: 130px">Стоимость</th>
                    <th style="width: 130px">Доход</th>
                    <th style="width: 100px">Доля</th>
                </tr>
                </thead>
                <tr class="border-bottom" v-for="c in stats_filtered">
                    <td class="ps-2">
                        <a v-if="c['ticker']" :href="`/asset/${c['ticker']}`" class="text-decoration-none">
                            <img :src="`/layout/${c['icon']}`" width="36" class="me-2" alt=""/>
                            {{ c['name'] }}
                        </a>
                        <span v-else role="button" @click="parent=c.id">{{ c['name'] }}</span>
                    </td>
                    <td>
                        <div class="p-0 small fw-bold">{{ formatPrice(c['ttl_now'], 'RUB') }}</div>
                        <div class="p-0 small text-muted">{{ formatPrice(c['ttl_spent'], 'RUB') }}</div>
                    </td>
                    <td class="small">
                        <div v-if="c['ttl_now'] > c['ttl_spent']" class="text-success">
                            <div class="p-0 small">
                                +{{ formatPrice(c['ttl_now'] - c['ttl_spent'], 'RUB') }}
                            </div>
                            <div class="p-0 small">
                                <i class="fa-solid fa-fw fa-chevron-up"></i>
                                +{{ formatPercent((c['ttl_now'] - c['ttl_spent']) / c['ttl_spent'] * 100) }}
                            </div>
                        </div>
                        <div v-else-if="c['ttl_now'] < c['ttl_spent']" class="text-danger">
                            <div class="p-0 small">
                                {{ formatPrice(c['ttl_now'] - c['ttl_spent'], 'RUB') }}
                            </div>
                            <div class="p-0 small">
                                <i class="fa-solid fa-fw fa-chevron-down"></i>
                                {{ formatPercent((c['ttl_now'] - c['ttl_spent']) / c['ttl_spent'] * 100) }}
                            </div>
                        </div>
                        <div v-else-if="c['ttl_now'] === c['ttl_spent']">
                            <div class="p-0 small">
                                {{ formatPrice(c['ttl_now'] - c['ttl_spent'], 'RUB') }}
                            </div>
                            <div class="p-0 small">
                                0%
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="p-0 small">
                            {{ formatPercent(c['ttl_now'] / totalNow * 100) }}
                        </div>
                        <div class="p-0 small fw-bold">
                            {{ formatPercent(c['target_weight']) }}
                        </div>
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
            stats: window.stats,
            parent: null,
        }
    },
    computed: {
        stats_filtered() {
            return this.stats.filter(c => c['parent_id'] === this.parent);
        },
        totalNow() {
            let ttl = 0;
            this.stats.filter(c => c['parent_id'] === this.parent)
                .forEach(c => ttl += 1 * c['ttl_now']);
            return ttl;
        }
    },
    methods: {
        formatPercent(x) {
            return Math.round(x * 100) / 100 + "%";
        },

        formatPrice(x, c) {
            let fmt = new Intl.NumberFormat('ru-RU', {style: 'currency', currency: 'RUB'});
            if (c === 'USD') {
                fmt = new Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'})
            }
            return fmt.format(x);
        },
    }
}
</script>
