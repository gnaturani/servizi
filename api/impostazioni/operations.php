<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("../models/setting.php");
require_once("../models/mailinfo.php");
require_once("../dati_anagrafici/operations.php");
require_once("../mainjwt.php");

error_reporting(E_ERROR | E_PARSE);


function salvaImpostazioni($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('salvaImpostazioni Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT INTO impostazioni
                                (id, chiave, etichetta, contenuto, tipo )
                            VALUES (:id, :chiave, :etichetta, :contenuto, :tipo )
                            ON DUPLICATE KEY UPDATE
                            chiave =:chiave,
                            etichetta =:etichetta, contenuto =:contenuto,
                            tipo = :tipo
                    ");

        $stmtInsert->bindParam(':id', $id);
        $stmtInsert->bindParam(':chiave', $chiave);
        $stmtInsert->bindParam(':etichetta', $etichetta);
        $stmtInsert->bindParam(':contenuto', $contenuto);
        $stmtInsert->bindParam(':tipo', $tipo);

        foreach ($data as $impostazione) {
            $id = $impostazione[id];
            $chiave = $impostazione[chiave];
            $etichetta = $impostazione[etichetta];
            $contenuto = $impostazione[contenuto];
            $tipo = $impostazione[tipo];
            $stmtInsert->execute();
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


function recuperaImpostazioni($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    error_log(print_r('recuperaImpostazioni Data:', true), 0);
    error_log(print_r($data, true), 0);

    $chiave = null;
    try {
        $chiave = $data[chiave];
    } catch (Exception $Err) { }

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select *
                                from impostazioni
                    ");

        if ($chiave !== null) {
            $stmtSearch = $conn->prepare("Select *
                                from impostazioni
                                where chiave = '$chiave'
                    ");
        }

        $alldata = array();
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {

            $act = new Setting();

            $act->id = $row['id'];
            $act->chiave = $row['chiave'];
            $act->etichetta = $row['etichetta'];
            $act->contenuto = $row['contenuto'];
            $act->tipo = $row['tipo'];
            $alldata[] = $act;
        }

        $returnResult->returnObject = $alldata;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Impostazioni recuperate!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}
