<template>
    <table class="border small" style="width: 500px">
        <template v-for="c0 in childrenFor(null)">
            <tr class="border-bottom">
                <td class="c_name" style="padding-left: 10px">
                    <img v-if="c0['icon']" :src="`/layout/${c0['icon']}`" alt="" width="32" class="me-2"/>
                    <img v-if="!c0['ticker']" src="/layout/pie-chart.svg" alt="" width="32" class="me-2"/>
                    {{ c0['name'] }}
                </td>
                <td class="c_weight">{{ c0['target_weight'] }}</td>
            </tr>
            <template v-for="c1 in childrenFor(c0['id'])">
                <tr class="border-bottom">
                    <td class="c_name" style="padding-left: 30px">
                        <img v-if="c1['icon']" :src="`/layout/${c1['icon']}`" alt="" width="32" class="me-2"/>
                        <img v-if="!c1['ticker']" src="/layout/pie-chart.svg" alt="" width="32" class="me-2"/>
                        {{ c1['name'] }}
                    </td>
                    <td class="c_weight">{{ c1['target_weight'] }}</td>
                </tr>
            </template>
        </template>
    </table>
</template>

<script>
export default {
    data() {
        return {
            stats: window.stats,
        }
    },
    methods: {
        childrenFor(parentId) {
            return this.stats.filter(c => c['parent_id'] === parentId);
        }
    }
}
</script>

<style scoped>
tr {
    height: 49px;
}
.c_name {
    padding: 8px
}

.c_weight {
    padding: 8px;
    text-align: right;
}
</style>
