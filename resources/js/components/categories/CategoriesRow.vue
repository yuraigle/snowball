<template>
    <tr>
        <td style="width: 30px">
            <template v-if="!c['ticker']">
                <button v-if="c['opened']" class="btn btn-link text-secondary px-1" @click="c['opened'] = false">
                    <i class="fa fa-fw fa-chevron-down"></i>
                </button>
                <button v-else class="btn btn-link text-secondary px-1" @click="c['opened'] = true">
                    <i class="fa fa-fw fa-chevron-right"></i>
                </button>
            </template>
        </td>
        <td class="c_name">
            <div class="spacer" :style="{ display: 'inline-block', width: `${level * 32}px`}">&nbsp;</div>

            <input type="color" class="color_picker" v-model="c['color']" />

            <template v-if="!c['ticker']">
                <img
                    class="p-0"
                    src="/layout/pie-chart.png"
                    :alt="c['name']"
                    width="32"
                    draggable="true"
                    @dragstart="onDragStart"
                />
                <input type="text" class="form-control" v-model="c['name']" @input="onChange()">
            </template>
            <template v-else>
                <img
                    class="p-0"
                    :src="`/layout/asset-${c['ticker']}.png`"
                    :alt="c['name']"
                    width="32"
                    draggable="true"
                    @dragstart="onDragStart"
                />
                <span style="margin-left: 13px">{{ c['name'] }}</span>
            </template>
        </td>
        <td class="c_weight">
            <input type="text" class="form-control" v-model="c['target_weight']" @input="onChange()">
        </td>
        <td class="text-end edits" style="width: 30px">
            <button
                class="btn btn-link text-dark opacity-50 py-0 px-2"
                type="button"
                :title="`Удалить ${c['ticker'] ? 'актив' : 'категорию'}`"
                @click="$emit('remove')"
            >
                <i class="fa-solid fa-fw fa-xmark"></i>
            </button>
            <button v-if="c['locked']" class="btn btn-link text-dark py-0 px-2 visible" @click="onUnlock"
                    title="Исключён из рекомендаций">
                <i class="fa fa-fw fa-lock"></i>
            </button>
            <button v-else-if="c['ticker']" class="btn btn-link text-dark opacity-50 py-0 px-2" @click="onLock"
                    title="Не рекомендовать к покупке">
                <i class="fa fa-fw fa-lock"></i>
            </button>
        </td>
    </tr>
</template>

<script>
export default {
    props: ['cat', 'level'],
    emits: ['update:modelValue', 'remove'],

    data() {
        return {
            c: this.cat,
        }
    },

    beforeMount() {
        if (!this.c['color']) {
            this.c['color'] = "#" + ((1 << 24) * Math.random() | 0).toString(16).padStart(6, "0")
            this.$emit('update:modelValue', this.c);
        }
    },

    methods: {
        onChange() {
            this.$emit('update:modelValue', this.c)
        },
        onDragStart(e) {
            e.dataTransfer.clearData();
            e.dataTransfer.setData('text/plain', this.c['id']);
        },
        onLock() {
            this.c['locked'] = 1;
            this.$emit('update:modelValue', this.c);
        },
        onUnlock() {
            this.c['locked'] = 0;
            this.$emit('update:modelValue', this.c);
        }
    }

}
</script>


<style scoped>
tr {
    height: 50px;
}

.edits > button {
    visibility: hidden;
}

tr:hover .edits > button {
    visibility: visible;
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

.c_name > img {
    cursor: pointer;
    padding: 0 0 5px 0;
}

.color_picker {
    cursor: pointer;
    width: 9px !important;
    height: 34px;
    vertical-align: middle;
    padding: 0;
    margin-right: 10px;
}

</style>
