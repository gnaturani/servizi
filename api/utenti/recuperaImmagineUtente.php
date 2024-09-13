<?php

    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, CustomAuthorization');

    header('Content-Type: application/json');

    require_once("../dbconnect.php");
    require_once("operations.php");

    $data = json_decode(file_get_contents('php://input'), true);

    $completeResult = recuperaImmagineUtente($data);

    if ($completeResult->success == 1) {
        echo json_encode($completeResult);
    } else {
        // header("HTTP/1.0 401 Unauthorized");
        // http_response_code(401);
        echo json_encode($completeResult);
    }

?>