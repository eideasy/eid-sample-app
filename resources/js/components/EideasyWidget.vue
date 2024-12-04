<script>
import '@eid-easy/eideasy-widget';

export default {
    props: {
        docId: String,
        baseUrl: String,
        clientId: String,
        availableMethods: Array,
    },
    data() {
        return {
            apiEndpoints: {
                base: () => this.baseUrl,
            },
            enabledMethods: {
                signature: this.availableMethods,
            }
        };
    },
    methods: {
        onSuccess(data) {
            this.$emit('success');
        },
        onFail(data) {
            this.$emit('fail');
        },
    },
};
</script>

<template>
    <div class="container">
        <eideasy-widget
            country-code="EE"
            language="en"
            :debug="true"
            :sandbox="true"
            :client-id="clientId"
            :doc-id="docId"
            :api-endpoints.prop="apiEndpoints"
            :enabled-methods.prop="enabledMethods"
            :on-success.prop="(data) => onSuccess(data)"
            :on-fail.prop="(error) => onFail(error)"
        />
    </div>
</template>
