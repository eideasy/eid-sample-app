<template>
    <div>
        <h2 class="text-center py-3">Estonian/Lithuanian Mobile-ID signing</h2>
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
        <button type="button" class="btn btn-primary" @click="startMobileidSigning">Sign</button>

        <div>
            <b-modal v-model="showModal" centered hide-footer hide-header-close title="Waiting for PIN2" hide-backdrop content-class="shadow">
                Please check your phone and enter Mobile-ID PIN2 to finish the signature.
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
    props: ['doc_id'],
    data() {
        return {
            phone: null,
            idcode: null,
            error: null,
            showModal: false,
            challenge: null
        }
    },
    methods: {
        async startMobileidSigning() {
            this.error = null;
            try {
                let startResponse = await axios.post(`${process.env.MIX_EID_API_URL}/api/signatures/start-signing`, {
                    client_id: process.env.MIX_EID_CLIENTID,
                    sign_type: "mobile-id",
                    doc_id: this.doc_id,
                    phone: this.phone,
                    idcode: this.idcode,
                    country: this.country
                });

                this.challenge = startResponse.data.challenge;
                this.showModal = true;

                let signResponse = await axios.post(`${process.env.MIX_EID_API_URL}/api/signatures/sk-mobile-id/complete`, {
                    client_id: process.env.MIX_EID_CLIENTID,
                    doc_id: this.doc_id,
                    token: startResponse.data.token
                });

                this.showModal = false;

                if (signResponse.data.status !== "OK") {
                    this.error = JSON.stringify(signResponse);
                    console.log(signResponse);
                    toastr.error("Mobile-ID signing failed, see console and server log");
                    return;
                }

                console.log("Mobile-ID signature completed: ", signResponse);
                this.showModal = false;
                window.location = "/show-download-signed-file/?doc_id=" + this.doc_id;
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
        country() {
            const numberStart = this.phone.substring(0, 4);
            if (numberStart === "+370") {
                return "LT";
            } else {
                return "EE"
            }
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
