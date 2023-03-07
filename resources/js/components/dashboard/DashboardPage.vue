<template>

    <div class="row">
        <div class="col-4 doughnut_wrapper">
            <div class="legend">{{ formatPrice(totalNow / 1000, 'KRUB', 0) }}K</div>
            <Doughnut :data="chartData" :options="chartOptions" ref="doughnut"/>
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
                    <th style="width: 50px">1D</th>
                    <th style="width: 50px">3D</th>
                    <th style="width: 50px">7D</th>
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
                              @mouseenter="onRowHovered(c)"
                              @mouseleave="onMouseLeave(c)"
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
                    offset: 5,
                    hoverOffset: 30
                }]
            }
        },

        chartOptions() {
            return {
                responsive: true,
                radius: '90%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data
                                    .reduce((a, b) => parseFloat(a) + parseFloat(b));
                                return (parseFloat(context.parsed) / total * 100).toFixed() + '%';
                            }
                        }
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
                return;
            }

            this.parent = c.id
            const chart = this.$refs.doughnut.chart;
            chart.setActiveElements([]);
            chart.tooltip.setActiveElements([]);
            chart.update();
        },

        onRowHovered(c) {
            // console.log('hovered ' + c.id);
            const chart = this.$refs.doughnut.chart;

            let i = 0;
            let ix = 0;
            this.childrenFor(this.parent).forEach(c1 => {
                if (c1.id === c.id) {
                    ix = i;
                }
                i++;
            });

            chart.setActiveElements([{datasetIndex: 0, index: ix}]);
            chart.tooltip.setActiveElements([{datasetIndex: 0, index: ix}]);
            chart.update();
        },

        onMouseLeave() {
            const chart = this.$refs.doughnut.chart;
            chart.setActiveElements([]);
            chart.tooltip.setActiveElements([]);
            chart.update();
        },

        formatPrice(x, c, n = 2) {
            let fmt;
            if (c === 'RUB') {
                fmt = new Intl.NumberFormat('ru-RU', {
                    style: 'currency',
                    currency: 'RUB',
                    maximumFractionDigits: n
                });
            } else if (c === 'USD') {
                fmt = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                    maximumFractionDigits: n
                })
            } else {
                fmt = new Intl.NumberFormat('ru-RU', {
                    maximumFractionDigits: n
                })
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

.doughnut_wrapper {
    position: relative;
}

.legend {
    font-size: 1.8rem;
    font-weight: bold;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}
</style>
