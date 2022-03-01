<?php

namespace App\Http\Controllers;

use EidEasy\Api\EidEasyApi;

class EidEasyExtendedApi extends EidEasyApi
{
    public function createSigningQueue($clientId, $secret, $docId) {
        $data = [
            'client_id' => $clientId,
            'secret'    => $secret,
            'doc_id'    => $docId,
            'has_management_page' => true,
        ];

        return $this->sendRequest('/api/signatures/signing-queues', $data);
    }
}
