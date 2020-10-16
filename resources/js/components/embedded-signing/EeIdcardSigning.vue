<template>

</template>

<script>
export default {
    props: ['doc_id'],
    data() {
        return {
            token: null,
            error: null,
        }
    },
    created() {
        this.idCardSigning();
    },
    methods: {
        async idCardSigning() {
            toastr.success('Start reading certificate');

            let certificate = null;
            try {
                // If result is not_allowed then not running over HTTPS.
                certificate = await window.hwcrypto.getCertificate({lang: 'en'});
            } catch (err) {
                console.log(err);
                toastr.error("Getting certificate failed. Are you running over HTTPS?");
                return;
            }

            toastr.success('Certificate acquired, preparing container');
            console.log("certificate is", certificate);
            const startSignResponse = await fetch("/api/signatures/start-signing", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    sign_type: "id-card",
                    doc_id: this.doc_id,
                    certificate: certificate.hex
                })
            }).then(response => response.json());
            toastr.success('Container prepared, starting hash signing on the card');
            console.log("Getting data to sign: ", startSignResponse);

            const signature = await window.hwcrypto.sign(certificate, {
                type: 'SHA-256',
                hex: startSignResponse.hexDigest
            }, {lang: 'en'});
            toastr.success('Signature on the card completed, finalizing signature and getting signed container');
            console.log("Signature is", signature);

            const signResponse = await fetch(`https://id${process.env.MIX_EID_CARD_DOMAIN}/api/signatures/id-card/complete`, {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    client_id: process.env.MIX_EID_CLIENTID,
                    doc_id: this.doc_id,
                    signature_value: signature.hex
                })
            }).then(response => response.json());

            if (signResponse.status !== "OK") {
                this.error = JSON.stringify(signResponse);
                console.log(signResponse);
                toastr.error("ID card signing failed, see console and server log");
                return;
            }

            console.log("ID signature completed: ", signResponse);

            window.location = "/show-download-signed-file/?file_id=" + this.doc_id;
        }
    }
}
</script>

<style scoped>

</style>
