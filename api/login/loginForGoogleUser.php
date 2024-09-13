<?php

header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, CustomAuthorization');

    header('Content-Type: application/json');

    require_once("../dbconnect.php");
    require_once("operations.php");
    require_once("operations_2.php");

    $filters = json_decode(file_get_contents('php://input'), true);

    $completeResult = loginForGoogleUser($filters);

    if ($completeResult->result == 1){
        echo json_encode($completeResult);
    } else {
        echo json_encode($completeResult);
    }



?>