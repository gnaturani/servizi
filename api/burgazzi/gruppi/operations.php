<?php
require_once("../../dbconnect.php");
require_once("../../resultCalling.php");
require_once("../../commonOperations.php");
require_once("../../mainjwt.php");
require_once("gruppo.php");

error_reporting(E_ERROR | E_PARSE);


function salvaGruppo($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('salvaGruppo Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select *
                                            from ab_gruppi
                             where id = :id
                    ");
        $stmtSearch->bindParam(':id', $id);
        $id = $data[id];
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();

        $found = false;
        foreach ($rows as $row) {
            $found = true;
        }


        error_log(print_r('found Data:', true), 0);
        error_log(print_r($found, true), 0);

        if ($found == false) {

            $stmtInsert = $conn->prepare("insert into ab_gruppi
                               ( nome,
                               descrizione,
                               testo,
                                inizio,
                                fine,
                                creato_il
                                )
                                VALUES (
                                    :nome,
                                    :descrizione,
                                    :testo,
                                    :inizio,
                                    :fine,
                                    NOW()
                                )
                    ");

            $stmtInsert->bindParam(':nome', $nome);
            $stmtInsert->bindParam(':descrizione', $descrizione);
            $stmtInsert->bindParam(':testo', $testo);
            $stmtInsert->bindParam(':inizio', $inizio);
            $stmtInsert->bindParam(':fine', $fine);

            $nome = $data[nome];
            $descrizione = $data[descrizione];
            $testo = $data[testo];
            $inizio =  explode("T", $data[inizio])[0];
            $fine =  explode("T", $data[fine])[0];

            $stmtInsert->execute();

            $confirmId = $conn->lastInsertId();

            $returnResult->success = 1;
            $returnResult->returnObject = $confirmId;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Gruppo salvato!";
        } else {
            $stmtUpdate = $conn->prepare("update ab_gruppi
                                set nome = :nome,
                                descrizione = :descrizione,
                                testo = :testo,
                                inizio = :inizio,
                                fine = :fine,
                                aggiornato_il = NOW()

                                where id = :id
                    ");

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':nome', $nome);
            $stmtUpdate->bindParam(':descrizione', $descrizione);
            $stmtUpdate->bindParam(':testo', $testo);
            $stmtUpdate->bindParam(':inizio', $inizio);
            $stmtUpdate->bindParam(':fine', $fine);

            $id = $data[id];
            $nome = $data[nome];
            $descrizione = $data[descrizione];
            $testo = $data[testo];
            $inizio =  explode("T", $data[inizio])[0];
            $fine =  explode("T", $data[fine])[0];
            $stmtUpdate->execute();

            $returnResult->success = 1;
            $returnResult->returnMessages = [];
            $returnResult->returnObject = $id;
            $returnResult->returnMessages[] = "Gruppo aggiornato!";

            $confirmId = $id;
        }

        $conn = null;
        return salvaFilesGruppo($data, $confirmId);
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function salvaFilesGruppo($data, $idgruppo)
{
    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('salvaFilesGruppo Data:', true), 0);
        error_log(print_r($data[files], true), 0);

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtDelete = $conn->prepare("delete
                                            from ab_gruppi_files
                             where idgruppo = :idgruppo
                    ");
        $stmtDelete->bindParam(':idgruppo', $id);
        $id = $idgruppo;
        $stmtDelete->execute();

        foreach ($data[files] as $act) {

            $stmtInsert = $conn->prepare("insert into ab_gruppi_files
            ( nome,
             url,
             idgruppo
                          )
             VALUES (
                 :nome,
                 :url,
                 :idgruppo
             )");

            $stmtInsert->bindParam(':nome', $nome);
            $stmtInsert->bindParam(':url', $url);
            $stmtInsert->bindParam(':idgruppo', $idgruppo);

            $nome = $act[nome];
            $url = $act[url];
            $idgruppo =  $idgruppo;

            $stmtInsert->execute();
        }

        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnObject = $id;
        $returnResult->returnMessages[] = "Gruppo aggiornato!";

    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function reuperaGruppi($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        /*
        error_log(print_r('recuperaGruppi Data:', true), 0);
        error_log(print_r($data, true), 0);
        */

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "Select *
                                            from ab_gruppi
                                            where id > 0
                    ";

        if ($data[id] !== null) {
            $sqlString = $sqlString . " and id = $data[id]";
        }

        /*
        error_log(print_r('sqlString Data:', true), 0);
        error_log(print_r($sqlString, true), 0);
        */

        $stmtSearch = $conn->prepare($sqlString);
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();

        $alldata = array();
        foreach ($rows as $row) {

            $act = new Gruppo();
            $act->id = $row[id];
            $act->nome = $row[nome];
            $act->descrizione = $row[descrizione];
            $act->testo = $row[testo];
            $act->inizio = $row[inizio];
            $act->fine = $row[fine];
            $act->creato_il = $row[creato_il];
            $act->aggiornato_il = $row[aggiornato_il];

            $alldata[] = $act;
        }

        if ($data[details] != 1) {
            $conn = null;
            $returnResult->returnObject = $alldata;
            $returnResult->success = 1;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Gruppi recuperati!";
            return $returnResult;
        }

        foreach ($alldata as $act) {
            $sqlString = "Select *
                    from ab_gruppi_files
                    where idgruppo = $act->id";

            error_log(print_r('sqlString Data:', true), 0);
            error_log(print_r($sqlString, true), 0);

            $stmtSearch = $conn->prepare($sqlString);
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();

            $act->files = array();
            foreach ($rows as $row) {
                $actFile = new File();
                $actFile->id = $row[id];
                $actFile->url = $row[url];
                $actFile->nome = $row[nome];

                $act->files[] = $actFile;
            }
        }

        foreach ($alldata as $act) {
            $sqlString = "Select *
                    from ab_gruppi_codes
                    where idgruppo = $act->id";

            error_log(print_r('sqlString Data:', true), 0);
            error_log(print_r($sqlString, true), 0);

            $stmtSearch = $conn->prepare($sqlString);
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();

            $act->codici = array();
            foreach ($rows as $row) {
                $actFile = new Codice();
                $actFile->id = $row[id];
                $actFile->code = $row[code];
                $actFile->creato_il = $row[creato_il];
                $actFile->nr_accessi = $row[nr_accessi];
                $actFile->nr_accessi_max = $row[nr_accessi_max];
                $actFile->last_access = $row[last_access];

                $act->codici[] = $actFile;
            }
        }

        $returnResult->returnObject = $alldata;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Gruppi recuperati!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function recuperaCartellaFiles()
{

    $hostport = $_SERVER[HTTP_HOST];
    if ($hostport == "localhost:8888") {
        return '../../../../../../Projects/Asilo Burgazzi/burgazzi_files';
    } else {
        return $_SERVER['DOCUMENT_ROOT'].'/burgazzi_files/';
    }
}

function recuperaFilesFolder($path, $folderIn)
{

    error_log(print_r('path:', true), 0);
    error_log(print_r($path, true), 0);

    $alldata = array_diff(scandir($path), array('.', '..', '.DS_Store'));

    if ($path === '../../../../../../Projects/Asilo Burgazzi/burgazzi_files/pietraparcellara/pietrabis') {
        error_log(print_r('files:', true), 0);
        error_log(print_r($alldata, true), 0);
    }

    foreach ($alldata as $act) {

        if (is_dir($path . '/' . $act)) {

            error_log(print_r('folder:', true), 0);
            error_log(print_r($act, true), 0);

            $folder = new File();
            $folder->url = $path . '/' . $act;
            $folder->name = $act;
            $folder->nome = $act;
            $folder->is_folder = true;
            $folder->is_file = false;
            $folderIn->files[] = $folder;

            recuperaFilesFolder($folder->url, $folder);
        } else {
            $file = new File();
            $file->url = $act;
            $file->name = $act;
            $file->nome = $act;
            $file->is_folder = false;
            $file->is_file = true;
            // $folderIn->files[] = $file;
        }
    }
    return $folderIn;
}

function recuperaFiles($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;
    $returnResult->returnMessages = [];

    try {

        error_log(print_r('recuperaFiles Data:', true), 0);
        error_log(print_r($data, true), 0);

        $alldata = [];
        $path = recuperaCartellaFiles();
        $returnResult->returnMessages[] = "Path recuperato: " . $path;

        $root = new File();
        $root->files = [];
        $alldata = recuperaFilesFolder($path, $root);

        error_log(print_r('alldata/n/n:', true), 0);
        error_log(print_r($alldata, true), 0);

        $returnResult->returnObject = $alldata->files;
        $returnResult->success = 1;
        // $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Files recuperati!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;
    }

    return $returnResult;
}
