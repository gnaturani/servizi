<?php

    $servername = "localhost";
    $dbname = "pgs";
    $username = "root";
    $password = "root";

    function connectToDb(){

        $hostport = $_SERVER[HTTP_HOST];

        //$hostport = "localhost:8888";

        if ($hostport == "localhost:8888"){
            $db = new mysqli("localhost", "root", "root", "Sql825371_1");
        } else {
            $db = new mysqli("62.149.150.229", "Sql825371", "g350h07fm3", "Sql825371_1");
        }

        if ($db->connect_errno) {
            echo "Impossibile connettersi a MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
            return null;
        }
        return $db;
    }

    function connectToDbPDO(){

        $hostport = $_SERVER[HTTP_HOST];

        //$hostport = "localhost:8888";

        if ($hostport == "localhost:8888"){
            $servername = "localhost";
            $dbname = "Sql825371_1";
            $username = "root";
            $password = "root";
        } else {
            $servername = "62.149.150.229";
            $dbname = "Sql825371_1";
            $username = "Sql825371";
            $password = "g350h07fm3";
        }
        
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

        $conn->exec("set names utf8");

        return $conn;
    }

?>
