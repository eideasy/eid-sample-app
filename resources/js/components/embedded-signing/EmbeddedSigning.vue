<template>
    <div>
        <h1 class="text-center pb-3">
            Choose signing method
        </h1>

        <div class="row">
            <div class="offset-lg-2 col-lg-3 col-md-4">
                <img src="/img/eid_idkaart_mark.png" class="eid-button" @click="chooseMethod('ee-id-card-signing')">
            </div>
            <div class="col-lg-3 col-md-4">
                <img src="/img/eid_mobiilid_mark.png" class="eid-button" @click="chooseMethod('mobile-id-signing')">
            </div>
            <div class="col-lg-3 col-md-4">
                <img src="/img/Smart-ID_login_btn.png" class="eid-button" @click="chooseMethod('smart-id-signing')">
            </div>
        </div>
        <transition name="bounce" mode="out-in">
          <component :is="method"
                     :doc_id="doc_id"
                     :client-id="clientId"
                     :apiUrl="apiUrl"
          ></component>
        </transition>
    </div>
</template>

<script>
import Vue from 'vue';

import SmartidSigning from "./SmartidSigning";
import IdcardSigning from "./EeIdcardSigning";
import MobileIdSigning from "./MobileIdSigning";

Vue.component('smart-id-signing', SmartidSigning);
Vue.component('mobile-id-signing', MobileIdSigning);
Vue.component('ee-id-card-signing', IdcardSigning);

export default {
    props: {
        doc_id: {
          type: String,
        },
        clientId: {
          type: String,
          required: true,
        },
        apiUrl: {
          type: String,
          required: true,
        },
    },
    data() {
        return {
            'method': null,
        }
    },
    methods: {
        chooseMethod(method) {
            this.method = method;
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
