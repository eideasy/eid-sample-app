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
        <button type="button" class="btn btn-primary" @click="startSmartLogin">Sign in</button>

        <div v-if="userData">
            <user-data :user-data="userData"></user-data>
        </div>

        <div>
            <b-modal v-model="showModal" centered hide-footer hide-header-close title="Waiting for PIN1" hide-backdrop
                     content-class="shadow">
                Please check your phone and enter Smart-ID PIN1 to continue logging in.
                <br>
                Verification code: <strong>{{challenge}}</strong>
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
                country: 'EE',
                idcode: null,
                error: null,
                showModal: false,
                challenge: null,
                userData: null,
            }
        },
        methods: {
            async startSmartLogin() {
                this.error = null;
                this.userData = null;
                try {
                    let startResponse = await axios.post('/api/identity/smart-id/start', {
                        country: this.country,
                        idcode: this.idcode
                    });

                    this.challenge = startResponse.data.challenge;
                    this.showModal = true;

                    let finishResponse = await axios.post('/api/identity/smart-id/finish', {
                        token: startResponse.data.token
                    });

                    this.userData = finishResponse.data;
                    this.showModal = false;
                } catch (err) {
                    this.error = err.response.data.message;
                }
            }
        }
    }
</script>

<style scoped>

</style>
