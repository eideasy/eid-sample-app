@extends('template')

@section('content')
    <div class="row">
        <h1>
            API Based Flow Using eID Easy Widget
        </h1>
        <p>
            Make sure you are running over https, have ID card in the reader and software installed is using ID card
            signing.
        </p>
    </div>
    <div class="row">
        <div class="col">
            @if(count($files)===0)
                <p>
                    No files prepared for signing
                </p>
            @else
                @foreach($files as $key => $file)
                    <div>
                        <div class="card">
                            <div class="card-body">
                                <h3 class="card-title">Document Preview</h3>
                                <div>
                                    Original file: <a
                                        href="/download-unsigned-file?doc_id={{$doc_id}}&filename={{$file['fileName']}}">{{$file['fileName']}}</a>
                                </div>
                                <object type="{{$file['mimeType']}}"
                                        data="data:{{$file['mimeType']}};base64,{{$file['fileContent']}}" class="w-100"
                                        height="550">
                                    <p>This type of file preview cannot be shown</p>
                                </object>
                            </div>

                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <div class="col col-lg-4">
            <div id="app">
                <div v-if="!isSuccess">
                    <widget
                        :doc-id="'{{ $doc_id }}'"
                        :client-id="'{{ $client_id }}'"
                        :enabled-methods='@json($available_methods)'
                        @success="handleSuccess"
                        @fail="handleFail"
                    ></widget>
                </div>
                <div v-else>
                    <div class="alert alert-success">
                        File was successfully signed!
                    </div>
                    You can download your Digitally signed file here:
                    <a href="{{url("/download-signed-file?file_id=$file_id")}}">
                        {{url("/download-signed-file?file_id=$file_id")}}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="/js/hwcrypto.js"></script>
    <script type="text/javascript" src="{{mix('/js/app.js')}}"></script>
@endsection
