<?php

require_once("resultCalling.php");
require_once 'vendor/autoload.php';

use \Firebase\JWT\JWT;

function getSecretKey()
{
    $secret_key = 'Guid0Naturan1@#millenovecento78';
    return $secret_key;
}

function getJwt($fields)
{

    $tokenId = base64_encode(mcrypt_create_iv(32));
    $issuedAt = time();
    $notBefore = $issuedAt;             //Adding 10 seconds
    $expire = $notBefore + ( 60 * 60 );            // Adding 60 seconds
    $serverName = 'ParrocchiaCarpaneto.com';

    $data = [
        'iat' => $issuedAt,         // Issued at: time when the token was generated
        'jti' => $tokenId,          // Json Token Id: an unique identifier for the token
        'iss' => $serverName,       // Issuer
        'nbf' => $notBefore,        // Not before
        'exp' => $expire,  // Expire

        /*
        'data' => [                  // Data related to the signer user
            'userid' => $fields->id, // userid from the users table
            'id' => $fields->id, // userid from the users table
            'username' => $fields->username, // User name
            'name' => $fields->nome, // User name
            'surname' => $fields->cognome, // User name,
            'apps' => $fields->apps
        ]
        */

        'data' => $fields
    ];

    $jwt = JWT::encode(
        $data,      //Data to be encoded in the JWT
        getSecretKey(), // The signing key
        'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
    );

    return $jwt;
}

function getTokenFromHeaders($requestHeaders = null)
{

    $headers = null;
    if (isset($_SERVER['Customauthorization'])) {
        $headers = trim($_SERVER["Customauthorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
        if (isset($requestHeaders['Customauthorization'])) {
            $headers = trim($requestHeaders['Customauthorization']);
        }
    }

    if ($headers !== null) {

        $authorizationHeader = str_replace('bearer ', '', $headers);
        $token = str_replace('Bearer ', '', $headers);
        // print_r($token);
        return $token;
        //exit();
    }

    if (!isset($requestHeaders['authorization']) && !isset($requestHeaders['Authorization'])) {
        // http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');

        $returnResult = new ServiceResult();
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Manca il token di autorizzazione (1).";
        $returnResult->success = 0;
        echo json_encode($returnResult);
        exit();
    }

    $authorizationHeader = isset($requestHeaders['authorization']) ? $requestHeaders['authorization'] : $requestHeaders['Authorization'];

    if ($authorizationHeader == null) {
        // http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        $returnResult = new ServiceResult();
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Manca il token di autorizzazione (2).";
        $returnResult->success = 0;
        echo json_encode($returnResult);
        exit();
    }

    $authorizationHeader = str_replace('bearer ', '', $authorizationHeader);
    $token = str_replace('Bearer ', '', $authorizationHeader);

    return $token;
}

function checkJwt($jwt = null, $apps = null)
{
    try {

        $token = JWT::decode($jwt, getSecretKey(), array('HS512'));

        // print_r($token);
        // $result = 'test';

        $json_result = json_encode($token->data);
        // print_r(json_encode($json_result->user_id));

        $returnMessage = new ServiceResult();
        $returnMessage->success = 1;
        $returnMessage->returnObject = $json_result;

        if ($apps !== null) {
            $okapp = checkApps($apps, json_decode($json_result)->apps);
            if (!$okapp) {
                $returnMessage->success = 0;
                $returnMessage->returnObject = null;
                $returnMessage->returnMessages = [];
                $returnMessage->returnMessages[] = "Utente NON autorizzato!";
            }
        }

        return $returnMessage;

    } catch (Exception $err) {
        // http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');

        if ($err->getMessage() === 'This token has expired!' || $err->getMessage() === 'Expired token') {

            /*
            echo json_encode(array(
                "message" => "Sessione scaduta. Rifare il Login",
                "messageNr" => "900"
            ));
            */

            $returnResult = new ServiceResult();
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Sessione scaduta. Rifare il Login!";
            $returnResult->success = 0;
            echo json_encode($returnResult);

        } else {
            // echo json_encode(array("message" => $err->getMessage()));

            $returnResult = new ServiceResult();
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = $err->getMessage();
            $returnResult->success = 0;
            echo json_encode($returnResult);
        }

        exit();
    }
}

function checkApps($apps, $user_apps) {
    // print_r($user_apps);

    $authorized = false;
    foreach($apps as $app) {
        foreach( $user_apps as $user_app) {
            if ($user_app->id == $app) {
                $authorized = true;
                return $authorized;
            }
        }
    }

    return false;
}

function checkJwtFromHeaders($request = null, $apps = null)
{
    $token = getTokenFromHeaders($request);

    $returnMessage = checkJwt($token, $apps);
    header('UserData: ' . $returnMessage->returnObject);
    return $returnMessage;
}

?>
