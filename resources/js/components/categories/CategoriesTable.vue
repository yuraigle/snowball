<template>
    <table class="border small" style="width: 550px">
        <template v-for="c0 in childrenFor(null)">
            <tr class="border-bottom">
                <td v-if="c0['ticker']" class="c_name" style="padding-left: 10px">
                    <img :src="`/layout/${c0['icon']}`" alt="" width="32" class="me-3"/>
                    {{ c0['name'] }}
                </td>
                <td v-else class="c_name" style="padding-left: 10px">
                    <img src="/layout/pie-chart.svg" alt="" width="32" class="me-2"/>
                    <input v-model="names[c0['id']]" type="text" class="form-control">
                </td>
                <td class="c_weight">
                    <input type="text" v-model="weights[c0['id']]" class="form-control">
                </td>
                <td class="text-end" style="width: 100px">
                    <button class="btn btn-link px-2" v-if="!c0['ticker']">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <button class="btn btn-link px-2" v-if="!c0['ticker']">
                        <i class="fa-solid fa-folder-plus"></i>
                    </button>
                </td>
            </tr>
            <template v-for="c1 in childrenFor(c0['id'])">
                <tr class="border-bottom">
                    <td v-if="c1['ticker']" class="c_name" style="padding-left: 30px">
                        <img :src="`/layout/${c1['icon']}`" alt="" width="32" class="me-3"/>
                        {{ c1['name'] }}
                    </td>
                    <td v-else class="c_name" style="padding-left: 30px">
                        <img src="/layout/pie-chart.svg" alt="" width="32" class="me-2"/>
                        <input v-model="names[c1['id']]" type="text" class="form-control">
                    </td>
                    <td class="c_weight">
                        <input type="text" v-model="weights[c1['id']]" class="form-control">
                    </td>
                    <td class="text-end" style="width: 100px">
                        <button class="btn btn-link px-2">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </td>
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
            weights: {},
            names: {},
        }
    },
    mounted() {
        window.stats.forEach(c => {
            this.weights[c['id']] = c['target_weight'];
            this.names[c['id']] = c['name'];
        });
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

.c_weight > input {
    width: 75px;
    text-align: right;
    background: transparent;
    border: 1px solid #fff;
}

.c_weight > input:hover, .c_weight > input:active, .c_weight > input:focus {
    background: #edf1f5;
    border: 1px solid #dee2e6;
}

.c_name > input {
    width: 250px;
    display: inline-block;
    background: transparent;
    border: 1px solid #fff;
}

.c_name > input:hover, .c_name > input:active, .c_name > input:focus {
    background: #edf1f5;
    border: 1px solid #dee2e6;
}
</style>
