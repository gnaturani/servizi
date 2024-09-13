<?php

header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, CustomAuthorization');

    header('Content-Type: application/json');

    require_once("../dbconnect.php");
    require_once("turno.php");
    require_once("operations_2.php");

    /*
    require_once("../mainjwt.php");
    $return = checkJwtFromHeaders(apache_request_headers());
    if ($return->success == 0) {
        echo json_encode($return);
        exit();
    }
    */

    $id = htmlspecialchars($_GET["id"]);
    $completeResult = recuperaStatoTurno($id);

    echo json_encode($completeResult);

?>