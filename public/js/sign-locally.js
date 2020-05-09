async function startSigning() {
    toastr.success('Start reading certificate');

    // If returns not allowed then not running over HTTPS
    let certificate=null;
    try {
        certificate = await window.hwcrypto.getCertificate({lang: 'en'});
    } catch(err) {
        toastr.error("Getting certificate failed. Are you running over HTTPS?");
        return;
    }

    toastr.success('Certificate acquired, preparing container');
    console.log("certificate is", certificate);
    const hashResponse = await fetch("/api/signatures/create-container-for-signing", {
        method: "POST",
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            certificate: btoa(String.fromCharCode(...certificate.encoded))
        })
    }).then(response => response.json());
    toastr.success('Container prepared, starting has signing on the card');
    console.log("Getting data to sign: ", hashResponse);

    const raw = atob(hashResponse.hash);
    const rawLength = raw.length;
    let dataToSign = new Uint8Array(new ArrayBuffer(rawLength));

    for (let i = 0; i < rawLength; i++) {
        dataToSign[i] = raw.charCodeAt(i);
    }
    console.log("Data to sign bytes are", dataToSign);

    const signature = await window.hwcrypto.sign(certificate, {
        type: 'SHA-256',
        value: dataToSign
    }, {lang: 'en'});
    toastr.success('Signature on the card completed, finalizing signature and getting signed container');

    const signResponse = (await fetch("/api/signatures/finalize-external-signature", {
        method: "POST",
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            containerId: hashResponse.containerId,
            signature: btoa(String.fromCharCode(...signature.value))
        })
    })).then(response => response.json());

    toastr.success('Signed container saved to your Storage folder');
    console.log("Here is signed container: ", signResponse);
}
