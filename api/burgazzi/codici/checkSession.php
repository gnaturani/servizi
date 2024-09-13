<?php

    error_reporting(E_ERROR | E_PARSE);

    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, CustomAuthorization');

    header('Content-Type: application/json');

    require_once("../../dbconnect.php");
    require_once("../../resultCalling.php");
    require_once("jwt_burgazzi.php");
    require_once("operations.php");

    $completeResult = Burgazzi_checkJwtFromHeaders(apache_request_headers());

    echo json_encode($completeResult);

?>