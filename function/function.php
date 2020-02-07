<?php
/* API Service
 *
 * BPJS API Caller - RS API Webservice
 * 
 * Copyright, 2020. dr. Diko Aprilio
 */

namespace Exdeus\JWT;

class EX {
    public static function response($token, $message, $code){
        http_response_code($code);
        $response = [
            'response' => [ 'token' => $token ],
            'metadata' => [
                'message' => $message,
                'code' => $code
            ]
        ];
    
        $json_response = json_encode($response ,JSON_PRETTY_PRINT);
        echo $json_response;
    }
}