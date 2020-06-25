<template>
    <div>
        <h1 class="text-center pb-3">
            Choose identification method
        </h1>

        <div class="row">
            <div class="offset-lg-2 col-lg-3 col-md-4">
                <img src="/img/eid_idkaart_mark.png" class="eid-button" @click="chooseMethod('ee-id-card-login')">
            </div>
            <div class="col-lg-3 col-md-4">
                <img src="/img/eid_mobiilid_mark.png" class="eid-button" @click="chooseMethod('ee-mobile-id-login')">
            </div>
            <div class="col-lg-3 col-md-4">
                <img src="/img/Smart-ID_login_btn.png" class="eid-button" @click="chooseMethod('smart-id-login')">
            </div>
        </div>
        <transition name="bounce" mode="out-in">
            <component :is="method"></component>
        </transition>
    </div>
</template>

<script>
    import Vue from 'vue';

    import SmartidLogin from "./SmartidLogin";
    import EeIdcardLogin from "./EeIdcardLogin";
    import EeMobileIdLogin from "./EeMobileIdLogin";

    Vue.component('smart-id-login', SmartidLogin);
    Vue.component('ee-mobile-id-login', EeMobileIdLogin);
    Vue.component('ee-id-card-login', EeIdcardLogin);

    export default {
        data() {
            return {
                'method': null,
            }
        },
        methods: {
            chooseMethod(method) {
                this.method = method;
            },
            startSmartId() {
                axios.post('/api/')
            }
        }
    }
</script>

<style scoped>
    .bounce-enter-active {
        animation: bounce-in .5s;
    }
    .bounce-leave-active {
        animation: bounce-in .5s reverse;
    }
    @keyframes bounce-in {
        0% {
            transform: scale(0);
        }
        50% {
            transform: scale(1.5);
        }
        100% {
            transform: scale(1);
        }
    }
</style>
