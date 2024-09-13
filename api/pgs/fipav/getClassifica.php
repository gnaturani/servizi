<?php

header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

    header('Content-Type: application/json');

    require_once("../dbconnect.php");
    require_once("decode2df.php");
    $CId = '45877';
    $CId = htmlspecialchars($_GET["CId"]);
    $url_fipav = 'http://www.fipavpiacenza.it/risultati-classifiche.aspx?ComitatoId=74&StId=1505&DataDa=&StatoGara=&CId='.$CId.'&SId=&PId=9653&btFiltro=CERCA';
    // $url_fipav = '2dif_18_19.htm';
    $completeResult = decodeRankingPage($url_fipav);

    if ($completeResult->success == 1){
        echo json_encode($completeResult);
    } else {
         header("HTTP/1.1 502 Internal Server Error");
         echo json_encode($completeResult->returnMessages);
    }

?>