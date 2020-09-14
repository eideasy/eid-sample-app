<template>
    <div>
        <h2 class="text-center py-3">Estonian ID card login starting</h2>
        <error-message :message="error" v-if="error!==null"></error-message>
        <p>
            Please wait, reading the card. Please choose certificate and enter PIN1 when prompt is opened.
        </p>
        <p>
            Make sure the ID card is in the reader and middleware software is installed
        </p>

        <div v-if="userData">
            <user-data :user-data="userData"></user-data>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                token: null,
                error: null,
                userData: null,
            }
        },
        created() {
            this.startReadCard('EE');
        },
        methods: {
            async startReadCard(country) {
                console.log('Starting to read card for country: ' + country);
                if (this.token !== null) {
                    console.log('Already reading card');
                    return;
                }
                this.token = '';
                let cardReadUrl = `https://${country}${process.env.MIX_EID_CARD_DOMAIN}/api/identity/${process.env.MIX_EID_CLIENTID}/read-card`;
                try {
                    let cardReadResponse = await axios.get(cardReadUrl);
                    console.log('Received response for read card', cardReadResponse.data);
                    this.token = cardReadResponse.data.token;

                    let cardVerifyResponse = await axios.post('/api/identity/id-card/finish', {
                        token: this.token,
                        country: country,
                    });
                    console.log('Card verify completed', cardVerifyResponse);

                    this.userData = cardVerifyResponse.data;
                } catch (err) {
                    this.token = null;
                    console.log('Error reading card', err);
                    if(!err.response) {
                        this.error = "Card reading failed, possibly wrong PIN entered";
                        return;
                    }
                    if (!err.response.data) {
                        this.error = "Unknown error. Check API response from developer tools";
                        return;
                    }
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
