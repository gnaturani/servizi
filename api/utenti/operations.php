<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("../models/user.php");
require_once("utente.php");
require_once("../mainjwt.php");
require_once("../models/mailinfo.php");

error_reporting(E_ERROR | E_PARSE);

function recuperaUtenti($data)
{
    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from utenti
                            order by cognome, nome
                            ";

        $stmt = $conn->prepare($sqlString);

        $alldata = [];
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // output data of each row
        foreach ($rows as $row) {

            $act = new Utente();

            $act->username = $row['username'];
            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->name = $row['nome'];
            $act->surname = $row['cognome'];
            $act->gruppo = $row['gruppo'];

            if ($row['cognome'] === '' || $row['cognome'] === null) {
                $act->cognome = $act->username;
                $act->surname = $act->username;
            }

            if ($row['gest_pag'] != null) {
                if ($row['gest_pag'] == 1) {
                    $act->gest_pag = true;
                } else {
                    $act->gest_pag = false;
                }
            } else {
                $act->gest_pag = false;
            }

            if ($row['only_display'] != null) {
                if ($row['only_display'] == 1) {
                    $act->only_display = true;
                } else {
                    $act->only_display = false;
                }
            } else {
                $act->only_display = false;
            }

            $act->id = $row['id'];
            $act->created_at = $row['created_at'];
            $act->confirmed_at = $row['confirmed_at'];
            $act->updated_at = $row['updated_at'];
            $act->confirmed = $row['confirmed'];
            $act->data_nascita = $row['data_nascita'];

            $act->email1 = $row['email1'];
            $act->cell1 = $row['cell1'];
            $act->email2 = $row['email2'];
            $act->cell2 = $row['cell2'];
            $act->privacy_accepted = setBool($row['privacy_accepted']);

            $act->apps = [];

            // recupero le apps legate all'utente
            $sqlStringApps = "SELECT * from utenti_app
                                    inner join app
                                     on utenti_app.idapp = app.id

                                where iduser = :iduser
                            ";

            $stmtApps = $conn->prepare($sqlStringApps);

            $stmtApps->bindParam(':iduser', $iduser);
            $iduser = $row['id'];

            $found = false;
            $stmtApps->execute();
            $rowsApps = $stmtApps->fetchAll();

            foreach ($rowsApps as $rowApp) {
                $actApp = new App();

                $actApp->id = $rowApp['id'];
                $actApp->title = $rowApp['title'];
                $actApp->url = $rowApp['url'];

                $act->apps[] = $actApp;
            }

            $alldata[] = $act;
        }

        $returnResult->returnObject = $alldata;
        $returnResult->success = 1;

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaImmagineUtente($data)
{
    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from utenti
                            where id = '$data[id]'
                            order by cognome, nome
                            ";

        $stmt = $conn->prepare($sqlString);

        $alldata = [];
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // output data of each row
        foreach ($rows as $row) {

            $act = new Utente();

            $act->username = $row['username'];
            $act->image = $row['image'];
        }

        $returnResult->returnObject = $act;
        $returnResult->success = 1;

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function setBool($in)
{
    $only_display = false;
    if ($in != null) {
        if ($in == 1) {
            $only_display = true;
        } else {
            $only_display = false;
        }
    } else {
        $only_display = false;
    }
    return $only_display;
}

function assignIfNotEmpty($item)
{
    if ($item !== null)  return true;
    return false;
}