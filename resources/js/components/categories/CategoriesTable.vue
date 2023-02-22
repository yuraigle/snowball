<template>
    <table class="border small" style="width: 500px">
        <template v-for="c0 in childrenFor(null)" :key="c0.id">
            <CategoriesRow :cat="c0" :level="0" @remove="onDeleteRow(c0.id)"/>
            <DropAreaRow :id="c0['id']" @dropped="onDrop"></DropAreaRow>

            <template v-if="c0['opened']" v-for="c1 in childrenFor(c0.id)" :key="c1.id">
                <CategoriesRow :cat="c1" :level="1" @remove="onDeleteRow(c1.id)"/>
                <DropAreaRow :id="c1['id']" @dropped="onDrop"></DropAreaRow>

                <template v-if="c1['opened']" v-for="c2 in childrenFor(c1.id)" :key="c2.id">
                    <CategoriesRow :cat="c2" :level="2" @remove="onDeleteRow(c2.id)"/>
                    <DropAreaRow :id="c2['id']" @dropped="onDrop"></DropAreaRow>
                </template>
            </template>
        </template>

        <tr class="border-bottom" v-if="addRowShown">
            <td class="c_name" colspan="2">
                <img class="ms-3 me-4 px-1" src="/layout/pie-chart.svg" alt="" width="32"/>
                <input type="text" class="form-control" v-model="addRowName" :class="{ 'is-invalid': addRowNameError }">
            </td>
            <td class="c_weight">
                <input type="text" class="form-control" v-model="addRowTargetWeight" :class="{ 'is-invalid': addRowTargetWeightError }"/>
            </td>
            <td>
                <button
                    class="btn btn-link text-success p-0"
                    type="button"
                    title="Сохранить"
                    @click="onAddRow"
                >
                    <i class="fa-solid fa-fw fa-check"></i>
                </button>
                <button
                    class="btn btn-link text-secondary p-0"
                    type="button"
                    title="Отменить"
                    @click="addRowShown = false"
                >
                    <i class="fa-solid fa-fw fa-xmark"></i>
                </button>
            </td>
        </tr>

        <tr class="bg-secondary bg-opacity-10 bg-gradient">
            <td colspan="2" class="ps-2 py-2">
                <button class="btn btn-outline-primary ms-2" @click="onSubmit">
                    <i class="fa-solid fa-check"></i> Сохранить
                </button>
                <a class="btn btn-outline-secondary ms-2" href="/categories">
                    <i class="fa-solid fa-xmark"></i> Отмена
                </a>
            </td>
            <td colspan="2" class="text-end pe-2">
                <button
                    v-if="!addRowShown"
                    class="btn btn-link px-2"
                    title="Добавить актив или категорию"
                    @click="showAddRow"
                >
                    <i class="fa-solid fa-fw fa-plus"></i>
                </button>
            </td>
        </tr>
    </table>

    <p class="text-muted small mt-4">Перетяните актив за иконку в нужную категорию</p>
</template>

<script>
import CategoriesRow from "./CategoriesRow.vue";
import DropAreaRow from "./DropAreaRow.vue";

export default {
    components: {DropAreaRow, CategoriesRow},

    data() {
        return {
            stats: window.stats,
            addRowShown: false,
            addRowName: '',
            addRowTargetWeight: '0.00',
            addRowNameError: false,
            addRowTargetWeightError: false,
        }
    },

    methods: {
        childrenFor(parentId) {
            return this.stats
                .filter(c => c['parent_id'] === parentId)
                .sort((c1, c2) => c1['ord'] - c2['ord'] || c1['id'] - c2['id'])
        },

        onDrop(v) {
            const a = this.stats.filter(c => +c.id === +v.transfer)[0];
            const b = this.stats.filter(c => +c.id === +v.to)[0];
            a['parent_id'] = b['opened'] ? b['id'] : b['parent_id'];

            let i = 0;
            this.childrenFor(b['parent_id']).forEach(c => {
                if (+c.id !== +a.id) {
                    c.ord = ++i * 2;
                }
            })
            a.ord = b['opened'] ? 0 : b.ord + 1;
        },

        showAddRow() {
            this.addRowName = '';
            this.addRowTargetWeight = '0.00';
            this.addRowNameError = false;
            this.addRowTargetWeightError = false;
            this.addRowShown = true;
        },

        onAddRow() {
            this.addRowNameError = !this.addRowName;
            this.addRowTargetWeightError = !this.addRowTargetWeight
                .match(/(^100(\.0{1,2})?$)|(^([1-9]([0-9])?|0)(\.[0-9]{1,2})?$)$/);

            if (this.addRowNameError && this.addRowTargetWeightError) {
                return;
            }

            this.stats.push({
                aid: null,
                ticker: null,
                icon: 'pie-chart.svg',
                parent_id: null,
                name: this.addRowName,
                target_weight: this.addRowTargetWeight,
                ord: 999,
            });

            this.addRowName = '';
            this.addRowTargetWeight = '0.00';
            this.addRowShown = false;
        },

        onDeleteRow(id) {
            for (let i = 0; i < this.stats.length; i++) {
                if (this.stats[i].id === id) {
                    this.stats.splice(i, 1);
                    i--;
                }
            }
        },

        onSubmit() {
            axios.post('/categories/update', this.stats)
                .then(() => window.location.reload())
                .catch(err => {
                    console.error(err);
                });
        },
    }
}
</script>

<style>
.c_name > img {
    padding: 0 0 5px 0;
}

.c_name > input {
    width: 250px;
    display: inline-block;
    background: #edf1f5;
    border: 1px solid #dee2e6;
}

.c_weight > input {
    width: 75px;
    background: #edf1f5;
    border: 1px solid #dee2e6;
    text-align: right;
}

</style>
