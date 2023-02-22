<template>

    <div class="row">
        <div class="col-4">
            <Doughnut :data="chartData" :options="chartOptions"/>
        </div>
        <div class="col-8">
            <table class="table border">
                <thead class="table-light">
                <tr>
                    <th class="ps-2 text-secondary" colspan="3">
                        <span v-if="parent" role="button" @click="parent = curr['parent_id']">
                            <i class="fa-solid fa-fw fa-arrow-left"></i> {{ curr['name'] }}
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
                <tr class="border-bottom" v-for="c in childrenFor(parent)" :key="c.id">
                    <td style="width: 4px" :style="{'background-color': c['color']}"></td>
                    <td class="text-center" style="width: 50px">
                        <a v-if="c['ticker']" :href="`/asset/${c['ticker']}`" class="text-decoration-none">
                            <img :src="`/layout/asset-${c['ticker']}.png`" width="36" alt=""/>
                        </a>
                        <img v-else src="/layout/pie-chart.png" class="p-0" width="36" alt=""
                             role="button" @click="parent=c.id"/>
                    </td>
                    <td>
                        <div v-if="c['ticker']">
                            <div>
                                <a :href="`/asset/${c['ticker']}`" class="text-decoration-none">
                                    {{ c['name'] }}
                                </a>
                            </div>
                            <div class="small text-muted">
                                <span>{{ c['ticker'] }}</span>
                                <span v-if="c['cnt']"> &bull; {{ parseFloat(c['cnt']) }} шт.</span>
                                <span> &bull; {{ formatPrice(c['price'], c['currency']) }}</span>
                            </div>
                        </div>
                        <div v-else @click="parent=c.id" role="button">
                            <div>{{ c['name'] }}</div>
                            <div class="small text-muted">
                                {{ childrenFor(c.id).length }} шт.
                            </div>
                        </div>
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
import {ArcElement, Chart as ChartJS, Colors, Tooltip} from 'chart.js';
import {Doughnut} from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Colors);

export default {
    components: {
        Doughnut
    },

    data() {
        return {
            stats: window.stats,
            parent: null,
        }
    },

    computed: {
        totalNow() {
            let ttl = 0;
            this.stats
                .filter(c => c['parent_id'] === this.parent)
                .forEach(c => ttl += 1 * c['ttl_now']);
            return ttl;
        },

        curr() {
            const filtered = this.stats
                .filter(c => c['id'] === this.parent);
            return filtered.length ? filtered[0] : null;
        },

        chartData() {
            const labels = [];
            const values = [];
            const colors = [];

            this.childrenFor(this.parent).forEach(c => {
                labels.push(c['name']);
                values.push(c['ttl_now']);
                colors.push(c['color']);
            });

            return {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    hoverOffset: 4
                }]
            }
        },

        chartOptions() {
            return {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                }
            }
        },
    },

    methods: {
        childrenFor(parentId) {
            return this.stats
                .filter(c => c['parent_id'] === parentId)
                .sort((c1, c2) => c1['ord'] - c2['ord'] || c1['id'] - c2['id'])
        },

        formatPercent(x) {
            if (isNaN(x)) {
                return "0%";
            }
            return Math.round(x * 100) / 100 + "%";
        },

        formatPrice(x, c) {
            let fmt = new Intl.NumberFormat('ru-RU', {style: 'currency', currency: 'RUB'});
            if (c === 'USD') {
                fmt = new Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'})
            }
            return fmt.format(x);
        },
    },
}
</script>

<style scoped>
tr > td {
    height: 64px;
}
</style>
