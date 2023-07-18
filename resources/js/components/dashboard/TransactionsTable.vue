<template>
    <div class="my-4">
        <button type="button" class="btn btn-sm btn-outline-primary"
                @click="selected=null"
                data-bs-toggle="modal" data-bs-target="#new_tx_modal">
            <i class="fa-solid fa-fw fa-plus"></i> Добавить сделку
        </button>

        <table class="table mt-2">
            <thead>
            <tr>
                <th>Операция</th>
                <th>Дата</th>
                <th>Количество</th>
                <th>Цена</th>
                <th>Комиссия</th>
                <th>Сумма</th>
                <th>Прибыль</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="tx in transactions">
                <td>
                    <span v-if="tx['deal_type'] === 0" class="text-success">Покупка</span>
                    <span v-else>Продажа</span>
                </td>
                <td>{{ tx['deal_date'] }}</td>
                <td>{{ parseFloat(tx['amount']) }}</td>
                <td>{{ formatPrice(tx['price'], tx['currency']) }}</td>
                <td>{{ formatPrice(tx['commission'], tx['currency']) }}</td>
                <td>{{ formatPrice(tx['amount'] * tx['price'] + 1 * tx['commission'], tx['currency']) }}</td>
                <td>
                    <span v-if="calcProfit(tx['amount'], tx['price'], tx['commission']) >= 0"
                          class="text-success small">
                        <i class="fa-solid fa-fw fa-chevron-up"></i>
                        +{{ formatPrice(calcProfit(tx['amount'], tx['price'], tx['commission']), tx['currency']) }}
                        ( {{ formatPercent(calcProfitPercent(tx['amount'], tx['price'], tx['commission'])) }} )
                    </span>
                    <span v-else class="text-danger small">
                        <i class="fa-solid fa-fw fa-chevron-down"></i>
                        {{ formatPrice(calcProfit(tx['amount'], tx['price'], tx['commission']), tx['currency']) }}
                        ( {{ formatPercent(calcProfitPercent(tx['amount'], tx['price'], tx['commission'])) }} )
                    </span>

                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-link" type="button" title="Редактировать"
                            @click="selected=tx['id']"
                            data-bs-toggle="modal" data-bs-target="#new_tx_modal">
                        <i class="fa-regular fw fa-pen-to-square"></i>
                    </button>
                </td>
            </tr>
            </tbody>
        </table>

        <transaction-form :selected="selected"></transaction-form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            transactions: window.transactions,
            selected: null,
        }
    },

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
            } else if (c === 'CNY') {
                fmt = new Intl.NumberFormat('zh-CN', {style: 'currency', currency: 'CNY'})
            }
            return fmt.format(x);
        },

        calcProfit(a, p, c) {
            return a * (window.asset_price - p) - c;
        },

        calcProfitPercent(a, p, c) {
            return this.calcProfit(a, p, c) / (a * p + 1 * c) * 100;
        }
    }
}
</script>
