<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("turno.php");
require_once("presenza.php");
require_once("pagamento.php");
require_once("movimento.php");
require_once("../dati_anagrafici/anagrafica.php");

function recuperaTurno($idturno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from turni
                                 where id = $idturno
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $returnResult->returnMessages[] = $idturno;

        $stmt = $conn->prepare($sqlString);

        // echo $sqlString;

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $act = new Turno();

        // output data of each row
        foreach ($rows as $row) {

            $act->titolo = $row['titolo'];
            $act->id = $row['id'];
            $act->idturnorif = $row['idturnorif'];
            $act->idturnopre = $row['idturnopre'];
            $act->diaria = $row['diaria'];

            $act->preiscrizione = $row['preiscrizione'];
            $inizio = DateTime::createFromFormat("Y-m-d", $row['inizio']);
            $fine = DateTime::createFromFormat("Y-m-d", $row['fine']);
            $act->inizio = $inizio->format("Y-m-d");
            $act->fine = $fine->format("Y-m-d");
            $act->year = $row['year'];
            $act->id_pre_isc_online = $row['id_pre_isc_online'];

            // echo json_encode($act);

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


function recuperaStatoTurno($idturno)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT iscrizioni_dettaglio.*,
                                iscrizioni.birthday,
                                iscrizioni.ruolo
                                from iscrizioni_dettaglio
                                    inner join iscrizioni 
                                 on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id
                                 where firChoice = $idturno
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $returnResult->returnMessages[] = $idturno;

        $stmt = $conn->prepare($sqlString);

        // echo $sqlString;

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $act = new Turno();

        $act->count_ragazzi_la = 0;
        $act->count_ragazzi = 0;
        $act->count_edu = 0;
        $act->count_edu_la = 0;


        // output data of each row
        foreach ($rows as $row) {

            $act->id = $idturno;

            if ( $row[ruolo] == "R" ) {
                if ( $row[firWeek1] == 0 || $row[firWeek2] == 0) {
                    $act->count_ragazzi_la++; 
                } else {
                    $act->count_ragazzi++; 
                }
            } else {

                if ( $row[firWeek1] == 0 || $row[firWeek2] == 0) {
                    $act->count_edu_la++; 
                } else {
                    $act->count_edu++; 
                }

            }

        }

        $returnResult->result = 1;
        $returnResult->success = 1;
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


function generaPrescrizioni($idturno)
{

    $completeResult = recuperaTurno($idturno);

    if ($completeResult->result == 1) {

        $turno = $completeResult->returnObject;

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            /*
                $stmtSearch = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                        as position
                        from iscrizioni_dettaglio  inner join iscrizioni
                            on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
                    where db_insert_at < '$created_at'
                    and status = '$status'
                    and firChoice = '$idTurno'
                    and ruolo = 'R'
                        ");
            */

            $sqlString = "
                            SELECT iscrizioni.*,
                                   iscrizioni.id as id_iscrizione,
                                   iscrizioni_dettaglio.*,
                                   iscrizioni_dettaglio.status as stato_iscrizione,
                                   iscrizioni_dettaglio.status_pag as stato_iscrizione_pag,
                                   turni.*,
                                   turni2.id as idturno2,
                                   turni2.titolo as defturno2,
                                   anag_base.ruolo as anag_ruolo,
                                   anag_base.data_nascita as anag_data_nascita,
                                   anag_base.comune_n_ques as anag_n_comune,
                                   anag_base.provincia_n_ques as anag_n_provincia,
                                   anag_base.res_provincia as anag_res_provincia,
                                   anag_base.res_citta as anag_res_citta,
                                   anag_base.id as id_anagrafica,
                                   anag_base.cellulare_1 as anag_cell1,
                                   anag_base.cellulare_2 as anag_cell2,
                                   anag_base.cellulare_3 as anag_cell3,
                                   anag_base.email_1 as anag_email1,

                                   anag_naz_nas.descrizione as naz_nas_descrizione,
                                   anag_cittadinanza.cittadinanza as naz_cittadinanza,

                                   anag_comune.descrizione as comune_nascita,
                                   anag_comune.provincia as provincia_nascita,

                                   anag_res.descrizione as res_citta,
                                   anag_res.provincia as res_provincia,

                                   utenti.username as creato_da,
                                   utenti.nome as creato_da_nome,
                                   utenti.cognome as creato_da_cognome,
                                   utenti.email1 as email1,
                                   utenti.email2 as email2,
                                   utenti.cell1 as cell1,
                                   utenti.cell2 as cell2

                                              from iscrizioni inner join iscrizioni_dettaglio
                                              on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id

                                              and iscrizioni_dettaglio.id = (
                                                  SELECT  ID
                                                         FROM iscrizioni_dettaglio
                                                 WHERE   iscrizioni_dettaglio.id_iscrizione = iscrizioni.id
                                                 ORDER BY id desc
                                                 LIMIT 1 )
                                                 inner join turni
                                                    on turni.id = $turno->id

                                                 left outer join anag_base
                                                    on iscrizioni.id_anag_base = anag_base.id

                                                left outer join turni as turni2
                                                    on iscrizioni_dettaglio.secChoice = turni2.id

                                                left outer join utenti
                                                    on iscrizioni.created_by = utenti.id

                                                left outer join anag_comune
                                                    on iscrizioni.comune_n_ques = anag_comune.codice

                                                left outer join anag_nazione as anag_naz_nas
                                                    on iscrizioni.stato_n_ques = anag_naz_nas.codice

                                                left outer join anag_nazione as anag_cittadinanza
                                                    on iscrizioni.cittadinanza_n_ques = anag_cittadinanza.codice

                                                left outer join anag_comune as anag_res
                                                    on iscrizioni.comune_res_n = anag_res.codice


                                              where iscrizioni.year = $turno->year
                                                and iscrizioni_dettaglio.firChoice = $turno->id
                                                and iscrizioni.is_deleted = 0

                                                order by iscrizioni_dettaglio.created_at
                                        ";

            $returnResult->returnMessages[] = $sqlString;
            $returnResult->returnMessages[] = $idturno;

            $stmt = $conn->prepare($sqlString);

            // echo $sqlString;

            $stmt->execute();
            $rows = $stmt->fetchAll();


            $alldata = array();

            // output data of each row
            foreach ($rows as $row) {

                $act = new Presenza();

                $act->idanag = $row['id_anagrafica'];
                $act->idturno = (int)$turno->id;
                $act->defturno = $row['titolo'];
                $act->nome = $row['name'];
                $act->cognome = $row['surname'];

                $act->nome = trim($act->nome, " ");
                $act->cognome = trim($act->cognome, " ");

                $act->data_nascita = $row['birthDay'];

                $act->idturno2 = (int)$row['idturno2'];
                $act->defturno2 = $row['defturno2'];
                /*

                    */

                $act->stanza = $row['stanza'];
                $act->note = $row['note'];
                $act->maglia = $row['maglia'];
                $act->creato_da_nome = $row['creato_da_nome'];
                $act->creato_da_cognome = $row['creato_da_cognome'];

                $act->frequenza_gruppi = $row['frequenza_gruppi'];
                $act->pullman = $row['pullman'];
                $act->settimana_1 = $row['firWeek1'];
                $act->settimana_2 = $row['firWeek2'];
                $act->stato_iscrizione = $row['status'];
                $act->stato_iscrizione_pag = $row['status_pag'];
                $act->ruolo = $row['ruolo'];

                if ($act->stato_iscrizione == null)  $act->stato_iscrizione = 'REG';
                if ($act->stato_iscrizione_pag == null)  $act->stato_iscrizione_pag = 'ZERO';

                $act->id_iscrizione = $row['id_iscrizione'];

                $act->anag_base_diff = false;
                $act->anag_base_confirmed =  $row['anag_base_confirmed'];
                if ($act->anag_base_confirmed === '1') {
                    $act->anag_base_confirmed = true;
                    $act->anag_base_diff = false;

                    if ($row['anag_data_nascita'] != null && $row['anag_data_nascita'] != '') {
                        $act->data_nascita = $row['anag_data_nascita'];
                    }

                    if ($row['anag_ruolo'] != null && $row['anag_ruolo'] != '') {
                        $act->ruolo = $row['anag_ruolo'];
                    } else {
                        $act->ruolo = 'R';

                        $year = date('Y');
                        $age = $year - $act->anno_nascita;
                        if ($age >= 18) {
                            $act->ruolo = 'E';
                        }
                    }
                } else {
                    $act->anag_base_confirmed = false;
                }

                $act->anno_nascita = substr($act->data_nascita, 0, 4);
                $act->data_inserimento = $row['db_insert_at'];
                $act->creato_da = $row['creato_da'];
                $act->data_arrivo = $row['data_arrivo'];
                $act->data_partenza = $row['data_partenza'];

                $act->comune_n_ques = $row['comune_n_ques'];
                $act->stato_n_ques = $row['stato_n_ques'];
                $act->cittadinanza_n_ques = $row['cittadinanza_n_ques'];

                $act->stato_nascita =  $row['naz_nas_descrizione'];
                $act->cittadinanza =  $row['naz_cittadinanza'];
                $act->sesso =  $row['sesso'];

                $act->comune_nascita =  $row['comune_nascita'];
                $act->provincia_n_ques =  $row['provincia_nascita'];

                $act->res_citta = $row['res_citta'];
                $act->res_provincia = $row['res_provincia'];
                $act->res_via = $row['via_res'];

                $act->email1 = $row['email'];
                if ($act->email1 === '') {
                    $act->email1 = $act->creato_da;
                }
                $act->cell1 = '';
                $act->cell2 = '';

                $act->all_mails = $act->email1;

                $act->emails = [];
                $act->emails[] = $act->email1;

                if ($row['cell'] != undefined && $row['cell'] != null ) {
                    $act->cell1 = $row['cell'];
                }

                if ($row['cell1'] != undefined && $row['cell1'] != null) {
                    $act->cell1 = $row['cell1'];
                }
                $act->email2 = $row['email2'];
                if ($row['cell2'] != undefined && $row['cell2'] != null) {
                    $act->cell2 = $row['cell2'];
                }
                $act->all_mails = $act->all_mails.' ; '.$act->email2;
                $act->emails[] = $act->email2;

                $act->cells = [];

                if ($row['cell'] != undefined) {
                    $act->cells[] = $row['cell'];
                }
                if ($row['cell1'] != undefined) {
                    $act->cells[] = $row['cell1'];
                }
                if ($row['cell2'] != undefined) {
                    $act->cells[] = $row['cell2'];
                }
                if ($row['anag_cell1'] != undefined) {
                    $act->cells[] = $row['anag_cell1'];
                }
                if ($row['anag_cell2'] != undefined) {
                    $act->cells[] = $row['anag_cell2'];
                }
                if ($row['anag_cell3'] != undefined) {
                    $act->cells[] = $row['anag_cell3'];
                }

                $count = 0;
                foreach ($act->cells as $cell) {
                    if ($cell !== '' && $cell !== null) {
                        $count++;
                        if ($count == 1) {
                            $act->cell1 = $cell;
                        }
                        if ($count == 2) {
                            $act->cell2 = $cell;
                        }
                        if ($count == 3) {
                            $act->cell3 = $cell;
                        }
                    }
                }

                if ($act->ruolo == '') {
                    $act->ruolo = 'R';

                    $year = date('Y');
                    $age = $year - $act->anno_nascita;
                    if ($age >= 16) {
                        $act->ruolo = 'A';
                    }
                    if ($age >= 18) {
                        $act->ruolo = 'E';
                    }
                }

                if (!$act->anag_base_confirmed) {
                    if ($act->ruolo != $row['anag_ruolo']) {
                        $act->anag_base_diff = true;
                        $act->anag_base_diff_det = 'anag_ruolo';
                    }
                    if ($act->data_nascita != $row['anag_data_nascita']) {
                        $act->anag_base_diff = true;
                        $act->anag_base_diff_det = 'anag_data_nascita';
                    }
                    if ($act->comune_n_ques != $row['anag_n_comune']) {
                        $act->anag_base_diff = true;
                        $act->anag_base_diff_det = 'anag_n_comune';
                    }
                    if ($act->provincia_n_ques != $row['anag_n_provincia']) {
                        $act->anag_base_diff = true;
                        $act->anag_base_diff_det = 'anag_n_provincia';
                    }
                    if ($act->res_citta != $row['anag_res_citta']) {
                        $act->anag_base_diff = true;
                        $act->anag_base_diff_det = 'anag_res_citta';
                    }
                    if ($act->res_provincia != $row['anag_res_provincia']) {
                        $act->anag_base_diff = true;
                        $act->anag_base_diff_det = 'anag_res_provincia';
                    }
                    if ($act->email1 != $row['anag_email1']) {
                        $act->anag_base_diff = true;
                        $act->anag_base_diff_det = 'anag_email1';
                    }
                    if ($act->cell1 != $row['anag_cell1']) {
                        $act->anag_base_diff = true;
                        $act->anag_base_diff_det = 'anag_cell1';
                    }
                }

                $act->position = '';
                if ($act->ruolo == 'R') {

                    $stato_agg = 'xx';
                    if ($act->stato_iscrizione == 'REG') {
                        $stato_agg = 'CONF';
                    }

                    $stmtSearch = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                            as position
                            from iscrizioni_dettaglio  inner join iscrizioni
                                on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
                        where db_insert_at < '$act->data_inserimento'
                        and ( status = '$act->stato_iscrizione' or status = '$stato_agg' )
                        and firChoice = '$act->idturno'
                        and ruolo = 'R'
                        and is_deleted = 0
                            ");

                    $stmtSearch->execute();
                    $rowsCount = $stmtSearch->fetchAll();
                    foreach ($rowsCount as $rowCount) {
                        $act->position = $rowCount[position] + 1;
                    }

                    $ricalcolo_LIS = "1";

                    if ($ricalcolo_LIS == '1') {

                        if ($act->stato_iscrizione == "LIS") {

                            error_log("Iscrizione in stato LIS", 0);

                            if ($act->settimana_1 == "1" && $act->settimana_2 == "1" ) {
                                $position2w = 0;

                                $stmtSearch2W = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                                        as position
                                        from iscrizioni_dettaglio  inner join iscrizioni
                                            on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
                                    where db_insert_at < '$act->data_inserimento'
                                    and status = '$act->stato_iscrizione'
                                    and firChoice = '$act->idturno'
                                    and firWeek1 = '1'
                                    and firWeek2 = '1'
                                    and ruolo = 'R'
                                    and is_deleted = 0
                                        ");

                                $stmtSearch2W->execute();
                                $rowsCount = $stmtSearch2W->fetchAll();
                                foreach ($rowsCount as $rowCount) {
                                    $position2w = $rowCount[position];
                                }

                                error_log("position2w", 0);
                                error_log(print_r($position2w, true), 0);

                                $act->position = $position2w + 1;
                            } else {

                                $position2w = 0;
                                $stmtSearch2W = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                                        as position
                                        from iscrizioni_dettaglio  inner join iscrizioni
                                            on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
                                    where status = '$act->stato_iscrizione'
                                    and firChoice = '$act->idturno'
                                    and firWeek1 = '1'
                                    and firWeek2 = '1'
                                    and ruolo = 'R'
                                    and is_deleted = 0
                                        ");

                                $stmtSearch2W->execute();
                                $rowsCount = $stmtSearch2W->fetchAll();
                                foreach ($rowsCount as $rowCount) {
                                    $position2w = $rowCount[position];
                                }                            

                                $position1w = 0;
                                $stmtSearch1W = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                                    as position
                                    from iscrizioni_dettaglio  inner join iscrizioni
                                        on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
                                    where db_insert_at < '$act->data_inserimento'
                                    and status = '$act->stato_iscrizione'
                                    and firChoice = '$act->idturno'
                                    and ( firWeek1 = '0' or firWeek2 = '0' )
                                    and ruolo = 'R'
                                    and is_deleted = 0
                                        ");

                                $stmtSearch1W->execute();
                                $rowsCount = $stmtSearch1W->fetchAll();
                                foreach ($rowsCount as $rowCount) {
                                    $position1w = $rowCount[position];
                                }

                                error_log("position1w", 0);
                                error_log(print_r($position1w, true), 0);

                                $act->position = $position2w + $position1w + 1;
                            }

                        }

                    }

                }

                $act->datePresenza = [];

                $inizio = DateTime::createFromFormat("Y-m-d", $turno->inizio);
                $fine = DateTime::createFromFormat("Y-m-d", $turno->fine);
                $actData = DateTime::createFromFormat("Y-m-d", $turno->inizio);
                $count = 0;


                while ($actData <= $fine) {
                    // print_r($actData);

                    $count++;
                    if ($act->settimana_1 === "1" && $count < 9) {
                        $presenza = new DataPresenza();
                        // $presenza->data = $actData;
                        $presenza->dataS = $actData->format("Y-m-d");;
                        $presenza->data = DateTime::createFromFormat("Y-m-d", $presenza->dataS);
                        $presenza->dataData = DateTime::createFromFormat("Y-m-d", $presenza->dataS);
                        $presenza->data = $presenza->data->format("D M d Y");
                        // $presenza->data = $actData->format("Y-m-d");

                        $presenza->presente = 1;

                        if ($act->data_arrivo !== NULL && $act->data_partenza !== NULL) {
                            $data_arrivo = DateTime::createFromFormat("Y-m-d", $act->data_arrivo);
                            $data_partenza = DateTime::createFromFormat("Y-m-d", $act->data_partenza);
                            if ( $presenza->dataData >= $data_arrivo && $presenza->dataData < $data_partenza){
                            } else {
                                $presenza->presente = 0;
                            }
                        }

                        $act->datePresenza[] = $presenza;
                    }

                    if ($act->settimana_2 === "1" && $count >= 9) {
                        $presenza = new DataPresenza();
                        // $presenza->data = $actData;
                        $presenza->dataS = $actData->format("Y-m-d");
                        $presenza->data = DateTime::createFromFormat("Y-m-d", $presenza->dataS);
                        $presenza->data = $presenza->data->format("D M d Y");
                        // $presenza->data = $actData->format("Y-m-d");
                        $presenza->presente = true;
                        $act->datePresenza[] = $presenza;
                    }

                    $actData = $actData->modify('+1 day');
                }

                // echo json_encode($act);

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
    } else {
        return $completeResult;
    }
}

function salvaTurnoCompleto($iturno)
{

    try {
        $returnResult = new ServiceResult();
        // $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages[] = $iturno;

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("INSERT INTO turni
                                (   id,
                                    titolo,
                                    year,
                                    gruppo,
                                    inizio, fine,
                                    inizio_isc, fine_isc,
                                    min_year, max_year,
                                    chiuso,
                                    auto_gestione,
                                    posti_max,
                                    diaria,
                                    costo_totale,
                                    specifica_date,
                                    posti_max_totali,
                                    accetta_edu_auto,
                                    accetta_rag_auto
                                     )
                            VALUES (
                                :id,
                                :titolo,
                                :year,
                                :gruppo,
                              :inizio, :fine,
                              :inizio_isc, :fine_isc,
                              :min_year, :max_year,
                              :chiuso,
                                :auto_gestione,                                
                                :posti_max,
                                    :diaria,
                                    :costo_totale,
                                    :specifica_date,
                                    :posti_max_totali,
                                    :accetta_edu_auto,
                                    :accetta_rag_auto                             
                                 )
                            ON DUPLICATE KEY UPDATE
                                titolo = :titolo,
                                gruppo = :gruppo,
                                year = :year,
                                    inizio = :inizio, fine = :fine,
                                    inizio_isc = :inizio_isc , fine_isc = :fine_isc,
                                    min_year = :min_year , max_year = :max_year,
                                    chiuso = :chiuso,
                                    auto_gestione = :auto_gestione,
                                    posti_max = :posti_max,
                                    diaria = :diaria,
                                    costo_totale = :costo_totale,
                                    specifica_date = :specifica_date,
                                    posti_max_totali = :posti_max_totali,
                                    accetta_edu_auto = :accetta_edu_auto,
                                    accetta_rag_auto = :accetta_rag_auto
                    ");

        $stmtUpdate->bindParam(':id', $id);
        $stmtUpdate->bindParam(':titolo', $titolo);
        $stmtUpdate->bindParam(':gruppo', $gruppo);
        $stmtUpdate->bindParam(':year', $year);
        $stmtUpdate->bindParam(':inizio', $inizio);
        $stmtUpdate->bindParam(':fine', $fine);
        $stmtUpdate->bindParam(':inizio_isc', $inizio_isc);
        $stmtUpdate->bindParam(':fine_isc', $fine_isc);
        $stmtUpdate->bindParam(':chiuso', $chiuso);
        $stmtUpdate->bindParam(':auto_gestione', $auto_gestione);
        $stmtUpdate->bindParam(':min_year', $min_year);
        $stmtUpdate->bindParam(':max_year', $max_year);
        $stmtUpdate->bindParam(':posti_max', $posti_max);
        $stmtUpdate->bindParam(':diaria', $diaria);
        $stmtUpdate->bindParam(':costo_totale', $costo_totale);
        $stmtUpdate->bindParam(':specifica_date', $specifica_date);
        $stmtUpdate->bindParam(':posti_max_totali', $posti_max_totali);
        $stmtUpdate->bindParam(':accetta_edu_auto', $accetta_edu_auto);
        $stmtUpdate->bindParam(':accetta_rag_auto', $accetta_rag_auto);

        $id = $iturno[id];
        $titolo = $iturno["titolo"];
        $gruppo = $iturno["gruppo"];
        $year = $iturno["year"];
        $inizio = explode("T", $iturno[inizio])[0];
        $fine = explode("T", $iturno[fine])[0];
        $inizio_isc = explode("T", $iturno[inizio_isc])[0];
        $fine_isc = explode("T", $iturno[fine_isc])[0];

        $auto_gestione = $iturno["auto_gestione"];
        $max_year = $iturno["max_year"];
        $min_year = $iturno["min_year"];
        $chiuso = $iturno["chiuso"];

        $posti_max = $iturno["posti_max"];
        $diaria = $iturno["diaria"];
        $costo_totale = $iturno["costo_totale"];
        $specifica_date = $iturno["specifica_date"];
        $posti_max_totali = $iturno["posti_max_totali"];
        $accetta_edu_auto = $iturno["accetta_edu_auto"];
        $accetta_rag_auto = $iturno["accetta_rag_auto"];

        $stmtUpdate->execute();

        $returnResult->returnMessages = [];
        $returnResult->result = 1;
        $returnResult->success = 1;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");

        // $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        error_log("returnMessages:", 0);
        error_log(print_r($e->getMessage(), true), 0);


        $conn = null;
    }

    return $returnResult;
}


function cancellaTurno($iturno)
{
    try {
        $returnResult = new ServiceResult();
        // $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages[] = $iturno;

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtDelete = $conn->prepare("DELETE from turni
                    WHERE id = :id
                ");

        $stmtDelete->bindParam(':id', $id);

        $id = $iturno[id];
        $stmtDelete->execute();

        $returnResult->returnMessages = [];
        $returnResult->result = 1;
        $returnResult->success = 1;

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");

        // $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        error_log("returnMessages:", 0);
        error_log(print_r($e->getMessage(), true), 0);


        $conn = null;
    }

    return $returnResult;
}



function getTodayData()
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "select sum(presente) as presenze_oggi
        from presenze_turni
        where data = DATE(NOW())
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $stmt = $conn->prepare($sqlString);

        // echo $sqlString;

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $act = new stdClass;

        // output data of each row
        foreach ($rows as $row) {
            $act->presenze = $row['presenze_oggi'];
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

    return $returnResult->returnObject ;
}
