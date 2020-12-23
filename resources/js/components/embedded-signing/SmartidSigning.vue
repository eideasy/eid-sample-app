<template>
    <div>
        <h2 class="text-center py-3">Smart-ID app login</h2>

        <error-message :message="error" v-if="error!==null"></error-message>

        <div class="row">
            <div class="form-group col-md-5">
                <label for="country">Country</label>
                <select v-model="country" id="country" class="form-control">
                    <option value="EE">Estonia</option>
                    <option value="LV">Latvia</option>
                    <option value="LT">Lithuania</option>
                </select>
            </div>
            <div class="form-group col-md-7">
                <label for="idcode">Identity code</label>
                <input v-model="idcode" class="form-control" id="idcode">
            </div>
        </div>
        <button type="button" class="btn btn-primary" @click="startSmartLogin">Sign</button>
        <div>
            <b-modal v-model="showModal" centered hide-footer hide-header-close title="Waiting for PIN2" hide-backdrop content-class="shadow">
                Please check your phone and enter Smart-ID PIN2 to finish signing.
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
            country: 'EE',
            idcode: null,
            error: null,
            showModal: false,
            challenge: null,
        }
    },
    methods: {
        async startSmartLogin() {
            this.error = null;
            this.userData = null;
            try {
                let startResponse = await axios.post(`https://id${process.env.MIX_EID_CARD_DOMAIN}/api/signatures/start-signing`, {
                    client_id: process.env.MIX_EID_CLIENTID,
                    sign_type: "smart-id",
                    doc_id: this.doc_id,
                    idcode: this.idcode,
                    country: this.country
                });

                this.challenge = startResponse.data.challenge;
                this.showModal = true;

                let signResponse = await axios.post(`https://id${process.env.MIX_EID_CARD_DOMAIN}/api/signatures/sk-smart-id/complete`, {
                    client_id: process.env.MIX_EID_CLIENTID,
                    doc_id: this.doc_id,
                    token: startResponse.data.token
                });

                if (signResponse.data.status !== "OK") {
                    this.error = JSON.stringify(signResponse);
                    console.log(signResponse);
                    toastr.error("Mobile-ID signing failed, see console and server log");
                    return;
                }

                console.log("Smart-ID signature completed: ", signResponse);
                this.showModal = false;
                window.location = "/show-download-signed-file/?file_id=" + this.doc_id;
            } catch (err) {
                this.error = err.response.data.message;
                if (err.response.data.errors) {
                    let errorValues = Object.values(err.response.data.errors);
                    errorValues.forEach(errValue => {
                        this.error = this.error + " " + errValue[0];
                    })
                }
            }
        }
    }
}
</script>

<style scoped>

</style>
