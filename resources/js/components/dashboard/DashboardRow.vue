<template>
    <tr class="border-bottom">
        <td style="width: 4px" :style="{'background-color': c['color']}"></td>
        <td class="text-center" style="width: 50px" role="button" @click="$emit('clicked')">
            <img v-if="c['ticker']" width="36" height="36" class="p-1" :alt="c['ticker']"
                 :src="`/layout/asset-${c['ticker']}.png`"/>
            <img v-else width="36" height="36" class="p-0" :alt="c['name']"
                 :style="{'background-color': c['color']}"
                 src="/layout/pie-chart-1.png"/>
        </td>
        <td role="button" @click="$emit('clicked')">
            <div class="p-0">
                {{ c['name'] }}
            </div>
            <div v-if="c['ticker']" class="small text-muted p-0">
                <span>{{ c['ticker'] }}</span>
                <span v-if="c['cnt']"> &bull; {{ parseFloat(c['cnt']) }} шт.</span>
                <span> &bull; {{ formatPriceExact(c['price'], c['currency']) }}</span>
            </div>
            <div v-else class="small text-muted p-0">
                <span>{{ cntChildren }} шт.</span>
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
</template>

<script>
export default {
    props: ['c', 'totalNow', 'cntChildren'],
    methods: {
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

        formatPriceExact(x, c) {
            let fmt = new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                maximumFractionDigits: 4
            });
            if (c === 'USD') {
                fmt = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                    maximumFractionDigits: 4
                })
            }
            return fmt.format(x);
        }
    }
}
</script>
