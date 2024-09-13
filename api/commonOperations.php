<?php

require_once("dbconnect.php");
require_once("resultCalling.php");
require_once("models/setting.php");

function recuperaTestoImpostazione($chiave) {

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    error_log(print_r('recuperaImpostazioni Chiave:', true), 0);
    error_log(print_r($chiave, true), 0);

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtSearch = $conn->prepare("Select *
                                from impostazioni
                                where chiave = '$chiave'
                    ");

                    $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {

            $act = new Setting();

            $act->id = $row['id'];
            $act->chiave = $row['chiave'];
            $act->etichetta = $row['etichetta'];
            $act->contenuto = $row['contenuto'];
            $act->tipo = $row['tipo'];
        }

        $returnResult->returnObject = $act->contenuto;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Impostazione recuperata!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}



?>