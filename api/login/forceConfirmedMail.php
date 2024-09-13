<?php

    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, CustomAuthorization');

    header('Content-Type: application/json');

    require_once("../dbconnect.php");
    require_once("../mainjwt.php");
    require_once("operations.php");

    $return = checkJwtFromHeaders(apache_request_headers());
    if ($return->success == 0) {
        echo json_encode($return);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    error_log(print_r('Input:', true), 0);
    error_log(print_r($data, true), 0);

    $completeResult = forceConfirmedMail($data);

    echo json_encode($completeResult);

?>