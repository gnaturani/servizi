<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("../commonOperations.php");
require_once("turno.php");
require_once("presenza.php");
require_once("pagamento.php");
require_once("movimento.php");
require_once("../dati_anagrafici/anagrafica.php");
require_once("../models/mailinfo.php");
require_once("../models/setting.php");
require_once("../iscrizioni/operations.php");
require_once("../impostazioni/operations.php");

function recuperaMovimentiTurno($idTurno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from movimenti_turni
                                 where idturno = $idTurno
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $returnResult->returnMessages[] = $idTurno;

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Movimento();
            $act->id = $row['id'];
            $act->idturno = $row['idturno'];
            $act->data = $row['data'];
            $act->valore = $row['valore'];
            $act->descrizione = $row['descrizione'];
            $act->dareavere = $row['dareavere'];
            $act->tipologia = $row['tipologia'];

            if ($row['image'] != null) {
                $act->haveImage = 1;
            } else {
                $act->haveImage = 0;
            }

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

function recuperaSingoloMovimento($id)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from movimenti_turni
                                 where id = $id
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $returnResult->returnMessages[] = $idTurno;

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $act = new Movimento();

        // output data of each row
        foreach ($rows as $row) {

            $act->id = $row['id'];
            $act->idturno = $row['idturno'];
            $act->data = $row['data'];
            $act->valore = $row['valore'];
            $act->descrizione = $row['descrizione'];
            $act->dareavere = $row['dareavere'];
            $act->tipologia = $row['tipologia'];

            $act->image = $row['image'];
        }

        $returnResult->result = 1;
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

function recuperaPagamentiTurno($idTurno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *,pagamenti_turni.id as idpagamento,
                                   anag_base.id as idanag
                                 from pagamenti_turni left outer join anag_base
                                      on pagamenti_turni.idanag = anag_base.id
                                 where idturno = $idTurno
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $returnResult->returnMessages[] = $idTurno;

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Pagamento();
            $act->idturno = $row['idturno'];
            $act->idanag = $row['idanag'];

            $act->anagrafica = new Anagrafica();

            $act->anagrafica->nome = $row['nome'];
            $act->anagrafica->cognome = $row['cognome'];
            $act->anagrafica->data_nascita = $row['data_nascita'];
            $act->anagrafica->sesso = $row['sesso'];
            $act->anagrafica->comune_nascita = $row['comune_nascita'];
            $act->anagrafica->provincia_nascita = $row['provincia_nascita'];
            $act->anagrafica->stato_nascita = $row['stato_nascita'];
            $act->anagrafica->cittadinanza = $row['cittadinanza'];

            $act->anagrafica->comune_n_ques = $row['comune_n_ques'];
            $act->anagrafica->provincia_n_ques = $row['provincia_n_ques'];
            $act->anagrafica->stato_n_ques = $row['stato_n_ques'];
            $act->anagrafica->cittadinanza_n_ques = $row['cittadinanza_n_ques'];

            $act->anagrafica->doc_tipo = $row['doc_tipo'];
            $act->anagrafica->doc_numero = $row['doc_numero'];
            $act->anagrafica->doc_comune_ril = $row['doc_comune_ril'];
            $act->anagrafica->doc_provincia_ril = $row['doc_provincia_ril'];
            $act->anagrafica->doc_stato_ril = $row['doc_stato_ril'];

            //error_log( print_r( $act, true ) );

            $act->rate = array();

            $rata = new RataPagamento();
            $rata->id = $row['idpagamento'];
            $rata->quota_pagata = $row['quota_pagata'];
            $rata->totale_da_pagare = $row['totale_da_pagare'];
            $rata->data = $row['data'];
            $rata->saldato = $row['saldato'];

            $found = false;
            foreach ($alldata as $pagamento) {
                if ($pagamento->idanag == $act->idanag) {
                    $pagamento->rate[] = $rata;
                    $found = true;
                }
            }

            if ($found == false) {
                $act->rate[] = $rata;
                $alldata[] = $act;
            }
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



function recuperaDettagliTurnoCsv($idTurno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *
                                 from dettagli_turni left outer join anag_base
                                      on dettagli_turni.idanag = anag_base.id
                                 where idturno = $idTurno
                                 order by anag_base.cognome, anag_base.nome
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $returnResult->returnMessages[] = $idTurno;

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Pagamento();
            $act->idturno = $row['idturno'];
            $act->idanag = $row['idanag'];

            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->sesso = $row['sesso'];
            $act->pullman_a = $row['pullman_a'];
            $act->pullman_r = $row['pullman_r'];
            $act->pullman_g = $row['pullman_g'];
            $act->pullman_ga = $row['pullman_ga'];
            $act->pullman_gr = $row['pullman_gr'];
            $act->ruolo = $row['ruolo'];

            $act->anno = "";

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

            $act->maglia = $row['maglia'];
            $act->stanza = $row['stanza'];
            $act->stanza = str_replace(",", "-", $act->stanza);
            $act->stanza = str_replace(array("\n", "\t", "\r"), ' - ', $act->stanza);
            $act->note = $row['note'];
            $act->note = str_replace(",", "-", $act->note);
            $act->note = str_replace(array("\n", "\t", "\r"), ' - ', $act->note);


            if ($act->pullman_a == null) $act->pullman_a = 0;
            if ($act->pullman_r == null) $act->pullman_r = 0;
            if ($act->pullman_g == null) $act->pullman_g = 0;
            if ($act->pullman_ga == null) $act->pullman_ga = 0;
            if ($act->pullman_gr == null) $act->pullman_gr = 0;

            $alldata[] = $act;
        }
        $returnResult->result = 1;

        $returnCsv = [];
        foreach ($alldata as $single) {
            $singleData =
                $single->cognome . ';' .
                $single->nome . ';' .
                $single->maglia . ';' .
                $single->stanza . ';' .
                $single->note . ';' .
                $single->anno . ';' .
                $single->sesso . ';';
            $returnCsv[] = $singleData;
        }

        // $returnResult->returnObject = $alldata;
        $returnResult->returnObject = $returnCsv;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}



function recuperaContattiTurnoCsv($idTurno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *
                                 from dettagli_turni left outer join anag_base
                                      on dettagli_turni.idanag = anag_base.id
                                 where idturno = $idTurno
                                 order by anag_base.cognome, anag_base.nome
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $returnResult->returnMessages[] = $idTurno;

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Pagamento();
            $act->idturno = $row['idturno'];
            $act->idanag = $row['idanag'];

            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->sesso = $row['sesso'];
            $act->cellulare_1 = $row['cellulare_1'];
            $act->cellulare_2 = $row['cellulare_2'];
            $act->cellulare_3 = $row['cellulare_3'];
            $act->email_1 = $row['email_1'];
            $act->email_2 = $row['email_2'];

            $act->anno = "";

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

            $alldata[] = $act;
        }
        $returnResult->result = 1;

        $returnCsv = [];
        foreach ($alldata as $single) {
            $singleData =
                $single->cognome . ';' .
                $single->nome . ';' .
                $single->cellulare_1 . ';' .
                $single->cellulare_2 . ';' .
                $single->cellulare_3 . ';' .
                $single->email_1 . ';' .
                $single->email_2 . ';' .
                $single->anno . ';' .
                $single->sesso . ';';
            $returnCsv[] = $singleData;
        }

        // $returnResult->returnObject = $alldata;
        $returnResult->returnObject = $returnCsv;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function recuperaDettagliTurno($idTurno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *
                                 from dettagli_turni left outer join anag_base
                                      on dettagli_turni.idanag = anag_base.id

                                 where idturno = $idTurno
                                 order by anag_base.cognome, anag_base.nome
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $returnResult->returnMessages[] = $idTurno;

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Pagamento();
            $act->idturno = $row['idturno'];
            $act->idanag = $row['idanag'];
            $act->id_iscrizione = $row['id_iscrizione'];

            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->sesso = $row['sesso'];
            $act->pullman_a = $row['pullman_a'];
            $act->pullman_r = $row['pullman_r'];
            $act->pullman_g = $row['pullman_g'];
            $act->pullman_ga = $row['pullman_ga'];
            $act->pullman_gr = $row['pullman_gr'];
            $act->ruolo = $row['ruolo'];

            $act->maglia = $row['maglia'];
            $act->stanza = $row['stanza'];
            $act->note = $row['note'];

            if ($act->pullman_a == null) $act->pullman_a = 0;
            if ($act->pullman_r == null) $act->pullman_r = 0;
            if ($act->pullman_g == null) $act->pullman_g = 0;
            if ($act->pullman_ga == null) $act->pullman_ga = 0;
            if ($act->pullman_gr == null) $act->pullman_gr = 0;

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

function salvaDettagli($anagrafiche)
{

    try {

        error_log(print_r('salvaDettagli:', true), 0);
        error_log(print_r($anagrafiche, true), 0);

        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        error_log(print_r($anagrafiche, true), 0);

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT INTO dettagli_turni
                                (idturno, idanag,
                                    pullman_a, pullman_r, pullman_g,  pullman_ga,  pullman_gr,
                                    ruolo, maglia, stanza, note, id_iscrizione )
                            VALUES (:idturno, :idanag,
                                    :pullman_a, :pullman_r, :pullman_g, :pullman_ga, :pullman_gr,
                                    :ruolo, :maglia, :stanza, :note, :id_iscrizione )
                            ON DUPLICATE KEY UPDATE
                                   pullman_a =:pullman_a, pullman_r =:pullman_r,
                                   pullman_g =:pullman_g, pullman_ga =:pullman_ga, pullman_gr =:pullman_gr,
                                    ruolo = :ruolo,
                                    maglia = :maglia,
                                    stanza = :stanza,
                                    note = :note,
                                    id_iscrizione = :id_iscrizione
                    ");

        $stmtInsert->bindParam(':idanag', $idanag);
        $stmtInsert->bindParam(':idturno', $idturno);
        $stmtInsert->bindParam(':pullman_a', $pullman_a);
        $stmtInsert->bindParam(':pullman_r', $pullman_r);
        $stmtInsert->bindParam(':pullman_g', $pullman_g);
        $stmtInsert->bindParam(':pullman_ga', $pullman_ga);
        $stmtInsert->bindParam(':pullman_gr', $pullman_gr);
        $stmtInsert->bindParam(':ruolo', $ruolo);
        $stmtInsert->bindParam(':maglia', $maglia);
        $stmtInsert->bindParam(':stanza', $stanza);
        $stmtInsert->bindParam(':note', $note);
        $stmtInsert->bindParam(':id_iscrizione', $id_iscrizione);

        foreach ($anagrafiche as $anagrafica) {
            $idanag = $anagrafica[id];
            $idturno = $anagrafica[idturno];
            $pullman_a = $anagrafica[pullman_a];
            $pullman_r = $anagrafica[pullman_r];
            $pullman_g = $anagrafica[pullman_g];
            $pullman_ga = $anagrafica[pullman_ga];
            $pullman_gr = $anagrafica[pullman_gr];
            $ruolo = $anagrafica[ruolo];
            $maglia = $anagrafica[maglia];
            $stanza = $anagrafica[stanza];
            $note = $anagrafica[note];
            $id_iscrizione = $anagrafica[id_iscrizione];
            $stmtInsert->execute();
        }

        $returnResult->result = 1;
        $returnResult->success = 1;
        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        error_log(print_r('salvaDettagli:' . $e->getMessage() . '#', true), 0);

        $conn = null;
    }
    return $returnResult;
}


function salvaPagamenti($pagamenti)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        error_log(print_r($pagamenti, true), 0);

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT INTO pagamenti_turni
                                (idturno, idanag, quota_pagata, totale_da_pagare,
                                data, saldato)
                            VALUES (:idturno, :idanag, :quota_pagata, :totale_da_pagare,
                                :data, :saldato)
                    ");

        $stmtInsert->bindParam(':idanag', $idanag);
        $stmtInsert->bindParam(':idturno', $idturno);
        $stmtInsert->bindParam(':quota_pagata', $quota_pagata);
        $stmtInsert->bindParam(':totale_da_pagare', $totale_da_pagare);
        $stmtInsert->bindParam(':data', $data);
        $stmtInsert->bindParam(':saldato', $saldato);

        foreach ($pagamenti as $pagamento) {
            if ($pagamento[aggiornadb] == true) {

                $anagrafica = $pagamento[anagrafica];
                foreach ($pagamento[rate] as $rata) {
                    if ($rata[aggiornadb] == true) {

                        if ($rata[todelete] == true) {

                            $stmtDelete = $conn->prepare("DELETE from pagamenti_turni
                                                    WHERE id = :id ");
                            $stmtDelete->bindParam(':id', $iddele);
                            $iddele = $rata[id];
                            $stmtDelete->execute();
                        } else {
                            $idanag = $anagrafica[id];
                            $idturno = $pagamento[idturno];
                            $saldato = $pagamento[saldato];
                            $quota_pagata = $rata[quota];
                            $totale_da_pagare = $rata[totale_da_pagare];
                            $data = $rata[data];

                            $stmtInsert->execute();
                        }
                    }
                }
            }
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


function salvaMovimenti($movimenti)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT INTO movimenti_turni
                                (idturno, descrizione, tipologia,
                                 valore, data, dareavere, image)
                            VALUES (:idturno, :descrizione, :tipologia,
                                :valore, :data, :dareavere, :image)
                    ");

        $stmtInsert->bindParam(':idturno', $idturno);
        $stmtInsert->bindParam(':descrizione', $descrizione);
        $stmtInsert->bindParam(':tipologia', $tipologia);
        $stmtInsert->bindParam(':valore', $valore);
        $stmtInsert->bindParam(':data', $data);
        $stmtInsert->bindParam(':dareavere', $dareavere);
        $stmtInsert->bindParam(':image', $image);

        foreach ($movimenti as $movimento) {
            if ($movimento[aggiornadb] == true) {
                $idturno = $movimento[idturno];
                $descrizione = $movimento[descrizione];
                $tipologia = $movimento[tipologia];
                $data = $movimento[data];
                $valore = $movimento[valore];
                $dareavere = $movimento[dareavere];
                $image = $movimento[image];

                $stmtInsert->execute();
            }
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


function recuperaTuttiTurni($year = null, $gruppo = null)
{

    if ($year == null) {
        $data = ['chiave' =>  'anno_iscrizioni'];
        $completeResult = recuperaImpostazioni($data);

        error_log(print_r($completeResult, true), 0);

        $year = $completeResult->returnObject[0]->contenuto;
        error_log(print_r($year, true), 0);
    }

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *,turni.titolo, turni.id, turni.inizio, turni.fine,
                                    turni.preiscrizione, turni.idturnorif, turni.idturnopre,
                                    turni.diaria,

                                    turni.year,
                                    turni.id_pre_isc_online,
                                    turni.gruppo,
                                    turni.auto_gestione,
                                    turni.chiuso,
                                    turni.min_year,
                                    turni.max_year,
                                    turni.costo_totale,
                                    turni.specifica_date,
                                    turni.posti_max,
                                    turni.posti_max_totali,
                                    turni.accetta_edu_auto,
                                    turni.accetta_rag_auto,

                                 anag_base.id as idanag

                                 from turni left outer join anag_base
                                      on turni.idaccompagnatore1 = anag_base.id

                                      ";

        $and = '';
        if ($year != null || $gruppo != null) {
            $sqlString = $sqlString . " where ";
        }

        if ($year != null && $gruppo != null) {
            $and = " and ";
        }

        if ($year != null) {
            $sqlString = $sqlString . " year = $year  ";
            $and = " and ";
        }

        if ($gruppo != null) {
            $sqlString = $sqlString . " $and turni.gruppo = '$gruppo'  ";
            $and = " and ";
        }

        $sqlString = $sqlString . " ORDER BY inizio ASC , preiscrizione DESC
                                      ";


        $returnResult->returnMessages[] = $sqlString;

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Turno();

            $act->titolo = $row['titolo'];
            $act->id = $row['id'];
            $act->idturnorif = $row['idturnorif'];
            $act->idturnopre = $row['idturnopre'];
            $act->diaria = $row['diaria'];

            $act->year = $row['year'];
            $act->id_pre_isc_online = $row['id_pre_isc_online'];
            $act->gruppo = $row['gruppo'];
            $act->auto_gestione = $row['auto_gestione'];

            $act->min_year = $row['min_year'];
            $act->max_year = $row['max_year'];

            if ( $act->min_year == "0" || $act->min_year == NULL) {
                $act->min_year = "1900";
            }
            if ( $act->max_year == "0" || $act->max_year == NULL) {
                $act->max_year = "2200";
            }

            if ($act->auto_gestione == "0") {
                $act->auto_gestione = false;
            } else {
                $act->auto_gestione = true;
            }

            $act->chiuso = $row['chiuso'];
            if ($act->chiuso == "0") {
                $act->chiuso = false;
            } else {
                $act->chiuso = true;
            }

            $act->specifica_date = $row['specifica_date'];
            if ($act->specifica_date == "0") {
                $act->specifica_date = false;
            } else {
                $act->specifica_date = true;
            }
            $act->costo_totale = $row['costo_totale'];
            $act->posti_max = $row['posti_max'];
            $act->posti_max_totali = $row['posti_max_totali'];
            $act->accetta_edu_auto = $row['accetta_edu_auto'];
            $act->accetta_rag_auto = $row['accetta_rag_auto'];

            if ($act->accetta_edu_auto == "0") {
                $act->accetta_edu_auto = false;
            } else {
                $act->accetta_edu_auto = true;
            }
            if ($act->accetta_rag_auto == "0") {
                $act->accetta_rag_auto = false;
            } else {
                $act->accetta_rag_auto = true;
            }

            $act->preiscrizione = $row['preiscrizione'];
            $inizio = DateTime::createFromFormat("Y-m-d", $row['inizio']);
            $fine = DateTime::createFromFormat("Y-m-d", $row['fine']);
            $act->inizio = $inizio->format("Y-m-d");
            $act->fine = $fine->format("Y-m-d");

            try {
                if ($row['inizio_isc'] !== NULL && $row['inizio_isc'] !== '') {
                    $inizio_isc = DateTime::createFromFormat("Y-m-d", $row['inizio_isc']);
                    $fine_isc = DateTime::createFromFormat("Y-m-d", $row['fine_isc']);
                    $act->inizio_isc = $inizio_isc->format("Y-m-d");
                    $act->fine_isc = $fine_isc->format("Y-m-d");
                    $today = new DateTime();
                    $act->isc_aperte = true;
                    if ($today > $fine_isc) {
                        $act->isc_aperte = false;
                    }
                    if ($today < $inizio_isc) {
                        $act->isc_aperte = false;
                    }
                }
            } catch (Exception $err) { }

            $act->idacc1 = $row['idanag'];
            $act->acc1_nome = $row['nome'];
            $act->acc1_cognome = $row['cognome'];

            $act->anag_acc1 = new Anagrafica();

            $act->anag_acc1->nome = $row['nome'];
            $act->anag_acc1->cognome = $row['cognome'];
            $act->anag_acc1->data_nascita = $row['data_nascita'];
            $act->anag_acc1->sesso = $row['sesso'];
            $act->anag_acc1->comune_nascita = $row['comune_nascita'];
            $act->anag_acc1->provincia_nascita = $row['provincia_nascita'];
            $act->anag_acc1->stato_nascita = $row['stato_nascita'];
            $act->anag_acc1->cittadinanza = $row['cittadinanza'];

            $act->anag_acc1->comune_n_ques = $row['comune_n_ques'];
            $act->anag_acc1->provincia_n_ques = $row['provincia_n_ques'];
            $act->anag_acc1->stato_n_ques = $row['stato_n_ques'];
            $act->anag_acc1->cittadinanza_n_ques = $row['cittadinanza_n_ques'];

            $act->anag_acc1->doc_tipo = $row['doc_tipo'];
            $act->anag_acc1->doc_numero = $row['doc_numero'];
            $act->anag_acc1->doc_comune_ril = $row['doc_comune_ril'];
            $act->anag_acc1->doc_provincia_ril = $row['doc_provincia_ril'];
            $act->anag_acc1->doc_stato_ril = $row['doc_stato_ril'];

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



function recuperaTurni($year = null, $gruppo = null)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *,turni.titolo, turni.id, turni.inizio, turni.fine,
                                    turni.preiscrizione, turni.idturnorif, turni.idturnopre,
                                    turni.diaria,

                                    turni.year,
                                    turni.id_pre_isc_online,
                                    turni.gruppo,
                                    turni.auto_gestione,
                                    turni.chiuso,
                                    turni.min_year,
                                    turni.max_year,

                                    turni.costo_totale,
                                    turni.specifica_date,
                                    turni.posti_max,

                                 anag_base.id as idanag

                                 from turni left outer join anag_base
                                      on turni.idaccompagnatore1 = anag_base.id

                                      ";

        $and = '';
        if ($year != null || $gruppo != null) {
            $sqlString = $sqlString . " where ";
        }

        if ($year != null && $gruppo != null) {
            $and = " and ";
        }

        if ($year != null) {
            $sqlString = $sqlString . " year = $year  ";
            $and = " and ";
        }

        if ($gruppo != null) {
            $sqlString = $sqlString . " $and turni.gruppo = '$gruppo'  ";
            $and = " and ";
        }

        $sqlString = $sqlString . " $and turni.chiuso = '0'  ";

        $sqlString = $sqlString . " ORDER BY inizio ASC , preiscrizione DESC
                                      ";


        $returnResult->returnMessages[] = $sqlString;

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Turno();

            $act->titolo = $row['titolo'];
            $act->id = $row['id'];
            $act->idturnorif = $row['idturnorif'];
            $act->idturnopre = $row['idturnopre'];
            $act->diaria = $row['diaria'];

            $act->year = $row['year'];
            $act->id_pre_isc_online = $row['id_pre_isc_online'];
            $act->gruppo = $row['gruppo'];
            $act->auto_gestione = $row['auto_gestione'];

            $act->min_year = $row['min_year'];
            $act->max_year = $row['max_year'];

            if ( $act->min_year == "0" || $act->min_year == NULL) {
                $act->min_year = "1900";
            }
            if ( $act->max_year == "0" || $act->max_year == NULL) {
                $act->max_year = "2200";
            }

            if ($act->auto_gestione == "0") {
                $act->auto_gestione = false;
            } else {
                $act->auto_gestione = true;
            }

            $act->chiuso = $row['chiuso'];
            if ($act->chiuso == "0") {
                $act->chiuso = false;
            } else {
                $act->chiuso = true;
            }

            $act->specifica_date = $row['specifica_date'];
            if ($act->specifica_date == "0") {
                $act->specifica_date = false;
            } else {
                $act->specifica_date = true;
            }

            $act->costo_totale = $row['costo_totale'];
            $act->posti_max = $row['posti_max'];
            $act->posti_max_totali = $row['posti_max_totali'];
            $act->accetta_edu_auto = $row['accetta_edu_auto'];
            $act->accetta_rag_auto = $row['accetta_rag_auto'];

            $act->preiscrizione = $row['preiscrizione'];
            $inizio = DateTime::createFromFormat("Y-m-d", $row['inizio']);
            $fine = DateTime::createFromFormat("Y-m-d", $row['fine']);
            $act->inizio = $inizio->format("Y-m-d");
            $act->fine = $fine->format("Y-m-d");

            try {
                if ($row['inizio_isc'] !== NULL && $row['inizio_isc'] !== '') {
                    $inizio_isc = DateTime::createFromFormat("Y-m-d", $row['inizio_isc']);
                    $fine_isc = DateTime::createFromFormat("Y-m-d", $row['fine_isc']);
                    $act->inizio_isc = $inizio_isc->format("Y-m-d");
                    $act->fine_isc = $fine_isc->format("Y-m-d");
                    $today = new DateTime();
                    $act->isc_aperte = true;
                    if ($today > $fine_isc) {
                        $act->isc_aperte = false;
                    }
                    if ($today < $inizio_isc) {
                        $act->isc_aperte = false;
                    }
                }
            } catch (Exception $err) { }

            $act->idacc1 = $row['idanag'];
            $act->acc1_nome = $row['nome'];
            $act->acc1_cognome = $row['cognome'];

            $act->anag_acc1 = new Anagrafica();

            $act->anag_acc1->nome = $row['nome'];
            $act->anag_acc1->cognome = $row['cognome'];
            $act->anag_acc1->data_nascita = $row['data_nascita'];
            $act->anag_acc1->sesso = $row['sesso'];
            $act->anag_acc1->comune_nascita = $row['comune_nascita'];
            $act->anag_acc1->provincia_nascita = $row['provincia_nascita'];
            $act->anag_acc1->stato_nascita = $row['stato_nascita'];
            $act->anag_acc1->cittadinanza = $row['cittadinanza'];

            $act->anag_acc1->comune_n_ques = $row['comune_n_ques'];
            $act->anag_acc1->provincia_n_ques = $row['provincia_n_ques'];
            $act->anag_acc1->stato_n_ques = $row['stato_n_ques'];
            $act->anag_acc1->cittadinanza_n_ques = $row['cittadinanza_n_ques'];

            $act->anag_acc1->doc_tipo = $row['doc_tipo'];
            $act->anag_acc1->doc_numero = $row['doc_numero'];
            $act->anag_acc1->doc_comune_ril = $row['doc_comune_ril'];
            $act->anag_acc1->doc_provincia_ril = $row['doc_provincia_ril'];
            $act->anag_acc1->doc_stato_ril = $row['doc_stato_ril'];

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


function recuperaDatiTurnoCsv($idTurno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages[] = "idturno = $idTurno";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT DISTINCT nome, cognome, data_nascita from presenze_turni
                            where idturno = $idTurno
                              and presente = 1
                              group by nome, cognome, data_nascita
                            ";

        $sqlString = "SELECT *, presenze_turni.id as firstid
                                from presenze_turni
                                    inner join anag_base
                                  on presenze_turni.idanag = anag_base.id
                            where idturno = '" . $idTurno . "'
                              order by anag_base.cognome, anag_base.nome, anag_base.data_nascita
                            ";

        // check pre-iscrizioni - turno non ancora confermato
        $sqlString = "SELECT count(*) as nr_pres from presenze_turni                                    
                            where idturno = '" . $idTurno . "'
                            ";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();

        $isc_fatte = false;
        foreach ($rows as $row) {
            if($row[nr_pres] > 0) {
                $isc_fatte = true;
            }
        }

        if ($isc_fatte == true) {
            $sqlString = "SELECT
                pr_turno.inizio, pr_turno.fine,
                presenze_turni.*, anag_base.*,
                presenze_turni.id as firstid,
                pr_turno.idturnorif as idturnoconf,
                pres_turno_conf.data as data_conf

                from presenze_turni
                    inner join anag_base
                        on presenze_turni.idanag = anag_base.id
                    left outer join turni as pr_turno
                        on pr_turno.id = presenze_turni.idturno
                    left outer join presenze_turni as pres_turno_conf
                        on pres_turno_conf.idturno = pr_turno.idturnorif
                        and pres_turno_conf.data = presenze_turni.data
                        and pres_turno_conf.idanag = presenze_turni.idanag

            where presenze_turni.idturno = '" . $idTurno . "'
            order by anag_base.cognome, anag_base.nome, anag_base.data_nascita";
        } else {
            $sqlString = "SELECT
                iscrizioni.surname as cognome,
                iscrizioni.name as nome,
                iscrizioni.birthday as data_nascita,
                pr_turno.inizio as data,
                pr_turno.inizio, pr_turno.fine,
                iscrizioni.*, iscrizioni_dettaglio.*,
                iscrizioni_dettaglio.firChoice as idturnoconf
                
                from iscrizioni inner join iscrizioni_dettaglio
                         on iscrizioni.id = iscrizioni_dettaglio.id_iscrizione
                    left outer join turni as pr_turno
                        on pr_turno.id = iscrizioni_dettaglio.firChoice

            where iscrizioni_dettaglio.firChoice = '" . $idTurno . "'
                and ( iscrizioni_dettaglio.status = 'REG' or iscrizioni_dettaglio.status = 'WIL' )
                and iscrizioni.is_deleted <> 1
            order by iscrizioni.surname, 
                    iscrizioni.name, iscrizioni.birthday";
        }


        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Presenza();

            $inizio_t = $row['inizio'];
            $fine_t = $row['fine'];

            $dataInizio = DateTime::createFromFormat("Y-m-d", $row['inizio']);
            $metaTurno = $dataInizio;
            date_add($metaTurno, date_interval_create_from_date_string('7 days'));

            $act->id = $row['firstid'];
            $act->idturno = $row['idturno'];
            $act->idanag = $row['idanag'];
            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->data_nascita = $row['data_nascita'];
            $act->sesso = $row['sesso'];

            $act->maschio = 0;
            $act->femmina = 0;
            if ($act->sesso == 'M') {
                $act->maschio = 1;
            } else {
                $act->femmina = 1;
            }

            $act->meta_turno = $metaTurno;

            $act->ruolo = $row['ruolo'];
            $act->accompagnatore = $row['accompagnatore'];

            $saldato = 0;
            if ($row['saldato'] != null) {
                $saldato = $row['saldato'];
            } else {
                $saldato = $row['saldato_2'];
            }

            if ($saldato == null) $act->stato_pagamento = "N";
            else {
                if ($saldato >= 1) {
                    $act->stato_pagamento = "S";
                } else {
                    $act->stato_pagamento = "P";
                }
            }

            $dataNas = DateTime::createFromFormat("Y-m-d", $row['data_nascita']);
            $act->anno_nascita = $dataNas->format("Y");

            $act->settimana_1 = 0;
            $act->settimana_2 = 0;

            $dataSel = DateTime::createFromFormat("Y-m-d", $row['data']);
            $dataPresenza = new DataPresenza();
            $dataPresenza->dataS = $dataSel->format("Y-m-d");
            $dataPresenza->data = $dataSel->format("D M d Y");
            $dataPresenza->presente = $row['presente'];
            $dataPresenza->id = $row['firstid'];
            $dataPresenza->data_conf = $row['data_conf'];

            $found = false;
            foreach ($alldata as $presenza) {
                if (
                    $presenza->nome == $act->nome &&
                    $presenza->cognome == $act->cognome &&
                    $presenza->data_nascita == $act->data_nascita
                ) {
                    $found = true;
                    if ($dataPresenza->presente == 1) {
                        if ($dataSel > $metaTurno) {
                            $presenza->settimana_2 = 1;
                        } else {
                            $presenza->settimana_1 = 1;
                        }
                    }
                    $presenza->datePresenza[] = $dataPresenza;
                }
            }

            if ($isc_fatte == true) {
            } else {
                $act->settimana_1 = 1;
                $act->settimana_2 = 1;
            }

            $check_position = false;
            if ($act->ruolo == 'R'){
                $check_position = true;
            }

            $data_isc = null;
            $data_update = null;
            if($isc_fatte == false && $check_position == true){
                $data_isc = $row['db_insert_at'];
                $act->data_isc = $data_isc;
            }
            if($isc_fatte == false && $check_position == true) {
                $stmtSearch = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                            as position
                            from iscrizioni_dettaglio  inner join iscrizioni
                                on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
                        where db_insert_at < '$data_isc'
                        and ( status = 'REG' )
                        and firChoice = '$idTurno'
                        and ruolo = 'R'
                        and is_deleted = 0
                            ");

                $position = 1;
                $stmtSearch->execute();
                $rowsCount = $stmtSearch->fetchAll();
                foreach ($rowsCount as $rowCount) {
                    $position = $rowCount[position] + 1;
                }

                if($position > 50) $found = true;
                $act->position = $position;
            }

            if ($found == false) {
                $act->datePresenza[] = $dataPresenza;

                if ($dataPresenza->presente == 1) {
                    if ($dataSel > $metaTurno) {
                        $act->settimana_2 = 1;
                    } else {
                        $act->settimana_1 = 1;
                    }
                }
                $alldata[] = $act;
            }

        }
        $returnResult->result = 1;

        $returnCsv = [];
        foreach ($alldata as $single) {
            $singleData =
                $single->cognome . ';' .
                $single->nome . ';' .
                $single->anno_nascita . ';' .
                $single->ruolo . ';' .
                $single->settimana_1 . ';' .
                $single->settimana_2 . ';' .
                $single->maschio . ';' .
                $single->femmina . ';';

            // $singleObj = new stdClass();
            // $singleObj['nome'] = $single->nome;

            $returnCsv[] = $singleData;
            // $returnCsv[] = $singleObj;
        }

        // $returnResult->returnObject = $alldata;
        $returnResult->returnObject = $returnCsv;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaTuttiDatiTurnoCsv($idTurno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages[] = "idturno = $idTurno";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT DISTINCT nome, cognome, data_nascita from presenze_turni
                            where idturno = $idTurno
                              and presente = 1
                              group by nome, cognome, data_nascita
                            ";

        $sqlString = "SELECT *, presenze_turni.id as firstid
                                from presenze_turni
                                    inner join anag_base
                                  on presenze_turni.idanag = anag_base.id
                            where idturno = '" . $idTurno . "'
                              order by anag_base.cognome, anag_base.nome, anag_base.data_nascita
                            ";

        $sqlString = "SELECT
                                pr_turno.inizio, pr_turno.fine,
                                presenze_turni.*, anag_base.*,
                                presenze_turni.id as firstid,
			                    pr_turno.idturnorif as idturnoconf,
			                    pres_turno_conf.data as data_conf,
                                dettagli_turni.*

                                from presenze_turni
                                    inner join anag_base
                                  		on presenze_turni.idanag = anag_base.id
                                    left outer join turni as pr_turno
                                        on pr_turno.id = presenze_turni.idturno
                                    left outer join presenze_turni as pres_turno_conf
                                        on pres_turno_conf.idturno = pr_turno.idturnorif
                                        and pres_turno_conf.data = presenze_turni.data
                                        and pres_turno_conf.idanag = presenze_turni.idanag

                                    left outer join dettagli_turni
                                      on dettagli_turni.idanag = anag_base.id
                                      and dettagli_turni.idturno = presenze_turni.idturno

                            where presenze_turni.idturno = '" . $idTurno . "'
                              order by anag_base.cognome, anag_base.nome, anag_base.data_nascita";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Presenza();

            $inizio_t = $row['inizio'];
            $fine_t = $row['fine'];

            $dataInizio = DateTime::createFromFormat("Y-m-d", $row['inizio']);
            $metaTurno = $dataInizio;
            date_add($metaTurno, date_interval_create_from_date_string('7 days'));

            $act->id = $row['firstid'];
            $act->idturno = $row['idturno'];
            $act->idanag = $row['idanag'];
            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->data_nascita = $row['data_nascita'];
            $act->sesso = $row['sesso'];

            $act->maschio = 0;
            $act->femmina = 0;
            if ($act->sesso == 'M') {
                $act->maschio = 1;
            } else {
                $act->femmina = 1;
            }

            $act->meta_turno = $metaTurno;

            $act->ruolo = $row['ruolo'];
            $act->accompagnatore = $row['accompagnatore'];

            $saldato = 0;
            if ($row['saldato'] != null) {
                $saldato = $row['saldato'];
            } else {
                $saldato = $row['saldato_2'];
            }

            if ($saldato == null) $act->stato_pagamento = "N";
            else {
                if ($saldato >= 1) {
                    $act->stato_pagamento = "S";
                } else {
                    $act->stato_pagamento = "P";
                }
            }

            $dataNas = DateTime::createFromFormat("Y-m-d", $row['data_nascita']);
            $act->anno_nascita = $dataNas->format("Y");

            $act->settimana_1 = 0;
            $act->settimana_2 = 0;


            $act->cellulare_1 = $row['cellulare_1'];
            $act->cellulare_2 = $row['cellulare_2'];
            $act->cellulare_3 = $row['cellulare_3'];
            $act->email_1 = $row['email_1'];
            $act->email_2 = $row['email_2'];

            $act->pullman_a = $row['pullman_a'];
            $act->pullman_r = $row['pullman_r'];
            $act->pullman_ga = $row['pullman_ga'];
            $act->pullman_gr = $row['pullman_gr'];

            if ($act->pullman_a  == '') $act->pullman_a = 0;
            if ($act->pullman_ga  == '') $act->pullman_ga = 0;
            if ($act->pullman_r  == '') $act->pullman_r = 0;
            if ($act->pullman_gr  == '') $act->pullman_gr = 0;

            $dataSel = DateTime::createFromFormat("Y-m-d", $row['data']);
            $dataPresenza = new DataPresenza();
            $dataPresenza->dataS = $dataSel->format("Y-m-d");
            $dataPresenza->data = $dataSel->format("D M d Y");
            $dataPresenza->presente = $row['presente'];
            $dataPresenza->id = $row['firstid'];
            $dataPresenza->data_conf = $row['data_conf'];

            $found = false;
            foreach ($alldata as $presenza) {
                if (
                    $presenza->nome == $act->nome &&
                    $presenza->cognome == $act->cognome &&
                    $presenza->data_nascita == $act->data_nascita
                ) {
                    $found = true;
                    if ($dataPresenza->presente == 1) {
                        if ($dataSel > $metaTurno) {
                            $presenza->settimana_2 = 1;
                        } else {
                            $presenza->settimana_1 = 1;
                        }
                    }
                    $presenza->datePresenza[] = $dataPresenza;
                }
            }

            if ($found == false) {
                $act->datePresenza[] = $dataPresenza;

                if ($dataPresenza->presente == 1) {
                    if ($dataSel > $metaTurno) {
                        $act->settimana_2 = 1;
                    } else {
                        $act->settimana_1 = 1;
                    }
                }
                $alldata[] = $act;
            }
        }
        $returnResult->result = 1;

        $returnCsv = [];

        $singleData =
            'cognome' . ';' .
            'nome' . ';' .
            'anno_nascita' . ';' .
            'ruolo' . ';' .
            'settimana_1' . ';' .
            'settimana_2' . ';' .
            'maschio' . ';' .
            'femmina' . ';' .

            'cellulare_1' . ';' .
            'cellulare_2' . ';' .
            'cellulare_3' . ';' .
            'email_1' . ';' .
            'email_2' . ';' .

            'pullman_a' . ';' .
            'pullman_r' . ';' .
            'pullman_ga'  . ';' .
            'pullman_gr'  . ';' .
            'data_nascita' . ';' 

            ;

            $returnCsv[] = $singleData;

        foreach ($alldata as $single) {
            $singleData =
                $single->cognome . ';' .
                $single->nome . ';' .
                $single->anno_nascita . ';' .
                $single->ruolo . ';' .
                $single->settimana_1 . ';' .
                $single->settimana_2 . ';' .
                $single->maschio . ';' .
                $single->femmina . ';' .

                $single->cellulare_1 . ';' .
            $single->cellulare_2 . ';' .
            $single->cellulare_3 . ';' .
            $single->email_1 . ';' .
            $single->email_2 . ';' .

            $single->pullman_a . ';' .
            $single->pullman_r  . ';' .
            $single->pullman_ga  . ';' .
            $single->pullman_gr  . ';'.
            $single->data_nascita  . ';' .

            ''

            ;

            // $singleObj = new stdClass();
            // $singleObj['nome'] = $single->nome;

            $returnCsv[] = $singleData;
            // $returnCsv[] = $singleObj;
        }

        // $returnResult->returnObject = $alldata;
        $returnResult->returnObject = $returnCsv;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function recuperaPresenzaTurno($idTurno, $returnDates = true)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages[] = "idturno = $idTurno";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT DISTINCT nome, cognome, data_nascita from presenze_turni
                            where idturno = $idTurno
                              and presente = 1
                              group by nome, cognome, data_nascita
                            ";

        $sqlString = "SELECT *, presenze_turni.id as firstid
                                from presenze_turni
                                    inner join anag_base
                                  on presenze_turni.idanag = anag_base.id
                            where idturno = '" . $idTurno . "'
                              order by anag_base.cognome, anag_base.nome, anag_base.data_nascita
                            ";

        $sqlString = "SELECT presenze_turni.*, anag_base.*,
                                 presenze_turni.creato_il as pres_creato_il,
                                presenze_turni.id as firstid,
			                    pr_turno.idturnorif as idturnoconf,
			                    pr_turno.inizio as inizio_turno,
                                pr_turno.fine as fine_turno,
                                pr_turno.titolo as titolo_turno,
			                    -- pres_turno_conf.data as data_conf,

                                (select sum(saldato) from pagamenti_turni where idturno = idturnoconf
                                        and idanag = presenze_turni.idanag
                                            group by idanag ) as saldato,
                                (select sum(quota_pagata) from pagamenti_turni where idturno = idturnoconf
                                        and idanag = presenze_turni.idanag
                                            group by idanag ) as quota_pagata,

                                (select sum(saldato) from pagamenti_turni where idturno = '" . $idTurno . "'
                                        and idanag = presenze_turni.idanag
                                            group by idanag ) as saldato_2,
                                (select sum(quota_pagata) from pagamenti_turni where idturno = '" . $idTurno . "'
                                        and idanag = presenze_turni.idanag
                                            group by idanag ) as quota_pagata_2

                                from presenze_turni
                                    inner join anag_base
                                          on presenze_turni.idanag = anag_base.id

                                    left outer join turni as pr_turno
                                        on pr_turno.id = presenze_turni.idturno

                                    /*
                                    left outer join presenze_turni as pres_turno_conf
                                        on pres_turno_conf.idturno = pr_turno.idturnorif
                                        and pres_turno_conf.data = presenze_turni.data
                                        and pres_turno_conf.idanag = presenze_turni.idanag
                                        */

                            where presenze_turni.idturno = '" . $idTurno . "'
                              order by anag_base.cognome, anag_base.nome, anag_base.data_nascita";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Presenza();

            $act->id = $row['firstid'];
            $act->idturno = $row['idturno'];
            $act->defturno = $row['titolo_turno'];
            $act->idanag = $row['idanag'];
            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->data_nascita = $row['data_nascita'];
            $act->sesso = $row['sesso'];
            $act->comune_nascita = $row['comune_nascita'];
            $act->provincia_nascita = $row['provincia_nascita'];
            $act->stato_nascita = $row['stato_nascita'];
            $act->cittadinanza = $row['cittadinanza'];

            $act->comune_n_ques = $row['comune_n_ques'];
            $act->provincia_n_ques = $row['provincia_n_ques'];
            $act->stato_n_ques = $row['stato_n_ques'];
            $act->cittadinanza_n_ques = $row['cittadinanza_n_ques'];

            $act->doc_tipo = $row['doc_tipo'];
            $act->doc_numero = $row['doc_numero'];
            $act->doc_comune_ril = $row['doc_comune_ril'];
            $act->doc_provincia_ril = $row['doc_provincia_ril'];
            $act->doc_stato_ril = $row['doc_stato_ril'];
            $act->id_iscrizione = $row['id_iscrizione'];

            $act->ruolo = $row['ruolo'];
            $act->accompagnatore = $row['accompagnatore'];

            $saldato = 0;
            if ($row['saldato'] != null) {
                $saldato = $row['saldato'];
            } else {
                $saldato = $row['saldato_2'];
            }

            if ($saldato == null) $act->stato_pagamento = "N";
            else {
                if ($saldato >= 1) {
                    $act->stato_pagamento = "S";
                } else {
                    $act->stato_pagamento = "P";
                }
            }

            $inizio_turno = DateTime::createFromFormat("Y-m-d", $row['inizio_turno']);
            $fine_turno = DateTime::createFromFormat("Y-m-d", $row['fine_turno']);
            $meta_turno = $inizio_turno;
            date_add($meta_turno, date_interval_create_from_date_string('8 days'));

            error_log(print_r('$meta_turno:', true), 0);
            error_log(print_r($meta_turno, true), 0);

            $dataSel = DateTime::createFromFormat("Y-m-d", $row['data']);
            $dataPresenza = new DataPresenza();
            $dataPresenza->dataS = $dataSel->format("Y-m-d");
            $dataPresenza->data = $dataSel->format("D M d Y");
            $dataPresenza->presente = $row['presente'];
            $dataPresenza->id = $row['firstid'];
            $dataPresenza->data_conf = $row['data_conf'];
            $dataPresenza->creato_il = $row['pres_creato_il'];
            $dataPresenza->modificato_il = $row['modificato_il'];

            if ($dataSel < $meta_turno && $dataPresenza->presente === '1') {
                $act->settimana_1 = 1;
            }
            if ($dataSel > $meta_turno && $dataPresenza->presente === '1') {
                $act->settimana_2 = 1;
            }


            $found = false;
            foreach ($alldata as &$presenza) {
                if (
                    $presenza->nome == $act->nome &&
                    $presenza->cognome == $act->cognome &&
                    $presenza->data_nascita == $act->data_nascita
                ) {
                    $found = true;
                    $presenza->datePresenza[] = $dataPresenza;
                    if ($dataPresenza->creato_il > $act->ultima_modifica) {
                        $act->data_inserimento = $dataPresenza->creato_il;
                    }

                    if ($dataSel < $meta_turno && $dataPresenza->presente === '1') {
                        $presenza->settimana_1 = 1;
                    }
                    if ($dataSel > $meta_turno && $dataPresenza->presente === '1') {
                        $presenza->settimana_2 = 1;
                    }
                    break;
                }
            }

            if ($found == false) {
                $act->datePresenza[] = $dataPresenza;
                $act->data_inserimento = $dataPresenza->creato_il;
                $alldata[] = $act;
            }
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


function recuperaBloccoDate($idTurno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages[] = "idturno = $idTurno";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT *
                                from blocca_presenze_turni
                            where idturno = '" . $idTurno . "'
                            ";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new BloccoData();

            $act->idturno = $row['idturno'];
            $act->data = $row['data'];
            $act->bloccato = $row['bloccato'];

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

function salvaBloccoDate($blocchi)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        error_log(print_r($blocchi, true), 0);

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT INTO blocca_presenze_turni
                                (idturno, data, bloccato, username)
                            VALUES (:idturno, :data, :bloccato, :username )
                            ON DUPLICATE KEY UPDATE
                                   bloccato =:bloccato, username = :username
                    ");

        $stmtInsert->bindParam(':idturno', $idturno);
        $stmtInsert->bindParam(':data', $data);
        $stmtInsert->bindParam(':bloccato', $bloccato);
        $stmtInsert->bindParam(':username', $username);

        foreach ($blocchi as $blocco) {
            $idturno = $blocco[idturno];
            $data = $blocco[dataS];
            $bloccato = $blocco[bloccato];
            $username = $blocco[username];
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



function salvaTurnoDatiBase($iturno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages[] = $iturno;

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("UPDATE turni
                                SET idaccompagnatore1 = :idaccompagnatore1
                                WHERE id = :idturno
                    ");

        $stmtUpdate->bindParam(':idaccompagnatore1', $idaccompagnatore1);
        $stmtUpdate->bindParam(':idturno', $idturno);

        $idturno = $iturno[id];
        $anagrafica = $iturno["anag_acc1"];
        $idaccompagnatore1 = $anagrafica[id];

        $stmtUpdate->execute();

        $returnResult->result = 1;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        error_log("returnMessages:", 0);
        error_log(print_r($e->getMessage(), true), 0);


        $conn = null;
    }

    return $returnResult;
}


function salvaPresenzaTurno($presenze)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages[] = $presenze;

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT INTO presenze_turni
                                (idturno, idanag, data, presente, creato_il, id_iscrizione)
                            VALUES (:idturno, :idanag, :data, :presente, NOW(), :id_iscrizione )
                    ");

        $stmtUpdate = $conn->prepare("UPDATE presenze_turni
                                SET presente = :presente,
                                    modificato_il = NOW()
                                WHERE id = :id
                    ");

        $stmtDelete = $conn->prepare("DELETE from presenze_turni
                                WHERE idturno = :idturno
                                  AND idanag = :idanag
                    ");

        foreach ($presenze as $act) {

            $anagrafica = $act["anagrafica"];
            $datePresenza = $act["datePresenza"];

            $rimuovi = $act["rimuovi"];

            $returnResult->returnMessages[] = json_encode($rimuovi);

            foreach ($datePresenza as $dataPresenza) {

                $stmtInsert->bindParam(':idturno', $idturno);
                $stmtInsert->bindParam(':idanag', $idanag);

                $stmtInsert->bindParam(':data', $dataPr);
                $stmtInsert->bindParam(':presente', $presente);
                $stmtInsert->bindParam(':id_iscrizione', $id_iscrizione);

                $stmtUpdate->bindParam(':id', $id);
                $stmtUpdate->bindParam(':presente', $presente);

                $stmtDelete->bindParam(':idturno', $idturno);
                $stmtDelete->bindParam(':idanag', $idanag);

                // insert a row
                $idturno = $act[idturno];
                $idanag = $anagrafica[id];
                $id = $dataPresenza[id];
                $dataPr = $dataPresenza[dataS];
                $presente = $dataPresenza[presente];
                $id_iscrizione = $act[id_iscrizione];

                if ($dataPresenza[id] != null) {
                    if ($rimuovi) {
                        $stmtDelete->execute();
                    } else {
                        $stmtUpdate->execute();
                    }
                } else {
                    if ($rimuovi) { } else {
                        $stmtInsert->execute();
                    }
                }
            }

            if ($rimuovi) {
                if ($act[id_iscrizione] !== null && $act[id_iscrizione] !== '') {
                    aggiornaStatoIscrizione($act[id_iscrizione], 'REG');
                }
            } else {
                if ($act[id_iscrizione] !== null && $act[id_iscrizione] !== '') {
                    $returnStatus = aggiornaStatoIscrizione($act[id_iscrizione], 'CONF');
                    if ($returnStatus->success === 1) {

                        /*
                        $returnInvio = recuperaTestoImpostazione("invia_mail_conferma");
                        if ($returnInvio->returnObject === 'X') {
                            $mailInfo = recuperaInfoMailConfermaIsc($act);
                            invioMail(recuperaMailsDaIsc($act[id_iscrizione]), $mailInfo->oggetto, $mailInfo->testo);
                        }
                        */

                        aggiornaDettagliDaIscrizione($act[id_iscrizione]);
                    }
                }
            }
        }

        $returnResult->result = 1;
        //$returnResult->returnObject = $alldata;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        error_log("returnMessages:", 0);
        error_log(print_r($e->getMessage(), true), 0);


        $conn = null;
    }

    return $returnResult;
}

function aggiornaDettagliDaIscrizione($id_iscrizione)
{
    $returnResult = new ServiceResult();
    $returnResult->returnMessages[] = "Start operation";
    // recupero i dati dall'iscrizione
    try {
        $returnResult = recuperaDatiIscrizione($id_iscrizione);

        error_log(print_r('aggiornaDettagliDaIscrizione:', true), 0);
        error_log(print_r($returnResult, true), 0);

        if ($returnResult->success == 1) {

            $anagrafica = $returnResult->returnObject;
            $act = new Pagamento();

            $act->idanag = $anagrafica->id_anag_base;
            $act->id_iscrizione = $anagrafica->id;
            $act->id = $anagrafica->id_anag_base;
            $act->idturno = $anagrafica->firChoice;
            if ($anagrafica->pullman == 1) {
                if ($anagrafica->firWeek1 == 1 && $anagrafica->firWeek2 == 1) {
                    $act->pullman_a = 1;
                    $act->pullman_r = 1;
                }
                if ($anagrafica->firWeek1 == 1 && $anagrafica->firWeek2 == 0) {
                    $act->pullman_a = 1;
                    $act->pullman_gr = 1;
                }
                if ($anagrafica->firWeek1 == 0 && $anagrafica->firWeek2 == 1) {
                    $act->pullman_ga = 1;
                    $act->pullman_r = 1;
                }
            }

            $act->ruolo = $anagrafica->ruolo;
            $act->stanza = $anagrafica->stanza;
            $act->note = $anagrafica->note;
            $act->maglia = $anagrafica->maglia;

            $act = json_encode($act, true);
            $act = json_decode($act, true);
            $anagrafiche = [];
            $anagrafiche[] = $act;

            error_log(print_r('aggiornaDettagliDaIscrizione:', true), 0);

            $returnResult = salvaDettagli($anagrafiche);
        }
    } catch (Exception $e) {
        error_log(print_r('aggiornaDettagliDaIscrizione:', true), 0);
        error_log(print_r($e->getMessage(), true), 0);
    }

    return $returnResult;
}

function recuperaMailsDaIsc($id_iscrizione)
{

    try {

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT utenti.username as email0,
                             utenti.email1,
                             utenti.email2

                                from iscrizioni inner join utenti
                                     on iscrizioni.created_by = utenti.id
                            where iscrizioni.id = '" . $id_iscrizione . "'
                            ";

        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {
            $alldata[] = $row['email0'];
            $alldata[] = $row['email1'];
            $alldata[] = $row['email2'];
        }

        $conn = null;

        // error_log("Mails:", 0);
        // error_log(print_r($alldata, true), 0);
        return $alldata;
    } catch (PDOException $e) {
        $conn = null;
        return null;
    }
}

function recuperaInfoMailConfermaIsc($presenza)
{

    $mailInfo = new MailInfo();
    try {
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $firChoiceTitle = '';

        $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
        $stmtSearch->bindParam(':id', $id_firChoice);
        $id_firChoice = $presenza[idturno];
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $firChoiceTitle = $row[titolo];
        }
        $conn = null;

        $anagrafica = $presenza["anagrafica"];
        $name = $anagrafica[name];
        $surname = $anagrafica[surname];

        if ($name === NULL || $name === undefined || $name === '') {
            $name = $anagrafica[nome];
        }
        if ($surname === NULL || $surname === undefined || $surname === '') {
            $surname = $anagrafica[cognome];
        }

        $returnRes = recuperaTestoImpostazione('mail_isc_confermata_oggetto_' . $id_firChoice);
        $mailInfo->oggetto = $returnRes->returnObject;
        $mailInfo->oggetto = str_replace("data[name]", $name, $mailInfo->oggetto);
        $mailInfo->oggetto = str_replace("data[surname]", $surname, $mailInfo->oggetto);

        $returnRes = recuperaTestoImpostazione('mail_isc_confermata_testo_' . $id_firChoice);
        $mailInfo->testo = $returnRes->returnObject;

        $mailInfo->testo = str_replace("data[name]", $name, $mailInfo->testo);
        $mailInfo->testo = str_replace("data[surname]", $surname, $mailInfo->testo);
        $mailInfo->testo = str_replace("firChoiceTitle", $firChoiceTitle, $mailInfo->testo);
    } catch (Exception $e) {
        $conn = null;
    }

    return $mailInfo;
}


function recuperaInfoMailLisIsc($presenza)
{

    $mailInfo = new MailInfo();
    try {
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $firChoiceTitle = '';

        $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
        $stmtSearch->bindParam(':id', $id_firChoice);
        $id_firChoice = $presenza[idturno];
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $firChoiceTitle = $row[titolo];
        }
        $conn = null;

        $anagrafica = $presenza["anagrafica"];
        $name = $anagrafica[name];
        $surname = $anagrafica[surname];
        $forced_note = $anagrafica[forced_note];

        if ($name === NULL || $name === undefined || $name === '') {
            $name = $anagrafica[nome];
        }
        if ($surname === NULL || $surname === undefined || $surname === '') {
            $surname = $anagrafica[cognome];
        }

        $returnRes = recuperaTestoImpostazione('mail_isc_lis_oggetto');
        $mailInfo->oggetto = $returnRes->returnObject;
        $mailInfo->oggetto = str_replace("data[name]", $name, $mailInfo->oggetto);
        $mailInfo->oggetto = str_replace("data[surname]", $surname, $mailInfo->oggetto);

        $returnRes = recuperaTestoImpostazione('mail_isc_lis_testo');
        $mailInfo->testo = $returnRes->returnObject;

        $mailInfo->testo = str_replace("data[name]", $name, $mailInfo->testo);
        $mailInfo->testo = str_replace("data[surname]", $surname, $mailInfo->testo);
        $mailInfo->testo = str_replace("firChoiceTitle", $firChoiceTitle, $mailInfo->testo);

        $mailInfo->testo = str_replace("data[forced_note]", $forced_note, $mailInfo->testo);

    } catch (Exception $e) {
        $conn = null;
    }

    return $mailInfo;
}

function aggiornaStatoIscrizione($id_iscrizione, $in_status)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $returnResult->result = 1;

        $stmtSearch = $conn->prepare("Select * from iscrizioni_dettaglio
                             where id_iscrizione = :id
                    ");
        $stmtSearch->bindParam(':id', $id);
        $id = $id_iscrizione;
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();

        $last_id = null;
        $old_stato = null;
        foreach ($rows as $row) {
            $last_id = $row[id];
            $old_stato = $row[status];
        }

        if ($old_stato === $in_status) {
            $returnResult->returnMessages[] = "Stato invariato!";
            $returnResult->result = 0;
            $returnResult->success = 0;

            $conn = null;
            return $returnResult;
        }

        $stmtUpdate = $conn->prepare("update iscrizioni_dettaglio
                    set status = :status,
                        updated_at = NOW()
                    where id = :id
            ");

        $stmtUpdate->bindParam(':id', $id);
        $stmtUpdate->bindParam(':status', $status);

        $id = $last_id;
        $status = $in_status;
        $stmtUpdate->execute();

        $returnResult->result = 1;
        $returnResult->success = 1;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        error_log("returnMessages:", 0);
        error_log(print_r($e->getMessage(), true), 0);
        $conn = null;
    }

    return $returnResult;
}
