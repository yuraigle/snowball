<template>
    <table class="border small" style="width: 500px">
        <template v-for="c0 in childrenFor(null)">
            <tr class="border-bottom">
                <td>
                    <div class="drag ps-3 pe-1" draggable="true" @dragstart="dragStart(c0, $event)"
                         :id="'row_' + c0['id']">
                        <i class="fa-solid fa-grip-vertical"></i>
                    </div>
                </td>
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
                <td class="text-end edits" style="width: 50px">
                    <button class="btn btn-link px-2">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </td>
            </tr>
            <tr class="drop-area"
                :class="{ 'drag-over': dropAreaShown === c0['id'] }"
                @dragover.prevent
                @dragenter="dropAreaShown = c0['id']"
                @dragleave="dropAreaShown = null"
                @drop="dragFinish(c0, $event)"
            >
                <td colspan="4"></td>
            </tr>
            <template v-for="c1 in childrenFor(c0['id'])">
                <tr class="border-bottom">
                    <td>
                        <div class="drag ps-3 pe-1" draggable="true" @dragstart="dragStart(c0, $event)">
                            <i class="fa-solid fa-grip-vertical"></i>
                        </div>
                    </td>
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
                    <td class="text-end edits" style="width: 50px">
                        <button class="btn btn-link px-2">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </td>
                </tr>
                <tr class="drop-area"
                    :class="{ 'drag-over': dropAreaShown === c1['id'] }"
                    @dragover.prevent
                    @dragenter="dropAreaShown = c1['id']"
                    @dragleave="dropAreaShown = null"
                    @drop="dragFinish(c1, $event)"
                >
                    <td colspan="4"></td>
                </tr>
            </template>
        </template>
        <tr>
            <td colspan="2">
                <button class="btn btn-outline-primary ms-2">
                    <i class="fa-solid fa-check"></i>
                    Сохранить
                </button>

                <button class="btn btn-outline-secondary ms-2">
                    <i class="fa-solid fa-xmark"></i>
                    Отмена
                </button>
            </td>
            <td colspan="2" class="text-end">
                <button class="btn btn-link px-2">
                    <i class="fa-solid fa-plus"></i>
                </button>
                <button class="btn btn-link px-2">
                    <i class="fa-solid fa-folder-plus"></i>
                </button>
            </td>
        </tr>
    </table>
</template>

<script>
export default {
    data() {
        return {
            stats: window.stats,
            weights: {},
            names: {},
            dropAreaShown: null,
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
        },
        dragStart(c, ev) {
            console.log(c);
            console.log('drag-start');
        },
        dragFinish(c, ev) {
            this.dropAreaShown = null
        },


    }
}
</script>

<style scoped>
tr {
    height: 49px;
}

.edits > button {
    display: none;
}

tr:hover .edits > button {
    display: inline-block;
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

.drag {
    cursor: grab;
}

.drop-area {
    height: 3px !important;
}

.drop-area.drag-over {
    background: #3f8db9;
}
</style>
