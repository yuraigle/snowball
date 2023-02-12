<template>
    <div class="modal modal fade" id="new_tx_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Новая сделка</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="onSubmit">
                    <div class="modal-body">
                        <div class="row mt-2">
                            <div class="col-6">
                                <label for="deal_type" class="form-label small mb-1">Операция</label>
                                <select class="form-select" id="deal_type" v-model="deal_type"
                                        :class="{ 'is-invalid': v$.deal_type.$errors.length }">
                                    <option value="0" selected>Покупка</option>
                                    <option value="1">Продажа</option>
                                </select>
                                <div v-if="v$.deal_type.$errors.length" class="invalid-feedback">
                                    {{ v$.deal_type.$errors[0].$message }}
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="deal_date" class="form-label small mb-1">Дата сделки</label>
                                <input type="date" class="form-control" id="deal_date" v-model="deal_date"
                                       :class="{ 'is-invalid': v$.deal_date.$errors.length }"/>
                                <div v-if="v$.deal_date.$errors.length" class="invalid-feedback">
                                    {{ v$.deal_date.$errors[0].$message }}
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-5">
                                <label for="amount" class="form-label small mb-1">Количество, шт.</label>
                                <input type="text" class="form-control" id="amount" v-model="amount"
                                       :class="{ 'is-invalid': v$.amount.$errors.length }"/>
                                <div v-if="v$.amount.$errors.length" class="invalid-feedback">
                                    {{ v$.amount.$errors[0].$message }}
                                </div>
                            </div>
                            <div class="col-4">
                                <label for="price" class="form-label small mb-1">
                                    Цена, {{ currencySymbol }}
                                </label>
                                <input type="text" class="form-control" id="price" v-model="price"
                                       :class="{ 'is-invalid': v$.price.$errors.length }"/>
                                <div v-if="v$.price.$errors.length" class="invalid-feedback">
                                    {{ v$.price.$errors[0].$message }}
                                </div>
                            </div>
                            <div class="col-3">
                                <label for="amount" class="form-label small mb-1">&nbsp;</label>
                                <select class="form-select" id="currency" v-model="currency"
                                        :class="{ 'is-invalid': v$.currency.$errors.length }">
                                    <option value="RUB" selected>RUB</option>
                                    <option value="USD">USD</option>
                                </select>
                                <div v-if="v$.currency.$errors.length" class="invalid-feedback">
                                    {{ v$.currency.$errors[0].$message }}
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <label for="commission" class="form-label small mb-1">Комиссия,
                                    {{ currencySymbol }}
                                </label>
                                <input type="text" class="form-control" id="commission" v-model="commission"
                                       :class="{ 'is-invalid': v$.commission.$errors.length }"/>
                                <div v-if="v$.commission.$errors.length" class="invalid-feedback">
                                    {{ v$.commission.$errors[0].$message }}
                                </div>
                            </div>
                            <div class="col-6 text-end" style="margin-top: 38px">
                                <strong>{{ fmtTotal }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import {helpers, required} from "@vuelidate/validators";
import Toastify from "toastify-js";

export default {
    setup() {
        return {v$: useVuelidate()}
    },

    mounted() {
        const myModalEl = document.getElementById('new_tx_modal')
        myModalEl.addEventListener('shown.bs.modal', event => {
            if (this.selected) {
                const x = window.transactions.filter(tx => tx.id === this.selected);
                if (x.length > 0) {
                    const tx = x[0];
                    this.deal_type = tx.deal_type;
                    this.deal_date = tx.deal_date;
                    this.amount = Math.round(tx.amount);
                    this.price = parseFloat(tx.price).toFixed(2);
                    this.currency = tx.currency;
                    this.commission = parseFloat(tx.commission).toFixed(2);
                }
            }
        })
    },

    props: {
        selected: Number,
    },

    data() {
        return {
            deal_type: 0,
            deal_date: new Date().toLocaleDateString('en-CA'),
            amount: undefined,
            price: undefined,
            currency: 'RUB',
            commission: undefined,
        }
    },

    validations() {
        return {
            deal_type: {
                required: helpers.withMessage('Обязательное поле', required)
            },
            deal_date: {
                required: helpers.withMessage('Обязательное поле', required)
            },
            amount: {
                required: helpers.withMessage('Обязательное поле', required)
            },
            price: {
                required: helpers.withMessage('Обязательное поле', required)
            },
            currency: {
                required: helpers.withMessage('Обязательное поле', required)
            },
            commission: {},
        }
    },

    computed: {
        fmtTotal() {
            const a = 1 * this.amount || 0;
            const b = 1 * this.price || 0;
            const c = 1 * this.commission || 0;
            const ttl = a * b + c;

            let fmt = new Intl.NumberFormat('ru-RU', {style: 'currency', currency: 'RUB'});
            if (this.currency === 'USD') {
                fmt = new Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'})
            }
            return fmt.format(ttl);
        },

        currencySymbol() {
            if (this.currency === 'USD') {
                return '$';
            }
            return '₽';
        }
    },

    methods: {
        onSubmit() {
            this.v$.$touch();
            if (this.v$.$errors.length) {
                return;
            }

            const formData = {
                id: this.selected,
                asset_id: window.asset_id,
                deal_type: this.deal_type,
                deal_date: this.deal_date,
                amount: this.amount,
                price: this.price,
                currency: this.currency,
                commission: this.commission,
            };

            axios.post('/transaction', formData)
                .then(res => {
                    if (res.status === 200) {
                        location.reload();
                    }
                })
                .catch(err => {
                    if (err.response.status === 401) {
                        this.showError(err.response.data[0])
                    } else if (err.response.status === 400) {
                        this.showError(Object.values(err.response.data)[0][0])
                    }
                })
        },

        showError(msg) {
            Toastify({
                text: msg,
                close: true,
                gravity: "bottom",
                position: "right",
                style: {background: "#dc3545"},
            }).showToast();
        }
    }
}
</script>
