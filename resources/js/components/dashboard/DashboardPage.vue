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
                <DashboardRow v-for="c in childrenFor(parent)"
                              :c="c"
                              :key="c.id"
                              :total-now="totalNow"
                              :cnt-children="childrenFor(c.id).length"
                              @clicked="onRowClicked(c)"
                />
            </table>
        </div>
    </div>
</template>

<script>
import {ArcElement, Chart as ChartJS, Colors, Tooltip} from 'chart.js';
import {Doughnut} from 'vue-chartjs';
import DashboardRow from "./DashboardRow.vue";

ChartJS.register(ArcElement, Tooltip, Colors);

export default {
    components: {
        DashboardRow,
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

        onRowClicked(c) {
            if (c['ticker']) {
                window.location.href = '/asset/' + c['ticker'];
            } else {
                this.parent = c.id
            }

        }
    },
}
</script>

<style scoped>
tr > td {
    height: 64px;
}
</style>
