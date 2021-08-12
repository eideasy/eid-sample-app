<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => ':Attribute tuleb aktsepteerida.',
    'active_url'           => ':Attribute ei ole kehtiv URL.',
    'after'                => ':Attribute peab olema kuupäev pärast :date.',
    'after_or_equal'       => ':Attribute peab olema kuupäev pärast või samastuma :date.',
    'alpha'                => ':Attribute võib sisaldada vaid tähemärke.',
    'alpha_dash'           => ':Attribute võib sisaldada vaid tähti, numbreid ja kriipse.',
    'alpha_num'            => ':Attribute võib sisaldada vaid tähti ja numbreid.',
    'array'                => ':Attribute peab olema massiiv.',
    'before'               => ':Attribute peab olema kuupäev enne :date.',
    'before_or_equal'      => ':Attribute peab olema kuupäev enne või samastuma :date.',
    'between'              => [
        'numeric' => ':Attribute peab olema :min ja :max vahel.',
        'file'    => ':Attribute peab olema :min ja :max kilobaidi vahel.',
        'string'  => ':Attribute peab olema :min ja :max tähemärgi vahel.',
        'array'   => ':Attribute peab olema :min ja :max kirje vahel.',
    ],
    'boolean'              => ':Attribute väli peab olema tõene või väär.',
    'confirmed'            => ':Attribute kinnitus ei vasta.',
    'date'                 => ':Attribute pole kehtiv kuupäev.',
    'date_equals'          => ':Attribute peab olema kuupäev väärtusega :date',
    'date_format'          => ':Attribute ei vasta formaadile :format.',
    'different'            => ':Attribute ja :other peavad olema erinevad.',
    'digits'               => ':Attribute peab olema :digits numbrit.',
    'digits_between'       => ':Attribute peab olema :min ja :max numbri vahel.',
    'dimensions'           => ':Attribute on valed pildi suurused.',
    'distinct'             => ':Attribute väljal on topeltväärtus.',
    'email'                => ':Attribute peab olema kehtiv e-posti aadress.',
    'ends_with'            => 'The :attribute must end with one of the following: :values.',
    'exists'               => 'Valitud :attribute on vigane.',
    'file'                 => ':Attribute peab olema fail.',
    'filled'               => ':Attribute väli on nõutav.',
    'gt'                   => [
        'numeric' => ':Attribute peab olema suurem kui :value',
        'file'    => ':Attribute peab olema suurem kui :value kilobaiti',
        'string'  => ':Attribute peab sisaldama rohkem kui :value tähemärki',
        'array'   => ':Attribute peab sisaldama rohkem kui :value üksust',
    ],
    'gte'                  => [
        'numeric' => ':Attribute peab olema suurem kui :value või samasugune',
        'file'    => ':Attribute peab olema suurem kui :value kilobaiti või sama palju',
        'string'  => ':Attribute peab sisaldama rohkem kui :value tähemärki või sama palju',
        'array'   => ':Attribute peab sisaldama vähemalt :value üksust',
    ],
    'image'                => ':Attribute peab olema pilt.',
    'in'                   => 'Valitud :attribute on vigane.',
    'in_array'             => ':Attribute väli ei eksisteeri :other sees.',
    'integer'              => ':Attribute peab olema täisarv.',
    'ip'                   => ':Attribute peab olema kehtiv IP aadress.',
    'ipv4'                 => ':Attribute peab olema kehtiv IPv4 aadress.',
    'ipv6'                 => ':Attribute peab olema kehtiv IPv6 aadress.',
    'json'                 => ':Attribute peab olema kehtiv JSON string.',
    'lt'                   => [
        'numeric' => ':Attribute peab olema väiksem kui :value',
        'file'    => ':Attribute peab olema väiksem kui :value kilobaiti',
        'string'  => ':Attribute ei tohi ületada :value tähemärki',
        'array'   => ':Attribute peab sisaldama vähem kui :value üksust',
    ],
    'lte'                  => [
        'numeric' => ':Attribute peab olema väiksem kui :value või samasugune',
        'file'    => ':Attribute peab olema väiksem kui :value kilobaiti või sama palju',
        'string'  => ':Attribute peab sisaldama vähem või sama palju :value tähemärke',
        'array'   => ':Attribute ei tohi sisaldada rohkem kui :value üksust',
    ],
    'max'                  => [
        'numeric' => ':Attribute ei tohi olla suurem kui :max.',
        'file'    => ':Attribute ei tohi olla suurem kui :max kilobaiti.',
        'string'  => ':Attribute ei tohi olla suurem kui :max tähemärki.',
        'array'   => ':Attribute ei tohi sisaldada rohkem kui :max kirjet.',
    ],
    'mimes'                => ':Attribute peab olema :values tüüpi.',
    'mimetypes'            => ':Attribute peab olema :values tüüpi.',
    'min'                  => [
        'numeric' => ':Attribute peab olema vähemalt :min.',
        'file'    => ':Attribute peab olema vähemalt :min kilobaiti.',
        'string'  => ':Attribute peab olema vähemalt :min tähemärki.',
        'array'   => ':Attribute peab olema vähemalt :min kirjet.',
    ],
    'not_in'               => 'Valitud :attribute on vigane.',
    'not_regex'            => ':Attribute vorming on vale',
    'numeric'              => ':Attribute peab olema number.',
    'present'              => ':Attribute väli peab olema esindatud.',
    'regex'                => ':Attribute vorming on vigane.',
    'required'             => ':Attribute väli on nõutud.',
    'required_if'          => ':Attribute väli on nõutud, kui :other on :value.',
    'required_unless'      => ':Attribute väli on nõutud, välja arvatud, kui :other on :values.',
    'required_with'        => ':Attribute väli on nõutud, kui :values on esindatud.',
    'required_with_all'    => ':Attribute väli on nõutud, kui :values on esindatud.',
    'required_without'     => ':Attribute väli on nõutud, kui :values ei ole esindatud.',
    'required_without_all' => ':Attribute väli on nõutud, kui ükski :values pole esindatud.',
    'same'                 => ':Attribute ja :other peavad sobima.',
    'size'                 => [
        'numeric' => ':Attribute peab olema :size.',
        'file'    => ':Attribute peab olema :size kilobaiti.',
        'string'  => ':Attribute peab olema :size tähemärki.',
        'array'   => ':Attribute peab sisaldama :size kirjet.',
    ],
    'starts_with'          => ':Attribute peab algama ühega järgmistest: :values',
    'string'               => ':Attribute peab olema string.',
    'timezone'             => ':Attribute peab olema kehtiv tsoon.',
    'unique'               => ':Attribute on juba hõivatud.',
    'uploaded'             => ':Attribute ei õnnestunud laadida.',
    'url'                  => ':Attribute vorming on vigane.',
    'uuid'                 => ':Attribute peab olema õige UUID',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'kohandatud-teade',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'redirect_uri'            => 'redirect uri väärtus',
        'test_name'               => 'testi nimi',
        'test_description'        => 'testi kirjeldus',
        'test_locale'             => 'keel',
        'image'                   => 'pilt',
        'result_text_under_image' => 'tulemuse tekst pildi all',
        'short_text'              => 'lühitekst',
        'idcode'                  => 'isikukood',
        'phone'                   => 'telefoninumber',
        'country'                 => 'riik',
    ],

    'values' => [
        'sign_type' => [
            'smart-id'  => __('smart-id'),
            'id-card'   => __('id-card'),
            'mobile-id' => __('mobile-id'),
        ]
    ],
];
