<template>
    <div>
        <h2 class="text-center py-3">Estonian/Lithuanian Mobile-ID login</h2>
        <p>API requires to send the full phone number with country code and without spaces. Otherwise API does not know
            if the number is Estonian or Lithuanian</p>
        <error-message :message="error" v-if="error!==null"></error-message>

        <div class="row">
            <div class="form-group col-md-5">
                <label for="phone">Phone</label>
                <input v-model="phone" class="form-control" id="phone" placeholder="+372..."
                       v-bind:class="{ 'is-invalid': !validPhone }">
            </div>
            <div class="form-group col-md-7">
                <label for="idcode">Identity code</label>
                <input v-model="idcode" class="form-control" id="idcode">
            </div>
        </div>
        <button type="button" class="btn btn-primary" @click="startMobileidLogin">Sign in</button>

        <div v-if="userData">
            <user-data :user-data="userData"></user-data>
        </div>

        <div>
            <b-modal v-model="showModal" centered hide-footer hide-header-close title="Waiting for PIN1" hide-backdrop
                     content-class="shadow">
                Please check your phone and enter Mobile-ID PIN1 to continue logging in.
                <br>
                Verification code: <strong>{{ challenge }}</strong>
            </b-modal>
        </div>
    </div>
</template>

<script>
    import Vue from 'vue';
    import {BModal} from 'bootstrap-vue';

    Vue.component('b-modal', BModal);

    export default {
        data() {
            return {
                phone: "",
                idcode: null,
                error: null,
                showModal: false,
                challenge: null,
                userData: null,
            }
        },
        methods: {
            async startMobileidLogin() {
                this.error = null;
                this.userData = null;
                try {
                    let startResponse = await axios.post('/api/identity/start', {
                        phone: this.phone,
                        idcode: this.idcode,
                        method: this.method
                    });

                    this.challenge = startResponse.data.challenge;
                    this.showModal = true;

                    let finishResponse = await axios.post('/api/identity/finish', {
                        token: startResponse.data.token,
                        method: this.method
                    });

                    this.userData = finishResponse.data;
                    this.showModal = false;
                } catch (err) {
                    this.error = err.response.data.message;
                    if (err.response.data.errors) {
                        let errorValues = Object.values(err.response.data.errors);
                        errorValues.forEach(errValue => {
                            this.error = this.error + " " + errValue[0];
                        })
                    }
                    this.showModal = false;
                }
            }
        },
        computed: {
            method() {
                const numberStart = this.phone.substring(0, 4);
                return numberStart === "+370" ? 'lt-mobile-id' : 'mid-login'
            },
            validPhone() {
                if (this.phone === null || this.phone.length < 4) {
                    return false;
                }
                const numberStart = this.phone.substring(0, 4);
                return numberStart === "+372" || numberStart === "+370";
            }
        }
    }
</script>

<style scoped>

</style>
