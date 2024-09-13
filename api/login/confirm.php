<?php

    error_reporting(E_ERROR | E_PARSE);

    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, CustomAuthorization');

    header('Content-type: text/html');

    require_once("../dbconnect.php");
    require_once("../mainjwt.php");
    require_once("operations.php");

    $data = $_GET['id'];

    $completeResult = confirm($data);

    if ($completeResult->success == 1) {

        echo "<html>";
        echo "<head>";
        echo "<meta http-equiv=\"refresh\" content=\"5;url=https://www.parrocchiacarpaneto.com/userhome/#/\">";
        echo "</head>";

        echo "<body>";
        echo "Registrazione terminata. Verrai reindirizzato alla pagina di login tra 5 secondi!";
        echo "</body>";
        echo "</html>";

    } else {
        // header("HTTP/1.0 401 Unauthorized");
        // http_response_code(401);
        echo json_encode($completeResult);
    }

?>