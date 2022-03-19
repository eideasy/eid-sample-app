@extends('template')

@section('content')
    <h1>
        Upload the file to be signed
    </h1>

    @if(Session::has('message'))
        <p class="alert {{ Session::get('alert-class', 'alert-info') }}">{{ Session::get('message') }}</p>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data">
        {{csrf_field()}}
        <div class="form-group">
            <label for="unsigned_file">Files to be signed</label>
            <input name="unsigned_file[]" multiple type="file" class="form-control-file" id="unsigned_file">
        </div>
        <div class="form-group">
            <label for="redirect_uri">Optional: Where to redirect after signing completed</label>
            <input name="redirect_uri" type="url" class="form-control" id="redirect_uri">
        </div>
        <div class="row">
            <div class="form-group col-md-6 col-sm-12">
                <label for="simple_email">Optional: E-mail for simple signature</label>
                <input name="simple_email" type="email" class="form-control" id="simple_email">
            </div>
            <div class="form-group col-md-6 col-sm-12">
                <label for="simple_sms">Optional: Phone for SMS simple signature</label>
                <input name="simple_sms" type="text" class="form-control" id="simple_sms">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6 col-sm-12">
                <label for="simple_firstname">Optional: Firstname (unverified)</label>
                <input name="simple_firstname" type="text" class="form-control" id="simple_firstname">
            </div>
            <div class="form-group col-md-6 col-sm-12">
                <label for="simple_lastame">Optional: Lastname (unverified)</label>
                <input name="simple_lastname" type="text" class="form-control" id="simple_lastname">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-4 col-sm-12">
                <label for="pdf_x">Optional: PDF visual X coordinate (A4 max 595)</label>
                <input name="pdf_x" type="number" class="form-control" id="pdf_x">
            </div>
            <div class="form-group col-md-4 col-sm-12">
                <label for="pdf_y">Optional: PDF visual Y coordinate (A4 max 842)</label>
                <input name="pdf_y" type="text" class="form-control" id="pdf_y">
            </div>
            <div class="form-group col-md-4 col-sm-12">
                <label for="pdf_page">Optional: PDF page number</label>
                <input name="pdf_page" type="text" class="form-control" id="pdf_page">
            </div>
        </div>
        <div class="form-group form-check col-md-3">
            <input class="form-check-input" type="checkbox" name="hide_pdf_visual" id="sign-locally">
            <label class="form-check-label" for="hide_pdf_visual">
                Hide PDF visual signature
            </label>
        </div>
        <small>Note on simple signatures: These are legally valid in court but need extra evidence if disputed. You can
            can connect the signer to the document with e-mail, SMS validation or both. Best to be used for low risk
            documents where you do not foresee disputes coming. For important documents use only Qualified Electronic
            Signatures.</small>
        <br>
        <h3>Signature creation process and integration complexity</h3>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="signType" id="sign-externally" value="external" checked>
            <label class="form-check-label" for="sign-externally">
                Sign externally - much easier implementation and everything just works with 2 API-s. Send files to be
                signed, download signed file.
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="signType" id="sign-locally" value="local">
            <label class="form-check-label" for="sign-locally">
                Sign locally - UX fully under your control but more work for you.
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="signType" id="sign-locally" value="digest">
            <label class="form-check-label" for="sign-locally">
                Sign locally using only hashcodes/digests - Good for privacy sensitive applications. Only SHA-256 hashes
                are send out from your application for signature and nobody else has any idea what is being signed
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="signType" id="create-eseal" value="eseal">
            <label class="form-check-label" for="create-eseal">
                Create e-Seal (Sign automatically using configured document signing certificate)
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="signType" id="multisign" value="multisign">
            <label class="form-check-label" for="multisign">
                Request signatures from multiple people. You will be redirected to a page where you can enter the
                names and email addresses of all the signers.
            </label>
        </div>
        <br>

        <h3>Container type</h3>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="containerType" id="asice-selection" value="asice"
                   checked>
            <label class="form-check-label" for="asice-selection">
                Create an .asice container. Very powerful signature type, allows to sign any type of file and multiple
                files at once.
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="containerType" id="pdf-selection" value="pdf">
            <label class="form-check-label" for="pdf-selection">
                Sign PDF. Signature can be verified with Adobe Reader and only one PDF is allowed. Only first uploaded
                file is used and it must be PDF.
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Sign now</button>
    </form>
    <hr>
    <ul>
        <li>ASiC-E using hashcode/digest - Sign locally and file to be signed never leaves your application. XAdES
            signature is created using SHA-256 digests in eID Easy and ASiC-E .asice container assembled locally
            after signing.
        </li>
        <li>PDF PAdES using hashcode/digest - Sign locally and file to be signed leaves your application. CAdES
            signatures is created using SHA-256 digests in eID Easy and later embedded into the PDF. Since PDF
            digital signature manipulation is quite complex then it is using helper applicaiton that you can run in
            your environment https://github.com/eideasy/eideasy-external-pades-digital-signatures
        </li>
    </ul>

@endsection
