<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("anagrafica.php");
require_once("comune.php");
require_once("nazione.php");

function ricercaAnagrafica($data)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";
        $returnResult->result = 0;
        $returnResult->success = 0;

        $conn = connectToDbPDO();
        $nome = trim($data[name]);
        $cognome = trim($data[surname]);

        $nome = str_replace("'", "", $nome);
        $cognome = str_replace("'", "", $cognome);

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *, comune.descrizione as comune_des,
                                    comune.provincia as provincia_des,

                                    stato.descrizione as stato_nascita,
                                    cittadinanza.cittadinanza as cittadinanza

                                 from anag_base

                                left outer join anag_comune as comune
                                on anag_base.comune_n_ques = comune.codice

                                left outer join anag_nazione as stato
                                on anag_base.stato_n_ques = stato.codice

                                left outer join anag_nazione as cittadinanza
                                on anag_base.cittadinanza_n_ques = cittadinanza.codice

                            where cognome like '%$cognome%'
                              and nome like '%$nome%'
                            order by cognome, nome ";

        $returnResult->returnMessages[] = $sqlString;

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Anagrafica();

            $act->id = $row['id'];
            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->comune_nascita = $row['comune_des'];
            $act->provincia_nascita = $row['provincia_des'];
            $act->stato_nascita = $row['stato_nascita'];
            $act->cittadinanza = $row['cittadinanza'];
            $act->comune_n_ques = $row['comune_n_ques'];
            $act->provincia_n_ques = $row['provincia_n_ques'];
            $act->stato_n_ques = $row['stato_n_ques'];
            $act->cittadinanza_n_ques = $row['cittadinanza_n_ques'];
            $act->cittadinanza = $row['cittadinanza'];
            $act->stato_nascita = $row['stato_nascita'];
            $act->sesso = $row['sesso'];
            //$act->data_nascita = $row['data_nascita'];
            if (
                $row['data_nascita'] != '' && $row['data_nascita'] != null
                && $row['data_nascita'] != "NULL"
            ) {
                $act->data_nascita = DateTime::createFromFormat("Y-m-d", $row['data_nascita']);
                $act->anno = $act->data_nascita->format("Y");
                $act->data_nascita = $act->data_nascita->format("Y-m-d");

                if ($act->data_nascita == "-0001-11-30") {
                    $act->data_nascita = "";
                    $act->anno = "";
                }
            }

            $act->doc_tipo = $row['doc_tipo'];
            $act->doc_numero = $row['doc_numero'];
            $act->doc_comune_ril = $row['doc_comune_ril'];
            $act->doc_provincia_ril = $row['doc_provincia_ril'];
            $act->doc_stato_ril = $row['doc_stato_ril'];
            $act->accompagnatore = $row['accompagnatore'];
            $act->ruolo = $row['ruolo'];

            $act->cellulare_1 = $row['cellulare_1'];
            $act->cellulare_2 = $row['cellulare_2'];
            $act->cellulare_3 = $row['cellulare_3'];
            $act->email_1 = $row['email_1'];
            $act->email_2 = $row['email_2'];
            $act->res_via = $row['res_via'];
            $act->res_citta = $row['res_citta'];
            $act->res_provincia = $row['res_provincia'];

            $alldata[] = $act;
            $returnResult->result = 1;
            $returnResult->success = 1;
        }

        $returnResult->returnObject = $alldata;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function recuperaAnagrafica($user)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        $gruppo = $user[gruppo];

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *, comune.descrizione as comune_des,
                                    comune.provincia as provincia_des
                                 from anag_base
                                left outer join anag_comune as comune
                                on anag_base.comune_n_ques = comune.codice
                            where gruppo like '$gruppo'
                            order by cognome, nome ";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Anagrafica();

            $act->id = $row['id'];
            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->comune_nascita = $row['comune_des'];
            $act->provincia_nascita = $row['provincia_des'];
            $act->stato_nascita = $row['stato_nascita'];
            $act->cittadinanza = $row['cittadinanza'];
            $act->comune_n_ques = $row['comune_n_ques'];
            $act->provincia_n_ques = $row['provincia_n_ques'];
            $act->stato_n_ques = $row['stato_n_ques'];
            $act->cittadinanza_n_ques = $row['cittadinanza_n_ques'];
            $act->sesso = $row['sesso'];
            //$act->data_nascita = $row['data_nascita'];
            if (
                $row['data_nascita'] != '' && $row['data_nascita'] != null
                && $row['data_nascita'] != "NULL"
            ) {
                $act->data_nascita = DateTime::createFromFormat("Y-m-d", $row['data_nascita']);
                $act->anno = $act->data_nascita->format("Y");
                $act->data_nascita = $act->data_nascita->format("Y-m-d");

                if ($act->data_nascita == "-0001-11-30") {
                    $act->data_nascita = "";
                    $act->anno = "";
                }
            }

            $act->doc_tipo = $row['doc_tipo'];
            $act->doc_numero = $row['doc_numero'];
            $act->doc_comune_ril = $row['doc_comune_ril'];
            $act->doc_provincia_ril = $row['doc_provincia_ril'];
            $act->doc_stato_ril = $row['doc_stato_ril'];
            $act->accompagnatore = $row['accompagnatore'];
            $act->ruolo = $row['ruolo'];

            $act->cellulare_1 = $row['cellulare_1'];
            $act->cellulare_2 = $row['cellulare_2'];
            $act->cellulare_3 = $row['cellulare_3'];
            $act->email_1 = $row['email_1'];
            $act->email_2 = $row['email_2'];
            $act->res_via = $row['res_via'];
            $act->res_citta = $row['res_citta'];
            $act->res_provincia = $row['res_provincia'];

            $alldata[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->returnObject = $alldata;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaAnagraficaSingola($data)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        $gruppo = $user[gruppo];

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *, comune.descrizione as comune_des,
                                    comune.provincia as provincia_des
                                 from anag_base
                                left outer join anag_comune as comune
                                on anag_base.comune_n_ques = comune.codice
                            where id = '$data[id]'
                            order by cognome, nome ";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Anagrafica();

            $act->id = $row['id'];
            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->comune_nascita = $row['comune_des'];
            $act->provincia_nascita = $row['provincia_des'];
            $act->stato_nascita = $row['stato_nascita'];
            $act->cittadinanza = $row['cittadinanza'];
            $act->comune_n_ques = $row['comune_n_ques'];
            $act->provincia_n_ques = $row['provincia_n_ques'];
            $act->stato_n_ques = $row['stato_n_ques'];
            $act->cittadinanza_n_ques = $row['cittadinanza_n_ques'];
            $act->sesso = $row['sesso'];
            //$act->data_nascita = $row['data_nascita'];
            if (
                $row['data_nascita'] != '' && $row['data_nascita'] != null
                && $row['data_nascita'] != "NULL"
            ) {
                $act->data_nascita = DateTime::createFromFormat("Y-m-d", $row['data_nascita']);
                $act->anno = $act->data_nascita->format("Y");
                $act->data_nascita = $act->data_nascita->format("Y-m-d");

                if ($act->data_nascita == "-0001-11-30") {
                    $act->data_nascita = "";
                    $act->anno = "";
                }
            }

            $act->doc_tipo = $row['doc_tipo'];
            $act->doc_numero = $row['doc_numero'];
            $act->doc_comune_ril = $row['doc_comune_ril'];
            $act->doc_provincia_ril = $row['doc_provincia_ril'];
            $act->doc_stato_ril = $row['doc_stato_ril'];
            $act->accompagnatore = $row['accompagnatore'];
            $act->ruolo = $row['ruolo'];

            $act->cellulare_1 = $row['cellulare_1'];
            $act->cellulare_2 = $row['cellulare_2'];
            $act->cellulare_3 = $row['cellulare_3'];
            $act->email_1 = $row['email_1'];
            $act->email_2 = $row['email_2'];
            $act->res_via = $row['res_via'];
            $act->res_citta = $row['res_citta'];
            $act->res_provincia = $row['res_provincia'];

            $alldata[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->success = true;
        $returnResult->returnObject = $act;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function aggiornaAnagrafica($inAnagrafica)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT INTO anag_base
                            (nome, cognome, data_nascita, sesso,
                            comune_nascita, provincia_nascita, stato_nascita, cittadinanza,
                            comune_n_ques, provincia_n_ques, stato_n_ques, cittadinanza_n_ques,
                            doc_tipo, doc_numero, doc_comune_ril,
                            doc_provincia_ril, doc_stato_ril, accompagnatore, ruolo,

                            cellulare_1, cellulare_2, cellulare_3, email_1, email_2,
                            res_via, res_citta, res_provincia

                            )
                            VALUES (:nome, :cognome, :data_nascita, :sesso,
                            :comune_nascita, :provincia_nascita, :stato_nascita, :cittadinanza,
                            :comune_n_ques, :provincia_n_ques, :stato_n_ques, :cittadinanza_n_ques,
                            :doc_tipo, :doc_numero, :doc_comune_ril,
                            :doc_provincia_ril, :doc_stato_ril, :accompagnatore, :ruolo,

                            :cellulare_1, :cellulare_2, :cellulare_3, :email_1, :email_2,
                            :res_via, :res_citta, :res_provincia

                            )
                    ");

        $stmtUpdate = $conn->prepare("UPDATE anag_base
                            set nome = :nome,
                                cognome = :cognome,
                                data_nascita = :data_nascita,
                                sesso = :sesso,
                                comune_nascita = :comune_nascita,
                                provincia_nascita = :provincia_nascita,
                                stato_nascita = :stato_nascita,
                                cittadinanza = :cittadinanza,

                                comune_n_ques = :comune_n_ques,
                                provincia_n_ques = :provincia_n_ques,
                                stato_n_ques = :stato_n_ques,
                                cittadinanza_n_ques = :cittadinanza_n_ques,

                                doc_tipo = :doc_tipo,
                                doc_numero = :doc_numero,
                                doc_comune_ril = :doc_comune_ril,
                                doc_provincia_ril = :doc_provincia_ril,
                                doc_stato_ril = :doc_stato_ril,
                                accompagnatore = :accompagnatore,
                                ruolo = :ruolo,

                                cellulare_1 = :cellulare_1,
                                cellulare_2 = :cellulare_2,
                                cellulare_3 = :cellulare_3,
                                email_1 = :email_1,
                                email_2 = :email_2,
                                res_via = :res_via,
                                res_citta = :res_citta,
                                res_provincia = :res_provincia,

                                aggiornato_il = NOW()
                            where id = :id
                    ");

        $stmtInsert->bindParam(':nome', $nome);
        $stmtInsert->bindParam(':cognome', $cognome);
        $stmtInsert->bindParam(':data_nascita', $data_nascita);
        $stmtInsert->bindParam(':sesso', $sesso);
        $stmtInsert->bindParam(':comune_nascita', $comune_nascita);
        $stmtInsert->bindParam(':provincia_nascita', $provincia_nascita);
        $stmtInsert->bindParam(':stato_nascita', $stato_nascita);
        $stmtInsert->bindParam(':cittadinanza', $cittadinanza);
        $stmtInsert->bindParam(':comune_n_ques', $comune_n_ques);
        $stmtInsert->bindParam(':provincia_n_ques', $provincia_n_ques);
        $stmtInsert->bindParam(':stato_n_ques', $stato_n_ques);
        $stmtInsert->bindParam(':cittadinanza_n_ques', $cittadinanza_n_ques);
        $stmtInsert->bindParam(':doc_tipo', $doc_tipo);
        $stmtInsert->bindParam(':doc_numero', $doc_numero);
        $stmtInsert->bindParam(':doc_comune_ril', $doc_comune_ril);
        $stmtInsert->bindParam(':doc_provincia_ril', $doc_provincia_ril);
        $stmtInsert->bindParam(':doc_stato_ril', $doc_stato_ril);
        $stmtInsert->bindParam(':accompagnatore', $accompagnatore);
        $stmtInsert->bindParam(':ruolo', $ruolo);

        $stmtInsert->bindParam(':cellulare_1', $cellulare_1);
        $stmtInsert->bindParam(':cellulare_2', $cellulare_2);
        $stmtInsert->bindParam(':cellulare_3', $cellulare_3);
        $stmtInsert->bindParam(':email_1', $email_1);
        $stmtInsert->bindParam(':email_2', $email_2);
        $stmtInsert->bindParam(':res_via', $res_via);
        $stmtInsert->bindParam(':res_citta', $res_citta);
        $stmtInsert->bindParam(':res_provincia', $res_provincia);

        $stmtUpdate->bindParam(':id', $id);
        $stmtUpdate->bindParam(':nome', $nome);
        $stmtUpdate->bindParam(':cognome', $cognome);
        $stmtUpdate->bindParam(':data_nascita', $data_nascita);
        $stmtUpdate->bindParam(':sesso', $sesso);
        $stmtUpdate->bindParam(':comune_nascita', $comune_nascita);
        $stmtUpdate->bindParam(':provincia_nascita', $provincia_nascita);
        $stmtUpdate->bindParam(':stato_nascita', $stato_nascita);
        $stmtUpdate->bindParam(':cittadinanza', $cittadinanza);
        $stmtUpdate->bindParam(':comune_n_ques', $comune_n_ques);
        $stmtUpdate->bindParam(':provincia_n_ques', $provincia_n_ques);
        $stmtUpdate->bindParam(':stato_n_ques', $stato_n_ques);
        $stmtUpdate->bindParam(':cittadinanza_n_ques', $cittadinanza_n_ques);
        $stmtUpdate->bindParam(':doc_tipo', $doc_tipo);
        $stmtUpdate->bindParam(':doc_numero', $doc_numero);
        $stmtUpdate->bindParam(':doc_comune_ril', $doc_comune_ril);
        $stmtUpdate->bindParam(':doc_provincia_ril', $doc_provincia_ril);
        $stmtUpdate->bindParam(':doc_stato_ril', $doc_stato_ril);
        $stmtUpdate->bindParam(':accompagnatore', $accompagnatore);
        $stmtUpdate->bindParam(':ruolo', $ruolo);

        $stmtUpdate->bindParam(':cellulare_1', $cellulare_1);
        $stmtUpdate->bindParam(':cellulare_2', $cellulare_2);
        $stmtUpdate->bindParam(':cellulare_3', $cellulare_3);
        $stmtUpdate->bindParam(':email_1', $email_1);
        $stmtUpdate->bindParam(':email_2', $email_2);
        $stmtUpdate->bindParam(':res_via', $res_via);
        $stmtUpdate->bindParam(':res_citta', $res_citta);
        $stmtUpdate->bindParam(':res_provincia', $res_provincia);

        $id = $inAnagrafica[id];
        $nome = $inAnagrafica[nome];
        $cognome = $inAnagrafica[cognome];
        $data_nascita = $inAnagrafica[data_nascita];

        try {
            $data_nascita = substr($data_nascita, 0,10);
        } catch (Exception $error) {
        }

        $sesso = $inAnagrafica[sesso];
        $comune_nascita = $inAnagrafica[comune_nascita];
        $provincia_nascita = $inAnagrafica[provincia_nascita];
        $stato_nascita = $inAnagrafica[stato_nascita];
        $cittadinanza = $inAnagrafica[cittadinanza];
        $comune_n_ques = $inAnagrafica[comune_n_ques];
        $provincia_n_ques = $inAnagrafica[provincia_n_ques];
        $stato_n_ques = $inAnagrafica[stato_n_ques];
        $cittadinanza_n_ques = $inAnagrafica[cittadinanza_n_ques];
        $doc_tipo = $inAnagrafica[doc_tipo];
        $doc_numero = $inAnagrafica[doc_numero];
        $doc_comune_ril = $inAnagrafica[doc_comune_ril];
        $doc_provincia_ril = $inAnagrafica[doc_provincia_ril];
        $doc_stato_ril = $inAnagrafica[doc_stato_ril];
        $accompagnatore = $inAnagrafica[accompagnatore];
        if ($accompagnatore == false) {
            $accompagnatore = 0;
        }
        if ($accompagnatore == true) {
            $accompagnatore = 1;
        }
        if($creato_da == null  || $creato_da == undefined  || $creato_da == ''){
            $creato_da = 1;
        }

        $ruolo = $inAnagrafica[ruolo];

        $cellulare_1 = $inAnagrafica[cellulare_1];
        $cellulare_2 = $inAnagrafica[cellulare_2];
        $cellulare_3 = $inAnagrafica[cellulare_3];
        $email_1 = $inAnagrafica[email_1];
        $email_2 = $inAnagrafica[email_2];
        $res_via = $inAnagrafica[res_via];
        $res_citta = $inAnagrafica[res_citta];
        $res_provincia = $inAnagrafica[res_provincia];

        if ($inAnagrafica[id] != null) {
            $stmtUpdate->execute();
        } else {
            $stmtInsert->execute();
        }

        $returnResult->result = 1;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function aggiornaAnagrafica2($inAnagrafica)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT INTO anag_base
                            (nome, cognome, data_nascita, sesso,
                            comune_nascita, provincia_nascita, stato_nascita, cittadinanza,
                            comune_n_ques, provincia_n_ques, stato_n_ques, cittadinanza_n_ques,
                            doc_tipo, doc_numero, doc_comune_ril,
                            doc_provincia_ril, doc_stato_ril, accompagnatore, ruolo,

                            cellulare_1, cellulare_2, cellulare_3, email_1, email_2,
                            res_via, res_citta, res_provincia, creato_da

                            )
                            VALUES (:nome, :cognome, :data_nascita, :sesso,
                            :comune_nascita, :provincia_nascita, :stato_nascita, :cittadinanza,
                            :comune_n_ques, :provincia_n_ques, :stato_n_ques, :cittadinanza_n_ques,
                            :doc_tipo, :doc_numero, :doc_comune_ril,
                            :doc_provincia_ril, :doc_stato_ril, :accompagnatore, :ruolo,

                            :cellulare_1, :cellulare_2, :cellulare_3, :email_1, :email_2,
                            :res_via, :res_citta, :res_provincia, :creato_da

                            )
                    ");

        $stmtUpdate = $conn->prepare("UPDATE anag_base
                            set nome = :nome,
                                cognome = :cognome,
                                data_nascita = :data_nascita,
                                sesso = :sesso,
                                comune_nascita = :comune_nascita,
                                provincia_nascita = :provincia_nascita,
                                stato_nascita = :stato_nascita,
                                cittadinanza = :cittadinanza,

                                comune_n_ques = :comune_n_ques,
                                provincia_n_ques = :provincia_n_ques,
                                stato_n_ques = :stato_n_ques,
                                cittadinanza_n_ques = :cittadinanza_n_ques,

                                doc_tipo = :doc_tipo,
                                doc_numero = :doc_numero,
                                doc_comune_ril = :doc_comune_ril,
                                doc_provincia_ril = :doc_provincia_ril,
                                doc_stato_ril = :doc_stato_ril,
                                accompagnatore = :accompagnatore,
                                ruolo = :ruolo,

                                cellulare_1 = :cellulare_1,
                                cellulare_2 = :cellulare_2,
                                cellulare_3 = :cellulare_3,
                                email_1 = :email_1,
                                email_2 = :email_2,
                                res_via = :res_via,
                                res_citta = :res_citta,
                                res_provincia = :res_provincia,

                                aggiornato_il = NOW()
                            where id = :id
                    ");

        $stmtInsert->bindParam(':nome', $nome);
        $stmtInsert->bindParam(':cognome', $cognome);
        $stmtInsert->bindParam(':data_nascita', $data_nascita);
        $stmtInsert->bindParam(':sesso', $sesso);
        $stmtInsert->bindParam(':comune_nascita', $comune_nascita);
        $stmtInsert->bindParam(':provincia_nascita', $provincia_nascita);
        $stmtInsert->bindParam(':stato_nascita', $stato_nascita);
        $stmtInsert->bindParam(':cittadinanza', $cittadinanza);
        $stmtInsert->bindParam(':comune_n_ques', $comune_n_ques);
        $stmtInsert->bindParam(':provincia_n_ques', $provincia_n_ques);
        $stmtInsert->bindParam(':stato_n_ques', $stato_n_ques);
        $stmtInsert->bindParam(':cittadinanza_n_ques', $cittadinanza_n_ques);
        $stmtInsert->bindParam(':doc_tipo', $doc_tipo);
        $stmtInsert->bindParam(':doc_numero', $doc_numero);
        $stmtInsert->bindParam(':doc_comune_ril', $doc_comune_ril);
        $stmtInsert->bindParam(':doc_provincia_ril', $doc_provincia_ril);
        $stmtInsert->bindParam(':doc_stato_ril', $doc_stato_ril);
        $stmtInsert->bindParam(':accompagnatore', $accompagnatore);
        $stmtInsert->bindParam(':ruolo', $ruolo);
        $stmtInsert->bindParam(':creato_da', $creato_da);

        $stmtInsert->bindParam(':cellulare_1', $cellulare_1);
        $stmtInsert->bindParam(':cellulare_2', $cellulare_2);
        $stmtInsert->bindParam(':cellulare_3', $cellulare_3);
        $stmtInsert->bindParam(':email_1', $email_1);
        $stmtInsert->bindParam(':email_2', $email_2);
        $stmtInsert->bindParam(':res_via', $res_via);
        $stmtInsert->bindParam(':res_citta', $res_citta);
        $stmtInsert->bindParam(':res_provincia', $res_provincia);

        $stmtUpdate->bindParam(':id', $id);
        $stmtUpdate->bindParam(':nome', $nome);
        $stmtUpdate->bindParam(':cognome', $cognome);
        $stmtUpdate->bindParam(':data_nascita', $data_nascita);
        $stmtUpdate->bindParam(':sesso', $sesso);
        $stmtUpdate->bindParam(':comune_nascita', $comune_nascita);
        $stmtUpdate->bindParam(':provincia_nascita', $provincia_nascita);
        $stmtUpdate->bindParam(':stato_nascita', $stato_nascita);
        $stmtUpdate->bindParam(':cittadinanza', $cittadinanza);
        $stmtUpdate->bindParam(':comune_n_ques', $comune_n_ques);
        $stmtUpdate->bindParam(':provincia_n_ques', $provincia_n_ques);
        $stmtUpdate->bindParam(':stato_n_ques', $stato_n_ques);
        $stmtUpdate->bindParam(':cittadinanza_n_ques', $cittadinanza_n_ques);
        $stmtUpdate->bindParam(':doc_tipo', $doc_tipo);
        $stmtUpdate->bindParam(':doc_numero', $doc_numero);
        $stmtUpdate->bindParam(':doc_comune_ril', $doc_comune_ril);
        $stmtUpdate->bindParam(':doc_provincia_ril', $doc_provincia_ril);
        $stmtUpdate->bindParam(':doc_stato_ril', $doc_stato_ril);
        $stmtUpdate->bindParam(':accompagnatore', $accompagnatore);
        $stmtUpdate->bindParam(':ruolo', $ruolo);

        $stmtUpdate->bindParam(':cellulare_1', $cellulare_1);
        $stmtUpdate->bindParam(':cellulare_2', $cellulare_2);
        $stmtUpdate->bindParam(':cellulare_3', $cellulare_3);
        $stmtUpdate->bindParam(':email_1', $email_1);
        $stmtUpdate->bindParam(':email_2', $email_2);
        $stmtUpdate->bindParam(':res_via', $res_via);
        $stmtUpdate->bindParam(':res_citta', $res_citta);
        $stmtUpdate->bindParam(':res_provincia', $res_provincia);

        $id = $inAnagrafica[id];
        $nome = $inAnagrafica[nome];
        $cognome = $inAnagrafica[cognome];
        $data_nascita = $inAnagrafica[data_nascita];
        $sesso = $inAnagrafica[sesso];
        $comune_nascita = $inAnagrafica[comune_nascita];
        $provincia_nascita = $inAnagrafica[provincia_nascita];
        $stato_nascita = $inAnagrafica[stato_nascita];
        $cittadinanza = $inAnagrafica[cittadinanza];
        $comune_n_ques = $inAnagrafica[comune_n_ques];
        $provincia_n_ques = $inAnagrafica[provincia_n_ques];
        $stato_n_ques = $inAnagrafica[stato_n_ques];
        $cittadinanza_n_ques = $inAnagrafica[cittadinanza_n_ques];
        $doc_tipo = $inAnagrafica[doc_tipo];
        $doc_numero = $inAnagrafica[doc_numero];
        $doc_comune_ril = $inAnagrafica[doc_comune_ril];
        $doc_provincia_ril = $inAnagrafica[doc_provincia_ril];
        $doc_stato_ril = $inAnagrafica[doc_stato_ril];
        $accompagnatore = $inAnagrafica[accompagnatore];
        if ($accompagnatore == false) {
            $accompagnatore = 0;
        }
        if ($accompagnatore == true) {
            $accompagnatore = 1;
        }
        $ruolo = $inAnagrafica[ruolo];
        $creato_da = $inAnagrafica[creato_da];
        if($creato_da == null ){
            $creato_da = 1;
        }

        $cellulare_1 = $inAnagrafica[cellulare_1];
        $cellulare_2 = $inAnagrafica[cellulare_2];
        $cellulare_3 = $inAnagrafica[cellulare_3];
        $email_1 = $inAnagrafica[email_1];
        $email_2 = $inAnagrafica[email_2];
        $res_via = $inAnagrafica[res_via];
        $res_citta = $inAnagrafica[res_citta];
        $res_provincia = $inAnagrafica[res_provincia];

        if ($inAnagrafica[id] != null) {
            $stmtUpdate->execute();
        } else {
            $stmtInsert->execute();
            $inAnagrafica[id] = $conn->lastInsertId();
        }

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnObject = $inAnagrafica;

        $conn = null;
    } catch (PDOException $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;
        $conn = null;
    }

    return $returnResult;
}


function recuperaComuni($filters)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from anag_comune where 1=1 ";

        if ($filters != null) {
            if (
                $filters[descrizione] != '' && $filters[descrizione] != null
            ) {
                    $sqlString = $sqlString . " and descrizione like '%" .
                        $filters[descrizione] . "%' ";
                }

            if (
                $filters[provincia] != '' && $filters[provincia] != null
            ) {
                    $sqlString = $sqlString . " and provincia like '%" .
                        $filters[provincia] . "%' ";
                }
        }

        $sqlString = $sqlString . " order by descrizione";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Comune();

            $act->codice = $row['codice'];
            $act->descrizione = $row['descrizione'];
            $act->provincia = $row['provincia'];

            $alldata[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->returnObject = $alldata;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaStorico($filters)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT distinct(idturno), turni.titolo , turni.inizio
                        from presenze_turni

                        inner join turni
                        on presenze_turni.idturno = turni.id
                        and ( turni.preiscrizione = 0
                                || turni.preiscrizione = NULL )

                            where idanag = $filters[id]
                            order by turni.inizio";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {
            $act = new StoricoPresenze();
            $act->idturno = $row['idturno'];
            $act->titolo = $row['titolo'];
            $act->inizio = $row['inizio'];
            $alldata[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnObject = $alldata;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        $conn = null;
    }
    return $returnResult;
}



function recuperaNazioni($filters)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from anag_nazione where 1=1 ";

        $sqlString = $sqlString . " order by descrizione";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {
            $act = new Nazione();
            $act->codice = $row['codice'];
            $act->descrizione = $row['descrizione'];
            $act->cittadinanza = $row['cittadinanza'];
            $alldata[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->returnObject = $alldata;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }
    return $returnResult;
}

