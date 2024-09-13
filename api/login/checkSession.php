<?php

    error_reporting(E_ERROR | E_PARSE);

    session_start();

    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, CustomAuthorization');

    header('Content-Type: application/json');

    require_once("../dbconnect.php");
    require_once("../mainjwt.php");
    require_once("operations.php");

    $completeResult = checkJwtFromHeaders(apache_request_headers());

    $_SESSION['main_user'] = json_decode($completeResult->returnObject);

    echo json_encode($completeResult);

?>