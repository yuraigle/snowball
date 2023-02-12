<template>
    <div class="row justify-content-center">
        <form class="col-lg-4 col-md-6 card p-4 my-5" method="post" @submit.prevent="onSubmit">
            <div class="input-group mb-2">
                <label for="email" class="input-group-text">
                    <i class="fa-solid fa-fw fa-envelope"></i>
                </label>
                <input type="email" class="form-control" id="email" v-model="email"
                       :class="{ 'is-invalid': v$.email.$errors.length }"/>
                <div v-if="v$.email.$errors.length" class="invalid-feedback">
                    {{ v$.email.$errors[0].$message }}
                </div>
            </div>

            <div class="input-group mb-2">
                <label for="password" class="input-group-text">
                    <i class="fa-solid fa-fw fa-key"></i>
                </label>
                <input type="password" class="form-control" id="password" v-model="password"
                       :class="{ 'is-invalid': v$.password.$errors.length }"/>
                <div v-if="v$.password.$errors.length" class="invalid-feedback">
                    {{ v$.password.$errors[0].$message }}
                </div>
            </div>

            <button type="submit" class="btn btn-outline-primary mb-3">Войти</button>

            <p class="m-0 text-center">Нет аккаунта? <a href="/register">Зарегистрируйтесь!</a></p>
        </form>
    </div>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import {email, helpers, maxLength, minLength, required} from "@vuelidate/validators";
import Toastify from "toastify-js";
import "toastify-js/src/toastify.css";

export default {
    setup() {
        return {v$: useVuelidate()}
    },

    mounted() {
        console.log('Component mounted.')
    },

    data() {
        return {
            email: '',
            password: '',
        }
    },

    validations() {
        return {
            email: {
                required: helpers.withMessage('Обязательное поле', required),
                email: helpers.withMessage('Неправильный формат email', email),
                maxLength: helpers.withMessage('Не длиннее 255 знаков', maxLength(255)),
            },
            password: {
                required: helpers.withMessage('Обязательное поле', required),
                minLength: helpers.withMessage('Не короче 4 знаков', minLength(4)),
            },
        }
    },

    methods: {
        onSubmit() {
            this.v$.$touch();
            if (this.v$.$errors.length) {
                return;
            }

            const formData = {email: this.email, password: this.password};
            axios.post('/login', formData)
                .then(res => {
                    if (res.status === 200) {
                        window.location.href = "/";
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
