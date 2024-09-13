<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");

function recuperaApp()
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from app ";
        $stmt = $conn->prepare($sqlString);

        // echo $sqlString;

        $stmt->execute();
        $rows = $stmt->fetchAll();

        $all = [];

        // output data of each row
        foreach ($rows as $row) {

            $act = new stdClass();
            $act->title = $row['title'];
            $act->id = $row['id'];
            $act->url = $row['url'];

            $all[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnObject = $all;

        $conn = null;
    } catch (PDOException $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}



function saveUserApps($data)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtDelete = $conn->prepare("DELETE from utenti_app
                    WHERE iduser = :iduser
                ");

        $stmtDelete->bindParam(':iduser', $iduser);
        $iduser = $data[userid];
        $stmtDelete->execute();

        $stmtInsert = $conn->prepare("INSERT into utenti_app
                        (
                            iduser, idapp
                        )
                        values
                        (
                            :iduser, :idapp
                        )
                ");

        $stmtInsert->bindParam(':iduser', $iduser);
        $stmtInsert->bindParam(':idapp', $idapp);
        foreach ($data[apps] as $app) {

            if ($app[selected]) {
                $iduser = $data[userid];
                $idapp = $app[id];
                $stmtInsert->execute();
            }

        }
        $returnResult->result = 1;
        $returnResult->success = 1;
        $conn = null;

    } catch (PDOException $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}
