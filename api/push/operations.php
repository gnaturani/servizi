<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("../mainjwt.php");

error_reporting(E_ERROR | E_PARSE);

function salvaSubscription($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('salvaSubscription Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select *
                                from push_subscriptions
                                where json = :json
                    ");
        $stmtSearch->bindParam(':json', $json);
        $json = $data[json];
        $stmtSearch->execute();

        $found = false;
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $found = true;
            if($data[id_utente] != NULL) {
                $stmtInsert = $conn->prepare("UPDATE push_subscriptions set id_utente = :id_utente
                                WHERE id = :id
                    ");
                $stmtInsert->bindParam(':id_utente', $id_utente);
                $id_utente = $data[id_utente];
                $stmtInsert->bindParam(':id', $id);
                $id = $row[id];
                $stmtInsert->execute();
            }
        }

        if ($found == false) {
            $stmtInsert = $conn->prepare("INSERT INTO push_subscriptions
                                    ( json )
                                VALUES ( :json )
                        ");

            $stmtInsert->bindParam(':json', $json);
            $json = $data[json];
            $stmtInsert->execute();
        } else {
            $returnResult->returnMessages[] = "Already registered!";    
        }

        $returnResult->result = 1;
        $returnResult->success = 1;

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaSubscriptions($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    error_log(print_r('recuperaSubscriptions Data:', true), 0);
    error_log(print_r($data, true), 0);

    $chiave = null;
    try {
        $chiave = $data[chiave];
    } catch (Exception $Err) { }

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select *
                                from push_subscriptions
                    ");

        $alldata = array();
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $act = new stdClass();

            $act->id = $row['id'];
            $act->json = $row['json'];
            $act->id_utente = $row['id_utente'];
            $act->test = $row['test'];
            $alldata[] = $act;
        }

        $returnResult->returnObject = $alldata;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "PushSubscriptions recuperate!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}
