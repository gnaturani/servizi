<?php

header('Access-Control-Allow-Origin: *'); 
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');

    header('Content-Type: application/json');


    $data = json_decode(file_get_contents('php://input'), true);

    try {
        $imageDir = $data['folder'];
        $base64Image = $data['image64'];

        $fileName =  $data['filename'];

        $base64Image = trim($base64Image);
        $base64Image = str_replace('data:image/png;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/jpg;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/jpeg;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/gif;base64,', '', $base64Image);
        $base64Image = str_replace(' ', '+', $base64Image);

        $imageData = base64_decode($base64Image);
        //Set image whole path here 
        $filePath = $_SERVER['DOCUMENT_ROOT']."/pgs/".$imageDir."/".$fileName;
        //$filePath = str_replace("home","pgs", $filePath);

        echo "filePath: ".$filePath;
        $result = file_put_contents($filePath, $imageData);

        echo "Result: ".$result;

    }
     catch(Exception $Err){
            header("HTTP/1.1 502 Internal Server Error");
            echo json_encode($Err);   
    }

?>
