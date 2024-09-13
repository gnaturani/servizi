<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("../commonOperations.php");
require_once("../models/iscription.php");
require_once("../models/mailinfo.php");
require_once("../models/setting.php");
require_once("../turni/operations.php");
require_once("../dati_anagrafici/operations.php");
require_once("../dati_anagrafici/anagrafica.php");
require_once("../mainjwt.php");

error_reporting(E_ERROR | E_PARSE);

function cancellaIscrizione($data) {

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('deleteIscription Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("update iscrizioni
                                            set is_deleted = 1,
                                                updated_at = NOW()
                                        where id = :id
                    ");
        $stmtUpdate->bindParam(':id', $id);
        $id = $data[id];
        $stmtUpdate->execute();

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Iscrizione cancellata!";

        $conn = null;

    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function noGroupFrequencyIscription($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {
        error_log(print_r('noGroupFrequencyIscription data:', true), 0);
        error_log(print_r($data, true), 0);

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("update iscrizioni
                                            set frequenza_gruppi = 0,
                                                forced_by = :forced_by
                                        where id = :id
                    ");
        $stmtUpdate->bindParam(':id', $id);
        $id = $data[id_iscrizione];
        $stmtUpdate->bindParam(':forced_by', $forced_by);
        $forced_by = $data[forced_by];
        $stmtUpdate->execute();

        $forced_note_text = "Dopo confronto con i catechisti/educatori del gruppo"
                    ." &egrave; stato verificato che il ragazzo/a NON partecipa al catechismo o ai gruppi!"
                    ." Pertanto l'iscritto/a verr&agrave; messo in lista d'attesa "
                    ." secondo le priorit&agrave; identificate al momento dell'iscrizione";

        $stmtUpdateDet = $conn->prepare("update iscrizioni_dettaglio
                                            set STATUS = 'LIS',
                                                forced_at = NOW(),
                                                forced_by = :forced_by,
                                                forced_note = :forced_note
                                        where id_iscrizione = :id_iscrizione
                    ");
        $stmtUpdateDet->bindParam(':id_iscrizione', $id_iscrizione);
        $id_iscrizione = $data[id_iscrizione];
        $stmtUpdateDet->bindParam(':forced_by', $forced_by);
        $forced_by = $data[forced_by];
        $stmtUpdateDet->bindParam(':forced_note', $forced_note);
        $forced_note = $forced_note_text;
        $stmtUpdateDet->execute();        

        invioMailStatoIscrizione($data);

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Frequenza ai gruppi cancellata!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function yesGroupFrequencyIscription($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {
        error_log(print_r('yesGroupFrequencyIscription data:', true), 0);
        error_log(print_r($data, true), 0);

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("update iscrizioni
                                            set frequenza_gruppi = 1,
                                                forced_by = :forced_by
                                        where id = :id
                    ");
        $stmtUpdate->bindParam(':id', $id);
        $id = $data[id_iscrizione];
        $stmtUpdate->bindParam(':forced_by', $forced_by);
        $forced_by = $data[forced_by];
        $stmtUpdate->execute();

        $stmtUpdateDet = $conn->prepare("update iscrizioni_dettaglio
                                            set STATUS = 'RIS',
                                                forced_at = NOW(),
                                                forced_by = :forced_by
                                        where id_iscrizione = :id_iscrizione
                    ");
        $stmtUpdateDet->bindParam(':id_iscrizione', $id_iscrizione);
        $id_iscrizione = $data[id_iscrizione];
        $stmtUpdateDet->bindParam(':forced_by', $forced_by);
        $forced_by = $data[forced_by];
        $stmtUpdateDet->execute();        

        invioMailStatoIscrizione($data);

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Frequenza ai gruppi aggiunta!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function newCreatedBy($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {
        error_log(print_r('newCreatedBy data:', true), 0);
        error_log(print_r($data, true), 0);

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


        $stmtSearch = $conn->prepare("Select id from utenti
                             where username = :username
                    ");
        $stmtSearch->bindParam(':username', $username);
        $username = $data[creato_da];
        $stmtSearch->execute();
        $found = false;
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $newCreatedById = $row[id];
            $found = true;
        }

        if ($found == false){
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Utente: " . $data[creato_da]. " non Trovato!";
            $returnResult->success = 0;
            $returnResult->result = 0;

        } else {

            $stmtUpdate = $conn->prepare("update iscrizioni
                        set created_by = :created_by
                    where id = :id
            ");
            $stmtUpdate->bindParam(':id', $id);
            $id = $data[id_iscrizione];
            $stmtUpdate->bindParam(':created_by', $created_by);
            $created_by = $newCreatedById;
            $stmtUpdate->execute();

            $returnResult->result = 1;
            $returnResult->success = 1;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Creatore modificato!";

        }

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function saveIscription($data)
{
    $eccezione2020 = false;

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    $solo_maggiorenni = false;

    try {

        error_log(print_r('saveIscription Data:', true), 0);
        error_log(print_r($data, true), 0);

        $email = '';

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $firChoiceTitle = '';
        $secChoiceTitle = '';
        $firChoicePosti = 50;

        $posti_max_totali = 999;
        $accetta_edu_auto = 0;
        $accetta_rag_auto = 0;

        $returnResult->returnMessages[] = "Id FirChoice: " . $data[firChoice];

        $stmtSearch = $conn->prepare("Select titolo, posti_max, min_year, max_year, auto_gestione,
                                                inizio_isc, fine_isc, posti_max_totali, accetta_edu_auto, accetta_rag_auto
                                            from turni
                             where id = :id
                    ");
        $stmtSearch->bindParam(':id', $id_firChoice);
        $id_firChoice = $data[firChoice];

        $auto_gestione = 0;

        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $firChoiceTitle = $row[titolo];
            $firChoicePosti = $row[posti_max];
            $min_year = $row[min_year];
            $max_year = $row[max_year];
            $auto_gestione = $row[auto_gestione];
            if($min_year == "0" || $min_year == NULL) {
                $min_year = 1900;
            }
            if($max_year == "0" || $max_year == NULL) {
                $max_year = 2200;
            }

            $inizio_isc = $row[inizio_isc];
            $fine_isc = $row[fine_isc];

            if($inizio_isc == "0" || $inizio_isc == NULL) {
                $inizio_isc = '2000-01-01';
            }
            if($fine_isc == "0" || $fine_isc == NULL) {
                $fine_isc = '2200-01-01';
            }

            $posti_max_totali = $row[posti_max_totali];
            $accetta_edu_auto = $row[accetta_edu_auto];
            $accetta_rag_auto = $row[accetta_rag_auto];
        }

        $birthday = explode("T", $data[birthDay])[0];
        $year = substr($birthday, 0, 4);

        if ($year >= $min_year && $year <= $max_year){            
        } else {
            $returnResult->success = false;
            $returnResult->result = 0;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Turno non aperto per la tua annata: $year!!";
            return $returnResult;
        }

        /*
        $isc_closed = false;
        $today = new DateTime();
        if ($today > $fine_isc) {
            $isc_closed = true;
        }
        if ($today < $inizio_isc) {
            $isc_closed = true;
        }
        if ($isc_closed == false){            
        } else {
            $returnResult->success = false;
            $returnResult->result = 0;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Iscrizioni dal $inizio_isc al $fine_isc!!";
            return $returnResult;
        }
        */


        $returnLimitePosti = recuperaTestoImpostazione('limite_posti');
        // $firChoicePosti = $returnLimitePosti->returnObject;

        if ($data[secChoice] !== null && $data[secChoice] !== undefined && $data[secChoice] !== '') {
            $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
            $stmtSearch->bindParam(':id', $id_secChoice);
            $id_secChoice = $data[secChoice];
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();
            foreach ($rows as $row) {
                $secChoiceTitle = $row[titolo];
            }
        }

        $stmtSearch = $conn->prepare("Select *
                                            from iscrizioni
                             where id = :id
                    ");
        $stmtSearch->bindParam(':id', $id);

        $id = $data[id];

        $found = false;
        $created_at = date('Y-m-d H:i:s');
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $found = true;

            $created_by_ok = $row[created_by];
            continue;
        }

        $doppione = false;
        // verifico NOMI DOPPI per anno
        if ($found == false) {

            $stmtSearch = $conn->prepare("Select *
                        from iscrizioni
                        where name = :name
                        and surname = :surname
                        and year = :year
                        and is_deleted = 0
                        ");
            $stmtSearch->bindParam(':name', $name);
            $stmtSearch->bindParam(':surname', $surname);
            $stmtSearch->bindParam(':year', $year);

            $name = $data[name];
            $surname = $data[surname];
            $year = substr($created_at, 0, 4);

            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();
            foreach ($rows as $row) {
                $doppione = true;

                $ruolo_trovato = $row["ruolo"];
                $birthday_trovato = $row["birthDay"];

                // $returnResult->returnMessages[] = "Ruolo trovato:".$ruolo_trovato;

                if ($ruolo_trovato == "E" || $ruolo_trovato == "C") {
                    $birth_year = (int)substr($birthday_trovato, 0, 4);

                    // $returnResult->returnMessages[] = "birth_year trovato:".$birth_year;

                    if ($birth_year < 2001) {
                        $doppione = false;
                        $returnResult->returnMessages[] = "NON DOPPIONE!";
                    }
                }

                // OMONIMIA E STESSO ANNO DI NASCITA
                if ($birthday !== $birthday_trovato) {
                    $doppione = false;
                }

                $created_at = $row["created_at"];
            }

            if ($doppione === true) {
                $month = date('m');
                if ($month >= 10 and $month <= 12){
                    $doppione = false;
                    $returnResult->returnMessages[] = "NON DOPPIONE! VACANZA INVERNALE";
                }
            }

            if ($doppione === true) {

                $returnResult->returnMessages = [];
                $returnResult->returnMessages[] = "Iscrizione per " .$name. " / " . $surname.  " già inserita!";
                return $returnResult;
            }
        }

        if ($found == false) {

            if ($solo_maggiorenni) {
                $birthday = explode("T", $data[birthDay])[0];
                $year_nascita = substr($birthday, 0, 4);

                $diff = date("Y") - $year_nascita;
                if ($diff > 17) {

                } else {

                    $returnResult->returnMessages = [];
                    $returnResult->returnMessages[] = "Iscrizioni attualmente aperte solo per gli educatori / aiuto cucina!";
                    return $returnResult;
                }
            }

            $stmtInsert = $conn->prepare("insert into iscrizioni

                               ( name,
                                surname,
                                birthday,
                                isOfCarpaneto,
                                year,
                                created_at,
                                created_by,
                                id_anag_base,

                                sesso,
                                comune_n_ques,
                                provincia_n_ques,
                                stato_n_ques,
                                cittadinanza_n_ques,

                                comune_res_n,
                                provincia_res,
                                via_res,
                                pullman,
                                ruolo,
                                note,
                                stanza,
                                maglia,
                                frequenza_gruppi,

                                data_arrivo,
                                data_partenza,
                                cell,
                                email

                                )

                                VALUES (
                                    :name,
                                    :surname,
                                    :birthday,
                                    :isOfCarpaneto,
                                    :year,
                                    NOW(),
                                    :created_by,
                                    :id_anag_base,

                                    :sesso,
                                    :comune_n_ques,
                                    :provincia_n_ques,
                                    :stato_n_ques,
                                    :cittadinanza_n_ques,

                                    :comune_res_n,
                                    :provincia_res,
                                    :via_res,
                                    :pullman,
                                    :ruolo,
                                    :note,
                                    :stanza,
                                    :maglia,
                                    :frequenza_gruppi,
                                    :data_arrivo,
                                    :data_partenza,
                                    :cell,
                                    :email

                                )

                    ");

            $stmtInsert->bindParam(':name', $name);
            $stmtInsert->bindParam(':surname', $surname);
            $stmtInsert->bindParam(':created_by', $created_by);
            $stmtInsert->bindParam(':birthday', $birthday);
            $stmtInsert->bindParam(':isOfCarpaneto', $isOfCarpaneto);
            $stmtInsert->bindParam(':year', $year);
            $stmtInsert->bindParam(':id_anag_base', $id_anag_base);

            $stmtInsert->bindParam(':sesso', $sesso);
            $stmtInsert->bindParam(':comune_n_ques', $comune_n_ques);
            $stmtInsert->bindParam(':provincia_n_ques', $provincia_n_ques);
            $stmtInsert->bindParam(':stato_n_ques', $stato_n_ques);
            $stmtInsert->bindParam(':cittadinanza_n_ques', $cittadinanza_n_ques);

            $stmtInsert->bindParam(':comune_res_n', $comune_res_n);
            $stmtInsert->bindParam(':provincia_res', $provincia_res);
            $stmtInsert->bindParam(':via_res', $via_res);
            $stmtInsert->bindParam(':pullman', $pullman);
            $stmtInsert->bindParam(':ruolo', $ruolo);

            $stmtInsert->bindParam(':stanza', $stanza);
            $stmtInsert->bindParam(':maglia', $maglia);
            $stmtInsert->bindParam(':note', $note);
            $stmtInsert->bindParam(':frequenza_gruppi', $frequenza_gruppi);

            $stmtInsert->bindParam(':data_arrivo', $data_arrivo);
            $stmtInsert->bindParam(':data_partenza', $data_partenza);
            $stmtInsert->bindParam(':cell', $cell);
            $stmtInsert->bindParam(':email', $email);

            $name = $data[name];
            $surname = $data[surname];
            $created_by = $data[created_by];
            $created_by_ok = $created_by;

            $birthday = explode("T", $data[birthDay])[0];
            $isOfCarpaneto = $data[isOfCarpaneto];

            $year = substr($created_at, 0, 4);

            $sesso = $data[sesso];
            $comune_n_ques = $data[comune_n_ques];
            $provincia_n_ques = $data[provincia_n_ques];
            $stato_n_ques = $data[stato_n_ques];
            $cittadinanza_n_ques = $data[cittadinanza_n_ques];

            $comune_res_n = $data[comune_res_n];
            $provincia_res = $data[provincia_res];
            $via_res = $data[via_res];
            $pullman = $data[pullman];
            $ruolo = $data[ruolo];

            $stanza = $data[stanza];
            $maglia = $data[maglia];
            $note = $data[note];
            $frequenza_gruppi = $data[frequenza_gruppi];
            if ($frequenza_gruppi === false) {
                $frequenza_gruppi = 0;
            }
            if ($frequenza_gruppi === true) {
                $frequenza_gruppi = 1;
            }
            if ($pullman === false) {
                $pullman = 0;
            }
            if ($pullman === true) {
                $pullman = 1;
            }

            $cell = $data[cell];
            $email = $data[email];

            if ($data[data_arrivo] == NULL || $data[data_arrivo] == null) {
                $data_arrivo = NULL;
            } else {
                $data_arrivo = explode("T", $data[data_arrivo])[0];
                // $data_arrivo = $data[data_arrivo];
            }
            if ($data[data_partenza] == NULL || $data[data_partenza] == null) {
                $data_partenza = NULL;
            } else {
                $data_partenza = explode("T", $data[data_partenza])[0];
                // $data_partenza = $data[data_partenza];
            }           

            $id_anag_base = ricercaAnagraficaPerIscrizione($data);

            $stmtInsert->execute();

            $confirmId = $conn->lastInsertId();

            $returnResult->success = 1;
            
            // $returnResult->returnMessages = [];

            $returnResult->returnMessages[] = "Iscrizione salvata!";
        } else {
            $stmtUpdate = $conn->prepare("update iscrizioni

                                set name = :name,
                                surname = :surname,
                                birthday = :birthday,
                                isOfCarpaneto = :isOfCarpaneto,
                                id_anag_base = :id_anag_base,

                                sesso = :sesso,
                                comune_n_ques = :comune_n_ques,
                                provincia_n_ques = :provincia_n_ques,
                                stato_n_ques = :stato_n_ques,
                                cittadinanza_n_ques = :cittadinanza_n_ques,

                                comune_res_n = :comune_res_n,
                                provincia_res = :provincia_res,
                                via_res = :via_res,
                                pullman = :pullman,
                                ruolo = :ruolo,

                                stanza = :stanza,
                                note = :note,
                                maglia = :maglia,
                                frequenza_gruppi = :frequenza_gruppi,

                                data_arrivo = :data_arrivo,
                                data_partenza = :data_partenza,
                                cell = :cell,
                                email = :email,

                                updated_at = NOW()

                                where id = :id
                    ");

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':name', $name);
            $stmtUpdate->bindParam(':surname', $surname);
            $stmtUpdate->bindParam(':birthday', $birthday);
            $stmtUpdate->bindParam(':isOfCarpaneto', $isOfCarpaneto);
            $stmtUpdate->bindParam(':id_anag_base', $id_anag_base);

            $stmtUpdate->bindParam(':sesso', $sesso);
            $stmtUpdate->bindParam(':comune_n_ques', $comune_n_ques);
            $stmtUpdate->bindParam(':provincia_n_ques', $provincia_n_ques);
            $stmtUpdate->bindParam(':stato_n_ques', $stato_n_ques);
            $stmtUpdate->bindParam(':cittadinanza_n_ques', $cittadinanza_n_ques);

            $stmtUpdate->bindParam(':comune_res_n', $comune_res_n);
            $stmtUpdate->bindParam(':provincia_res', $provincia_res);
            $stmtUpdate->bindParam(':via_res', $via_res);
            $stmtUpdate->bindParam(':pullman', $pullman);
            $stmtUpdate->bindParam(':ruolo', $ruolo);

            $stmtUpdate->bindParam(':stanza', $stanza);
            $stmtUpdate->bindParam(':maglia', $maglia);
            $stmtUpdate->bindParam(':note', $note);
            $stmtUpdate->bindParam(':frequenza_gruppi', $frequenza_gruppi);
            $stmtUpdate->bindParam(':data_arrivo', $data_arrivo);
            $stmtUpdate->bindParam(':data_partenza', $data_partenza);

            $stmtUpdate->bindParam(':cell', $cell);
            $stmtUpdate->bindParam(':email', $email);

            $id = $data[id];
            $name = $data[name];
            $surname = $data[surname];
            $birthday = explode("T", $data[birthDay])[0];
            $isOfCarpaneto = $data[isOfCarpaneto];

            $sesso = $data[sesso];
            $comune_n_ques = $data[comune_n_ques];
            $provincia_n_ques = $data[provincia_n_ques];
            $stato_n_ques = $data[stato_n_ques];
            $cittadinanza_n_ques = $data[cittadinanza_n_ques];

            $comune_res_n = $data[comune_res_n];
            $provincia_res = $data[provincia_res];
            $via_res = $data[via_res];
            $pullman = $data[pullman];
            $ruolo = $data[ruolo];

            $stanza = $data[stanza];
            $maglia = $data[maglia];
            $note = $data[note];
            $frequenza_gruppi = $data[frequenza_gruppi];
            if ($frequenza_gruppi === false) {
                $frequenza_gruppi = 0;
            }
            if ($frequenza_gruppi === true) {
                $frequenza_gruppi = 1;
            }
            if ($pullman === false) {
                $pullman = 0;
            }
            if ($pullman === true) {
                $pullman = 1;
            }

            $cell = $data[cell];
            $email = $data[email];

            $data_arrivo = explode("T", $data[data_arrivo])[0];
            $data_partenza =  explode("T", $data[data_partenza])[0];

            if ($data[data_arrivo] == NULL || $data[data_arrivo] == null) {
                $data_arrivo = NULL;
            }
            if ($data[data_partenza] == NULL || $data[data_partenza] == null) {
                $data_partenza = NULL;
            }

            $id_anag_base = ricercaAnagraficaPerIscrizione($data);

            $stmtUpdate->execute();

            $returnResult->success = 1;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Iscrizione salvata!";

            $confirmId = $id;

            // ricerco il dettaglio e vado a fare un nuovo inserimento SOLO
            // in caso di cambio PRIMA scelta

            $stmtSearch = $conn->prepare("Select *
                        from iscrizioni_dettaglio
                        where id_iscrizione = :id
                            order by id desc
                        ");
            $stmtSearch->bindParam(':id', $id);

            $id = $data[id];
            $stmtSearch->execute();

            $rows = $stmtSearch->fetchAll();
            foreach ($rows as $row) {
                $found = true;

                error_log(print_r('First Old Row:', true), 0);
                error_log(print_r($row, true), 0);

                if ($data[firChoice] == $row[firChoice]) {
                    $created_at = $row[created_at];
                }

                if (
                    $data[firChoice] == $row[firChoice] &&
                    $data[secChoice] == $row[secChoice] &&
                    $data[firWeek1] == $row[firWeek1] &&
                    $data[firWeek2] == $row[firWeek2] &&
                    $data[secWeek1] == $row[secWeek1] &&
                    $data[secWeek2] == $row[secWeek2]
                ) {
                    $notSave = true;
                }

                break;
            }
        }

        $notSave = false;
        error_log(print_r('Not Save:', true), 0);
        error_log(print_r($notSave, true), 0);
        error_log(print_r('created_at:', true), 0);
        error_log(print_r($created_at, true), 0);

        if ($notSave === false || $notSave === '') {
            error_log(print_r('Start check iscrizioni_dettaglio_h:', true), 0);

            try {
                // copio i record in una tabella di storico
                $stmtInsert = $conn->prepare("

                        INSERT INTO iscrizioni_dettaglio_h 
                        (id_iscrizione, firChoice, secChoice, firWeek1, firWeek2, 
                        secWeek1, secWeek2, created_at, status, updated_at, db_insert_at )

                        SELECT 
                        id_iscrizione, firChoice, secChoice, firWeek1, firWeek2, 
                        secWeek1, secWeek2, created_at, status, updated_at, db_insert_at 
                        FROM iscrizioni_dettaglio where id_iscrizione = :id_iscrizione" );
                        

                $stmtInsert->bindParam(':id_iscrizione', $id_iscrizione);
                $id_iscrizione = $confirmId;
                $stmtInsert->execute();

            } catch (Exception $ex){

            }

            try {

                error_log(print_r('Start save Detail:', true), 0);

                $stmtInsert = $conn->prepare("INSERT INTO iscrizioni_dettaglio
                                    (
                                        id_iscrizione,

                                        firChoice,
                                        secChoice,
                                        firWeek1,
                                        firWeek2,
                                        secWeek1,
                                        secWeek2,

                                        created_at,
                                        db_insert_at,
                                        status
                                    )
                                    VALUES (
                                        :id_iscrizione,

                                        :firChoice,
                                        :secChoice,
                                        :firWeek1,
                                        :firWeek2,
                                        :secWeek1,
                                        :secWeek2,

                                        :created_at,
                                        NOW(),
                                        :status
                                    )
                            ");

                $stmtInsert->bindParam(':id_iscrizione', $id_iscrizione);

                $id_iscrizione = $confirmId;

                $stmtInsert->bindParam(':firChoice', $firChoice);
                $stmtInsert->bindParam(':secChoice', $secChoice);
                $stmtInsert->bindParam(':firWeek1', $firWeek1);
                $stmtInsert->bindParam(':firWeek2', $firWeek2);
                $stmtInsert->bindParam(':secWeek1', $secWeek1);
                $stmtInsert->bindParam(':secWeek2', $secWeek2);
                $stmtInsert->bindParam(':created_at', $created_at);
                $stmtInsert->bindParam(':status', $status);

                $firChoice = $data[firChoice];
                $secChoice = $data[secChoice];
                $firWeek1 = $data[firWeek1];
                $firWeek2 = $data[firWeek2];
                $secWeek1 = $data[secWeek1];
                $secWeek2 = $data[secWeek2];

                if ($firWeek1 == '') $firWeek1 = 0;
                if ($firWeek2 == '') $firWeek2 = 0;
                if ($secWeek1 == '') $secWeek1 = 0;
                if ($secWeek2 == '') $secWeek2 = 0;

                $status = 'REG';

                if ($firWeek1 == 0 || $firWeek2 == 0 ) {
                    $status = 'LIS';
                }
                if ($frequenza_gruppi == 0) {
                    $status = 'LIS';
                }

                /* TOLTO il check sul COMUNE DI CARPANETO
                if ($comune_res_n !== '408033011' ){
                    $status = 'LIS';
                } 
                */

                if ($ruolo == 'E') {
                    $status = 'LIS';
                }    

                if ($ruolo == 'E' && $accetta_edu_auto == 1) {
                    $status = 'REG';
                }
                if ($ruolo == 'C' && $accetta_edu_auto == 1) {
                    $status = 'REG';
                }
                if ($ruolo == 'R' && $accetta_rag_auto == 1) {
                    $status = 'REG';
                }

                /* PRENDIAMO subito i cuochi che è meglio!
                if ($ruolo == 'C') {
                    $status = 'LIS';
                }    */

                if ($eccezione2020) {
                    $status = 'REG';
                }

                if ($auto_gestione == 1) {
                    $status = 'REG';
                }

                $stmtInsert->execute();

                // cancello i record vecchi dalla tabella delle iscrizioni
                $lastId = $conn->lastInsertId();


                error_log(print_r('Detail saved!', true), 0);

            } catch (Exception $ex){


                error_log(print_r('Detail NOT saved:', true), 0);
                error_log(print_r($ex, true), 0);

                $returnResult->returnMessages = [];
                $returnResult->returnMessages[] = "Error: " . $ex;
                $returnResult->success = 0;
                $returnResult->result = 0;
        
                $conn = null;
                return $returnResult;

            }


            try {
                // copio i record in una tabella di storico
                $stmtDelete = $conn->prepare("
                        delete  
                        FROM iscrizioni_dettaglio 
                        where id_iscrizione = :id_iscrizione
                                and id < :id
                        " );
                $stmtDelete->bindParam(':id_iscrizione', $id_iscrizione);
                $id_iscrizione = $confirmId;
                $stmtDelete->bindParam(':id', $id);
                $id = $lastId;
                $stmtDelete->execute();

            } catch (Exception $ex){

            }


            $obj = new stdClass;
            $obj->status = $status;

            $stmtNow = $conn->prepare("SELECT NOW() as `now`");
            $stmtNow->execute();
            $rows = $stmtNow->fetchAll();
            foreach ($rows as $row) {
                $obj->db_insert_at = $row['now'];
            }

            $returnResult->returnObject = $obj;
            $returnResult->result = 1;
            $returnResult->success = 1;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Iscrizione salvata!";

            // se si tratta di ragazzi recupero la posizione per verificarne lo stato

            $ricercaPosizione = false;
            if ($ruolo == 'R') {
                $ricercaPosizione = true;
            }

            if ($eccezione2020) {
                $ricercaPosizione = true;
            }

            if ($ricercaPosizione) {

                $status1 = "NULL";
                if($status == "REG") {
                    $status1 = "CONF";
                }

                // getYoungPosition
                $stmtSearch = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                            as position
                            from iscrizioni_dettaglio  inner join iscrizioni
                                on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
                        where db_insert_at < '$obj->db_insert_at'
                        and ( status = '$status' || status = '$status1' )
                        and firChoice = '$firChoice'
                        and ruolo = 'R'
                        and is_deleted = 0
                          ");

                $returnResult->returnMessages[] = $stmtSearch;

                $stmtSearch->execute();
                $rows = $stmtSearch->fetchAll();

                $position = 0;
                foreach ($rows as $row) {
                    $position = $row[position] + 1;
                }

                if ($position > $firChoicePosti) {
                    $status = 'RIS';
                }
            }

            if ($posti_max_totali != 999 && $posti_max_totali != null && $posti_max_totali != NULL) {

                $stmtSearch = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                            as position
                            from iscrizioni_dettaglio  inner join iscrizioni
                                on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
                        where db_insert_at < '$obj->db_insert_at'
                        and status = '$status'
                        and firChoice = '$firChoice'
                        and is_deleted = 0
                          ");

                $returnResult->returnMessages[] = $stmtSearch;

                $stmtSearch->execute();
                $rows = $stmtSearch->fetchAll();

                $position = 0;
                foreach ($rows as $row) {
                    $position = $row[position] + 1;
                }

                if ($position > $posti_max_totali) {
                    $status = 'RIS';
                }
            }

            // invio mail di conferma della registrazione dell'iscrizione
            $stmtSearch = $conn->prepare("Select * from utenti
                                where id = :id
                        ");
            $stmtSearch->bindParam(':id', $created_by);
            $created_by = $created_by_ok;
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();
            foreach ($rows as $row) {

                $mailsto = [];
                $mailsto[] = $row[username];
                $mailsto[] = $row[email1];
                $mailsto[] = $row[email2];
                $mailsto[] = $email;

                $returnResult->returnMessages[] = json_encode($mailsto);

                // $mailInfo = recuperaInfoMailRegistrazioneIsc($data);
                $mailInfo = recuperaInfoMailRegistrazioneIscPerStato($data, $status, $ruolo);
                

                invioMail($mailsto, $mailInfo->oggetto, $mailInfo->testo);
                continue;
            }
        }

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaInfoMailAnnullaIsc($data)
{
    $mailInfo = new MailInfo();

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $firChoiceTitle = '';
        $secChoiceTitle = '';

        $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
        $stmtSearch->bindParam(':id', $id_firChoice);
        $id_firChoice = $data[firChoice];
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $firChoiceTitle = $row[titolo];
        }

        if ($data[secChoice] !== null && $data[secChoice] !== undefined && $data[secChoice] !== '') {
            $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
            $stmtSearch->bindParam(':id', $id_secChoice);
            $id_secChoice = $data[secChoice];
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();
            foreach ($rows as $row) {
                $secChoiceTitle = $row[titolo];
            }
        }
        $conn = null;
    } catch (Exception $e) {
        $conn = null;
    }


    $returnRes = recuperaTestoImpostazione('mail_isc_non_confermata_oggetto');
    $mailInfo->oggetto = $returnRes->returnObject;

    $returnRes = recuperaTestoImpostazione('mail_isc_non_confermata_testo');
    $mailInfo->testo = $returnRes->returnObject;

    $mailInfo->testo = str_replace("data[name]", $data[name], $mailInfo->testo);
    $mailInfo->testo = str_replace("data[surname]", $data[surname], $mailInfo->testo);

    return $mailInfo;
}

function recuperaInfoMailInAttesa($data)
{
    $mailInfo = new MailInfo();

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $firChoiceTitle = '';
        $secChoiceTitle = '';

        $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
        $stmtSearch->bindParam(':id', $id_firChoice);
        $id_firChoice = $data[firChoice];
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $firChoiceTitle = $row[titolo];
        }

        if ($data[secChoice] !== null && $data[secChoice] !== undefined && $data[secChoice] !== '') {
            $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
            $stmtSearch->bindParam(':id', $id_secChoice);
            $id_secChoice = $data[secChoice];
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();
            foreach ($rows as $row) {
                $secChoiceTitle = $row[titolo];
            }
        }
        $conn = null;
    } catch (Exception $e) {
        $conn = null;
    }

    $returnRes = recuperaTestoImpostazione('mail_isc_in_attesa_oggetto');
    $mailInfo->oggetto = $returnRes->returnObject;

    $returnRes = recuperaTestoImpostazione('mail_isc_in_attesa_testo');
    $mailInfo->testo = $returnRes->returnObject;

    $mailInfo->testo = str_replace("data[name]", $data[name], $mailInfo->testo);
    $mailInfo->testo = str_replace("data[surname]", $data[surname], $mailInfo->testo);
    $mailInfo->testo = str_replace("firChoiceTitle", $firChoiceTitle, $mailInfo->testo);
    $mailInfo->testo = str_replace("secChoiceTitle", $secChoiceTitle, $mailInfo->testo);

    return $mailInfo;
}


function recuperaInfoMailRegistrazioneIsc($data)
{
    $mailInfo = new MailInfo();

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $firChoiceTitle = '';
        $secChoiceTitle = '';

        $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
        $stmtSearch->bindParam(':id', $id_firChoice);
        $id_firChoice = $data[firChoice];
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $firChoiceTitle = $row[titolo];
        }

        if ($data[secChoice] !== null && $data[secChoice] !== undefined && $data[secChoice] !== '') {
            $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
            $stmtSearch->bindParam(':id', $id_secChoice);
            $id_secChoice = $data[secChoice];
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();
            foreach ($rows as $row) {
                $secChoiceTitle = $row[titolo];
            }
        }
        $conn = null;
    } catch (Exception $e) {
        $conn = null;
    }

    $returnRes = recuperaTestoImpostazione('mail_isc_registrata_oggetto');
    $mailInfo->oggetto = $returnRes->returnObject;

    $returnRes = recuperaTestoImpostazione('mail_isc_registrata_testo');
    $mailInfo->testo = $returnRes->returnObject;

    $mailInfo->testo = str_replace("data[name]", $data[name], $mailInfo->testo);
    $mailInfo->testo = str_replace("data[surname]", $data[surname], $mailInfo->testo);
    $mailInfo->testo = str_replace("firChoiceTitle", $firChoiceTitle, $mailInfo->testo);
    $mailInfo->testo = str_replace("secChoiceTitle", $secChoiceTitle, $mailInfo->testo);

    error_log(print_r('mailInfo Data:', true), 0);
    error_log(print_r($mailInfo, true), 0);

    return $mailInfo;
}


function recuperaInfoMailRegistrazioneIscPerStato($data, $status, $ruolo)
{
    $mailInfo = new MailInfo();

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $firChoiceTitle = '';
        $secChoiceTitle = '';

        $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
        $stmtSearch->bindParam(':id', $id_firChoice);
        $id_firChoice = $data[firChoice];
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $firChoiceTitle = $row[titolo];
        }

        if ($data[secChoice] !== null && $data[secChoice] !== undefined && $data[secChoice] !== '') {
            $stmtSearch = $conn->prepare("Select titolo
                                            from turni
                             where id = :id
                    ");
            $stmtSearch->bindParam(':id', $id_secChoice);
            $id_secChoice = $data[secChoice];
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();
            foreach ($rows as $row) {
                $secChoiceTitle = $row[titolo];
            }
        }
        $conn = null;
    } catch (Exception $e) {
        $conn = null;
    }

    if ($status == 'REG'){
        $returnRes = recuperaTestoImpostazione('mail_isc_registrata_oggetto');
        $mailInfo->oggetto = $returnRes->returnObject;

        $returnRes = recuperaTestoImpostazione('mail_isc_registrata_testo');
        $mailInfo->testo = $returnRes->returnObject;
    }

    if ($status == 'RIS'){
        $returnRes = recuperaTestoImpostazione('mail_isc_reg_riserva_oggetto');
        $mailInfo->oggetto = $returnRes->returnObject;

        $returnRes = recuperaTestoImpostazione('mail_isc_reg_riserva_testo');
        $mailInfo->testo = $returnRes->returnObject;
    }  
    
    if ($status == 'LIS' && $ruolo == 'R'){
        $returnRes = recuperaTestoImpostazione('mail_isc_reg_in_attesa_oggetto');
        $mailInfo->oggetto = $returnRes->returnObject;

        $returnRes = recuperaTestoImpostazione('mail_isc_reg_in_attesa_testo');
        $mailInfo->testo = $returnRes->returnObject;
    }  

    if ($status == 'LIS' && $ruolo != 'R'){
        $returnRes = recuperaTestoImpostazione('mail_isc_edu_oggetto');
        $mailInfo->oggetto = $returnRes->returnObject;

        $returnRes = recuperaTestoImpostazione('mail_isc_edu_testo');
        $mailInfo->testo = $returnRes->returnObject;
    }  

    $mailInfo->testo = str_replace("data[name]", $data[name], $mailInfo->testo);
    $mailInfo->testo = str_replace("data[surname]", $data[surname], $mailInfo->testo);
    $mailInfo->testo = str_replace("firChoiceTitle", $firChoiceTitle, $mailInfo->testo);
    $mailInfo->testo = str_replace("secChoiceTitle", $secChoiceTitle, $mailInfo->testo);

    error_log(print_r('mailInfo Data:', true), 0);
    error_log(print_r($mailInfo, true), 0);

    return $mailInfo;
}


function ricercaAnagraficaPerNomeCognome($iscrizione)
{
    try {
        if (
            $iscrizione[nome] === '' || $iscrizione[nome] === undefined ||
            $iscrizione[nome] === null
        ) {
            $iscrizione[nome] = $iscrizione[name];
            $iscrizione[cognome] = $iscrizione[surname];
        }

        if (
            $iscrizione[name] === '' || $iscrizione[name] === undefined ||
            $iscrizione[name] === null
        ) {
            $iscrizione[name] = $iscrizione[nome];
            $iscrizione[surname] = $iscrizione[cognome];
        }

        $completeResult = ricercaAnagrafica($iscrizione);

        return $completeResult;
    } catch (Exception $err) {
        return null;
    }
    return null;
}

function ricercaAnagraficaPerIscrizione($iscrizione)
{

    try {
        $iscrizione[nome] = $iscrizione[name];
        $iscrizione[cognome] = $iscrizione[surname];

        $completeResult = ricercaAnagrafica($iscrizione);

        return $completeResult->returnObject[0]->id;
    } catch (Exception $err) {
        return null;
    }
    return null;
}


function ricercaAnagraficaDaIscrizione($iscrizione)
{

    if ($iscrizione[nome] === null || $iscrizione[nome] === '') {
        return null;
    }

    $iscrizione[name] = $iscrizione[nome];
    $iscrizione[surname] = $iscrizione[cognome];

    try {
        $completeResult = ricercaAnagrafica($iscrizione);
        if ($completeResult->result == 0) {
            return $completeResult;
        }

        $completeResult = aggiornaIscrizioneIdAnagrafica($iscrizione[id_iscrizione], $completeResult->returnObject[0]->id);

        return $completeResult;
    } catch (Exception $err) {
        return null;
    }
    return null;
}


function creaAnagraficaDaIscrizione($idIsc, $confirm = false)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


        $stmtSearch = $conn->prepare("select * from iscrizioni
                where id = :id
        ");        
        $stmtSearch->bindParam(':id', $id);
        $id = $idIsc;

        $singleIsc = array();
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {

            error_log(print_r('row Data:', true), 0);
            error_log(print_r($row, true), 0);

            $act = new ArrayObject();  
            $act['nome'] = $row['name'];
            $act['cognome'] = $row['surname'];
            $act['data_nascita'] = $row['birthDay'];
            $act['comune_n_ques'] = $row['comune_n_ques'];
            $act['provincia_n_ques'] = $row['provincia_n_ques'];
            $act['stato_n_ques'] = $row['stato_n_ques'];
            $act['cittadinanza_n_ques'] = $row['cittadinanza_n_ques'];
            $act['sesso'] = $row['sesso'];
            $act['ruolo'] = $row['ruolo'];
            $act['cellulare_1'] = $row['cell'];
            $act['email_1'] = $row['email'];
            $act['creato_da'] = 1;
            $act['modificato_da'] = 1;
        }

        $returnAnagrafica = aggiornaAnagrafica2($act);
        $anagrafica = $returnAnagrafica->returnObject;

        error_log(print_r('anagrafica Data:', true), 0);
        error_log(print_r($anagrafica[0], true), 0);

        error_log(print_r('anagrafica ID:', true), 0);
        error_log(print_r($anagrafica['id'], true), 0);

        // IN UN SECONDO TEMPO ANDRO' AD ASSOCIARE L'ANAGRAFICA CREATA ALLA ISCRIZIONE

        $stmtUpdate = $conn->prepare("update iscrizioni

        set id_anag_base = :id_anag_base,
            anag_base_confirmed = :anag_base_confirmed

        where id = :id
        ");

        $stmtUpdate->bindParam(':id', $id);
        $stmtUpdate->bindParam(':id_anag_base', $id_anag_base);
        $stmtUpdate->bindParam(':anag_base_confirmed', $anag_base_confirmed);

        $id = $idIsc;
        $id_anag_base = $anagrafica['id'];
        $anag_base_confirmed = $confirm;

        $stmtUpdate->execute();

        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Iscrizione aggiornata!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}

function salvaPagamento($data) {

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('salvaPagamento Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT INTO pagamenti_doc
                                (id_iscrizione, doc_image )
                            VALUES (:id_iscrizione, :doc_image )
                    ");

        $stmtInsert->bindParam(':id_iscrizione', $id_iscrizione);
        $stmtInsert->bindParam(':doc_image', $doc_image);

        $id_iscrizione = $data[id_iscrizione];
        $doc_image = $data[image];
        $stmtInsert->execute();

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

function aggiornaIscrizioneIdAnagrafica($idIsc, $idAnag, $confirm = false)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("update iscrizioni

        set id_anag_base = :id_anag_base,
            anag_base_confirmed = :anag_base_confirmed

        where id = :id
        ");

        $stmtUpdate->bindParam(':id', $id);
        $stmtUpdate->bindParam(':id_anag_base', $id_anag_base);
        $stmtUpdate->bindParam(':anag_base_confirmed', $anag_base_confirmed);

        $id = $idIsc;
        $id_anag_base = $idAnag;
        $anag_base_confirmed = $confirm;

        $stmtUpdate->execute();

        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Iscrizione aggiornata!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}

function setChoice($choice)
{
    if ($choice === '0' || $choice == 0 || $choice == null) {
        return false;
    } else {
        return true;
    }
}


function getIscriptions($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select iscrizioni.*,

                                    anag_comune.descrizione as comune_nascita,
                                    anag_comune_res.descrizione as comune_res,

                                    iscrizioni_dettaglio.status,
                                    iscrizioni_dettaglio.firChoice,
                                    iscrizioni_dettaglio.secChoice,
                                    iscrizioni_dettaglio.firWeek1,
                                    iscrizioni_dettaglio.firWeek2,
                                    iscrizioni_dettaglio.secWeek1,
                                    iscrizioni_dettaglio.secWeek2,
                                    iscrizioni_dettaglio.created_at,
                                    iscrizioni_dettaglio.updated_at,
                                    iscrizioni_dettaglio.db_insert_at,
                                    iscrizioni_dettaglio.forced_note,
                                    turni.titolo as firChoiceTitle,
                                    turni.fine_isc as firChoiceEndIsc,
                                    turni.inizio_isc as firChoiceStartIsc,
                                    turni.gruppo as firChoiceGroup

                                from iscrizioni
                                    left outer join iscrizioni_dettaglio
                                    on iscrizioni.id = iscrizioni_dettaglio.id_iscrizione

                                    left outer join turni
                                    on turni.id = iscrizioni_dettaglio.firChoice

                                    left outer join anag_comune
                                    on iscrizioni.comune_n_ques = anag_comune.codice

                                    left outer join anag_comune as anag_comune_res
                                    on iscrizioni.comune_res_n = anag_comune_res.codice

                             where created_by = :created_by
                               and is_deleted = 0
                            order by year desc, iscrizioni_dettaglio.id desc
                    ");
        $stmtSearch->bindParam(':created_by', $created_by);
        $created_by = $data[created_by];

        $alldata = array();
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {

            $act = new Iscription();

            $act->id = $row['id'];
            $act->name = $row['name'];
            $act->surname = $row['surname'];
            $act->year = $row['year'];
            $act->birthDay = $row['birthDay'];
            $act->birthYear = substr($row['birthDay'], 0, 4);
            $act->isOfCarpaneto = $row['isOfCarpaneto'];
            $act->status = $row['status'];
            if ($act->status == null) $act->status = 'REG';

            $act->sesso = $row['sesso'];
            $act->comune_n_ques = $row['comune_n_ques'];
            $act->comune_nascita = $row['comune_nascita'];
            $act->provincia_n_ques = $row['provincia_n_ques'];
            $act->stato_n_ques = $row['stato_n_ques'];
            $act->cittadinanza_n_ques = $row['cittadinanza_n_ques'];

            $act->comune_res_n = $row['comune_res_n'];
            $act->comune_res = $row['comune_res'];
            $act->via_res = $row['via_res'];
            $act->provincia_res = $row['provincia_res'];
            $act->pullman = $row['pullman'];
            $act->frequenza_gruppi = $row['frequenza_gruppi'];

            if ($row['pullman'] == '0') {
                $act->pullman = false;
            } else {
                $act->pullman = true;
            }

            if ($row['frequenza_gruppi'] == '0') {
                $act->frequenza_gruppi = false;
            } else {
                $act->frequenza_gruppi = true;
            }

            if ($row['is_deleted'] == '0') {
                $act->is_deleted = false;
            } else {
                $act->is_deleted = true;
            }

            $act->data_arrivo = $row['data_arrivo'];
            $act->data_partenza = $row['data_partenza'];

            $act->ruolo = $row['ruolo'];
            $act->note = $row['note'];
            $act->stanza = $row['stanza'];
            $act->maglia = $row['maglia'];

            $act->firChoice = $row['firChoice'];
            $act->firChoiceTitle = $row['firChoiceTitle'];
            $act->secChoice = $row['secChoice'];

            $act->cell = $row['cell'];
            $act->email = $row['email'];

            if ($data[gruppo] !== null && $data[gruppo] !== '') {
                if ( $row['firChoiceGroup'] !== $data[gruppo]) {
                    continue;
                }
            }

            $fine_isc = DateTime::createFromFormat("Y-m-d", $row['firChoiceEndIsc']);
            $inizio_isc = DateTime::createFromFormat("Y-m-d", $row['firChoiceStartIsc']);
            $today = new DateTime();
            $act->isc_aperta = true;
            if ($today > $fine_isc) {
                $act->isc_aperta = false;
            }
            if ($today < $inizio_isc) {
                $act->isc_aperta = false;
            }

            /*
            $act->firWeek1 = $row['firWeek1'];
            $act->firWeek2 = $row['firWeek2'];
            $act->secWeek1 = $row['secWeek1'];
            $act->secWeek2 = $row['secWeek2'];
            */

            if ($row['firWeek1'] == '0' || $row['firWeek1'] == NULL) {
                $act->firWeek1 = false;
            } else {
                $act->firWeek1 = true;
            }

            if ($row['firWeek2'] == '0' || $row['firWeek2'] == NULL) {
                $act->firWeek2 = false;
            } else {
                $act->firWeek2 = true;
            }

            if ($row['secWeek1'] == '0' || $row['secWeek1'] == NULL) {
                $act->secWeek1 = false;
            } else {
                $act->secWeek1 = true;
            }

            if ($row['secWeek2'] == '0' || $row['secWeek2'] == NULL) {
                $act->secWeek2 = false;
            } else {
                $act->secWeek2 = true;
            }

            $act->created_at = $row['created_at'];
            $act->updated_at = $row['updated_at'];
            $act->db_insert_at = $row['db_insert_at'];
            $act->created_by = $row['created_by'];

            $act->forced_note = '';
            if ($row[forced_note] !== null && $row[forced_note] !== '') {
                $act->forced_note = $row['forced_note'];
            }

            // per ogni anno aggiungo una sola iscrizione
            $found = false;
            foreach ($alldata as $fIsc) {
                if (
                    $fIsc->name === $act->name
                    && $fIsc->surname === $act->surname
                    && $fIsc->year === $act->year
                ) {
                    $found = true;
                    if(substr($fIsc->name, 0, 3) === 'DON' ){
                        $found = false;
                    }
                }
            }

            // consento più iscrizioni all'anno
            // if ($found === false) {
                $alldata[] = $act;
            // }
        }

        $returnResult->returnObject = $alldata;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Iscrizioni recuperate!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}



function recuperaDocPagIscrizione($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    error_log(print_r('recuperaDocPagIscrizione Data:', true), 0);
    error_log(print_r($data, true), 0);

    $with_files = null;
    try {
        $with_files = $data[with_files];
    } catch (Exception $Err) { }

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select id, id_iscrizione, created_at
                                from pagamenti_doc
                                where id_iscrizione = $data[id_iscrizione]
                    ");

        if ($with_files !== null) {
            $stmtSearch = $conn->prepare("Select *
                                from pagamenti_doc
                                where id_iscrizione = $data[id_iscrizione]
                    ");
        }

        $alldata = array();
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $alldata[] = $row;
        }

        $returnResult->returnObject = $alldata;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "recuperaDocPagIscrizione recuperate!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}


function getYoungPosition($data) {
    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->returnMessages = [];

    $eccezione2020 = false;

    try {

        $idTurno = $data[idTurno];
        $created_at = $data[created_at];
        $status = $data[status];
        $ruolo = $data[ruolo];

        $returnResult->returnMessages[] = "Lista_attesa: ".$lista_attesa;

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $status1 = "NULL";
        if ($status == "REG"){
            $status1 = "CONF";
        }

        $stmtSearch = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                as position
                from iscrizioni_dettaglio  inner join iscrizioni
                    on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
            where db_insert_at < '$created_at'
            and ( status = '$status' || status = '$status1' )
            and firChoice = '$idTurno'            
            and ruolo = 'R'       
            and is_deleted = 0
                ");

        if ($eccezione2020) {
            $stmtSearch = $conn->prepare("select count(iscrizioni_dettaglio.id) 
                        as position
                        from iscrizioni_dettaglio  inner join iscrizioni
                            on iscrizioni_dettaglio.id_iscrizione = iscrizioni.id   
                    where db_insert_at < '$created_at'
                    and  ( status = '$status' || status = '$status1' )
                    and firChoice = '$idTurno'  
            ");
        }

        $returnResult->returnMessages[] = $stmtSearch;

        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();

        $position = 0;
        foreach ($rows as $row) {
            $position = $row[position] + 1;
        }
        if ($position == 0){
            $position = 1;
        }        

        $obj = new stdClass;
        $obj->position = $position;
        
        $returnResult->returnObject = $obj;
        $returnResult->success = 1;
        $returnResult->returnMessages[] = "Posizione recuperata!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}

function getHistoryIscriptionById($data){

    $returnResult = getIscriptionById($data);
    $inscription = $returnResult->returnObject[0];

    $returnResult = getHistoryDetailForId($inscription->id);

    $inscription->history = $returnResult->returnObject;
    $returnResult->returnObject = $inscription;

    return $returnResult;
}

function getHistoryDetailForId($id_iscrizione) {


    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select iscrizioni_dettaglio_h.*, turni.titolo

                                from iscrizioni_dettaglio_h
                                            inner join turni 
                                        on iscrizioni_dettaglio_h.firChoice = turni.id

                             where id_iscrizione = :id_iscrizione
                            order by db_insert_at desc
                    ");
        $stmtSearch->bindParam(':id_iscrizione', $id_isc);
        $id_isc = $id_iscrizione;

        $alldata = array();
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {

            $act = new IscriptionHistory();

            $act->id = $row['id'];
            $act->id_iscrizione = $row['id_iscrizione'];
            $act->created_at = $row['created_at'];
            $act->status = $row['status'];
            $act->db_insert_at = $row['db_insert_at'];
            $act->firChoice = $row['firChoice'];
            $act->firChoiceTitle = $row['titolo'];
            $act->firWeek1 = $row['firWeek1'];
            $act->firWeek2 = $row['firWeek2'];

            if ($row['firWeek1'] == '0' || $row['firWeek1'] == NULL) {
                $act->firWeek1 = "";
            } else {
                $act->firWeek1 = "X";
            }
            if ($row['firWeek2'] == '0' || $row['firWeek2'] == NULL) {
                $act->firWeek2 = "";
            } else {
                $act->firWeek2 = "X";
            }

            $alldata[] = $act;
        }

        $returnResult->returnObject = $alldata;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Storico Iscrizione recuperato!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;

}


function getIscriptionById($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select iscrizioni.*,

                                    anag_comune.descrizione as comune_nascita,
                                    anag_comune_res.descrizione as comune_res,

                                    iscrizioni_dettaglio.status,
                                    iscrizioni_dettaglio.firChoice,
                                    iscrizioni_dettaglio.secChoice,
                                    iscrizioni_dettaglio.firWeek1,
                                    iscrizioni_dettaglio.firWeek2,
                                    iscrizioni_dettaglio.secWeek1,
                                    iscrizioni_dettaglio.secWeek2,
                                    iscrizioni_dettaglio.created_at,
                                    iscrizioni_dettaglio.updated_at,
                                    iscrizioni_dettaglio.forced_note,
                                    turni.titolo as firChoiceTitle,
                                    turni.fine_isc as firChoiceEndIsc,
                                    turni.inizio_isc as firChoiceStartIsc,
                                    turni.gruppo as firChoiceGroup

                                from iscrizioni
                                    left outer join iscrizioni_dettaglio
                                    on iscrizioni.id = iscrizioni_dettaglio.id_iscrizione

                                    left outer join turni
                                    on turni.id = iscrizioni_dettaglio.firChoice

                                    left outer join anag_comune
                                    on iscrizioni.comune_n_ques = anag_comune.codice

                                    left outer join anag_comune as anag_comune_res
                                    on iscrizioni.comune_res_n = anag_comune_res.codice

                             where iscrizioni.id = :id_iscrizione
                               and is_deleted = 0
                            order by year desc, iscrizioni_dettaglio.id desc
                    ");
        $stmtSearch->bindParam(':id_iscrizione', $id_iscrizione);
        $id_iscrizione = $data[id_iscrizione];

        $alldata = array();
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {

            $act = new Iscription();

            $act->id = $row['id'];
            $act->name = $row['name'];
            $act->surname = $row['surname'];
            $act->year = $row['year'];
            $act->birthDay = $row['birthDay'];
            $act->birthYear = substr($row['birthDay'], 0, 4);
            $act->isOfCarpaneto = $row['isOfCarpaneto'];
            $act->status = $row['status'];
            if ($act->status == null) $act->status = 'REG';

            $act->sesso = $row['sesso'];
            $act->comune_n_ques = $row['comune_n_ques'];
            $act->comune_nascita = $row['comune_nascita'];
            $act->provincia_n_ques = $row['provincia_n_ques'];
            $act->stato_n_ques = $row['stato_n_ques'];
            $act->cittadinanza_n_ques = $row['cittadinanza_n_ques'];

            $act->comune_res_n = $row['comune_res_n'];
            $act->comune_res = $row['comune_res'];
            $act->via_res = $row['via_res'];
            $act->provincia_res = $row['provincia_res'];
            $act->pullman = $row['pullman'];
            $act->frequenza_gruppi = $row['frequenza_gruppi'];

            if ($row['pullman'] == '0') {
                $act->pullman = false;
            } else {
                $act->pullman = true;
            }

            if ($row['frequenza_gruppi'] == '0') {
                $act->frequenza_gruppi = false;
            } else {
                $act->frequenza_gruppi = true;
            }

            if ($row['is_deleted'] == '0') {
                $act->is_deleted = false;
            } else {
                $act->is_deleted = true;
            }


            $act->ruolo = $row['ruolo'];
            $act->note = $row['note'];
            $act->stanza = $row['stanza'];
            $act->maglia = $row['maglia'];

            $act->firChoice = $row['firChoice'];
            $act->firChoiceTitle = $row['firChoiceTitle'];
            $act->secChoice = $row['secChoice'];

            if ($data[gruppo] !== null && $data[gruppo] !== '') {
                if ( $row['firChoiceGroup'] !== $data[gruppo]) {
                    continue;
                }
            }

            $fine_isc = DateTime::createFromFormat("Y-m-d", $row['firChoiceEndIsc']);
            $inizio_isc = DateTime::createFromFormat("Y-m-d", $row['firChoiceStartIsc']);
            $today = new DateTime();
            $act->isc_aperta = true;
            if ($today > $fine_isc) {
                $act->isc_aperta = false;
            }
            if ($today < $inizio_isc) {
                $act->isc_aperta = false;
            }

            /*
            $act->firWeek1 = $row['firWeek1'];
            $act->firWeek2 = $row['firWeek2'];
            $act->secWeek1 = $row['secWeek1'];
            $act->secWeek2 = $row['secWeek2'];
            */

            if ($row['firWeek1'] == '0' || $row['firWeek1'] == NULL) {
                $act->firWeek1 = false;
            } else {
                $act->firWeek1 = true;
            }

            if ($row['firWeek2'] == '0' || $row['firWeek2'] == NULL) {
                $act->firWeek2 = false;
            } else {
                $act->firWeek2 = true;
            }

            if ($row['secWeek1'] == '0' || $row['secWeek1'] == NULL) {
                $act->secWeek1 = false;
            } else {
                $act->secWeek1 = true;
            }

            if ($row['secWeek2'] == '0' || $row['secWeek2'] == NULL) {
                $act->secWeek2 = false;
            } else {
                $act->secWeek2 = true;
            }

            $act->created_at = $row['created_at'];
            $act->updated_at = $row['updated_at'];
            $act->created_by = $row['created_by'];
            
            $act->forced_note = '';
            if ($row[forced_note] !== null && $row[forced_note] !== '') {
                $act->forced_note = $row['forced_note'];
            }

            // per ogni anno aggiungo una sola iscrizione
            $found = false;
            foreach ($alldata as $fIsc) {
                if (
                    $fIsc->name === $act->name
                    && $fIsc->surname === $act->surname
                    && $fIsc->year === $act->year
                ) {
                    $found = true;
                    if(substr($fIsc->name, 0, 3) === 'DON' ){
                        $found = false;
                    }
                }
            }

            if ($found === false) {
                $alldata[] = $act;
            }
        }

        $returnResult->returnObject = $alldata;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Iscrizioni recuperate!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaDatiIscrizione($id_iscrizione)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        error_log(print_r('recuperaDatiIscrizione:' . $id_iscrizione . ' #', true), 0);

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select iscrizioni.*,

                                    anag_comune.descrizione as comune_nascita,
                                    anag_comune_res.descrizione as comune_res,

                                    iscrizioni_dettaglio.status,
                                    iscrizioni_dettaglio.firChoice,
                                    iscrizioni_dettaglio.secChoice,
                                    iscrizioni_dettaglio.firWeek1,
                                    iscrizioni_dettaglio.firWeek2,
                                    iscrizioni_dettaglio.secWeek1,
                                    iscrizioni_dettaglio.secWeek2,
                                    iscrizioni_dettaglio.created_at,
                                    iscrizioni_dettaglio.updated_at

                                from iscrizioni
                                    left outer join iscrizioni_dettaglio
                                    on iscrizioni.id = iscrizioni_dettaglio.id_iscrizione

                                    left outer join anag_comune
                                    on iscrizioni.comune_n_ques = anag_comune.codice

                                    left outer join anag_comune as anag_comune_res
                                    on iscrizioni.comune_res_n = anag_comune_res.codice

                             where iscrizioni.id = :id_iscrizione
                    ");
        $stmtSearch->bindParam(':id_iscrizione', $id_iscrizione);

        $alldata = array();
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {

            $act = new Iscription();

            $act->id = $row['id'];
            $act->name = $row['name'];
            $act->surname = $row['surname'];
            $act->year = $row['year'];
            $act->birthDay = $row['birthDay'];
            $act->birthYear = substr($row['birthDay'], 0, 4);
            $act->isOfCarpaneto = $row['isOfCarpaneto'];
            $act->status = $row['status'];
            if ($act->status == null) $act->status = 'REG';

            $act->sesso = $row['sesso'];
            $act->comune_n_ques = $row['comune_n_ques'];
            $act->comune_nascita = $row['comune_nascita'];
            $act->provincia_n_ques = $row['provincia_n_ques'];
            $act->stato_n_ques = $row['stato_n_ques'];
            $act->cittadinanza_n_ques = $row['cittadinanza_n_ques'];

            $act->comune_res_n = $row['comune_res_n'];
            $act->comune_res = $row['comune_res'];
            $act->via_res = $row['via_res'];
            $act->provincia_res = $row['provincia_res'];
            $act->pullman = $row['pullman'];

            if ($row['pullman'] == '0') {
                $act->pullman = false;
            } else {
                $act->pullman = true;
            }

            $act->ruolo = $row['ruolo'];
            $act->note = $row['note'];
            $act->stanza = $row['stanza'];
            $act->maglia = $row['maglia'];

            $act->firChoice = $row['firChoice'];
            $act->secChoice = $row['secChoice'];
            $act->firWeek1 = $row['firWeek1'];
            $act->firWeek2 = $row['firWeek2'];
            $act->secWeek1 = $row['secWeek1'];
            $act->secWeek2 = $row['secWeek2'];

            $act->created_at = $row['created_at'];
            $act->updated_at = $row['updated_at'];
            $act->created_by = $row['created_by'];

            $act->id_anag_base = $row['id_anag_base'];

            continue;
        }

        error_log(print_r('recuperaDatiIscrizione:', true), 0);
        error_log(print_r($act, true), 0);

        $returnResult->returnObject = $act;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Iscrizione recuperata!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        error_log(print_r('recuperaDatiIscrizione Errore: ' . $e->getMessage(), true), 0);

        $conn = null;
    }

    return $returnResult;
}


function aggiornaIscrizione($data)
{

    $id_iscrizione = $data[id_iscrizione];
    $in_status = $data[stato_iscrizione];

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages = [];

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
        foreach ($rows as $row) {
            $last_id = $row[id];
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

        if ($status == 'ANN') {

            // invio mail di conferma della registrazione dell'iscrizione
            $stmtSearch = $conn->prepare("Select * from utenti
                        where username = :id
                            ");
            $stmtSearch->bindParam(':id', $created_by);
            $created_by = $data[creato_da];;
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();
            foreach ($rows as $row) {

                $mailsto = [];
                $mailsto[] = $row[username];
                $mailsto[] = $row[email1];
                $mailsto[] = $row[email2];


                $mailInfo = recuperaInfoMailAnnullaIsc($data);
                invioMail($mailsto, $mailInfo->oggetto, $mailInfo->testo);
                continue;
            }
        }

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

function forzaCambioTurnoById($data) {

    $returnResult = getIscriptionById($data);
    $inscription = $returnResult->returnObject[0];
    $inscription->idturno = $data[idturno];
    $inscription->firChoice = $data[idturno];
    $inscription->cell = $data[cellulare_1];
    $inscription->email = $data[email_1];

    $inscriptionarray = (array) $inscription;

    error_log(print_r('inscriptionarray Data:', true), 0);
    error_log(print_r($inscriptionarray, true), 0);

    $returnResult = saveIscription($inscriptionarray);
    return $returnResult;
}


function forzaCambioStatoById($data) {

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('Change status Iscription Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("update iscrizioni_dettaglio
                                            set status = :stato,
                                            forced_at = NOW(),
                                            forced_by = :forced_by
                                        where id_iscrizione = :id
                    ");
        $stmtUpdate->bindParam(':id', $id);
        $id = $data[id_iscrizione];
        $stmtUpdate->bindParam(':stato', $stato);
        $stato = $data[stato];

        $stmtUpdate->bindParam(':forced_by', $forced_by);
        $forced_by = $data[forced_by];

        $stmtUpdate->execute();

        if ($data[stato] == "WIL") {            
            $stmtUpdate = $conn->prepare("update iscrizioni
                                                set ruolo = 'W'
                                            where id = :id
                        ");
            $stmtUpdate->bindParam(':id', $id);
            $id = $data[id_iscrizione];
            $stmtUpdate->execute();
        }

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Stato aggiornato!";

        $conn = null;

    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function forzaCambioCompagniStanzaById($data) {

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('Change room Iscription Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("update iscrizioni
                                            set stanza = :stanza,
                                            forced_at = NOW(),
                                            forced_by = :forced_by
                                        where id = :id
                    ");
        $stmtUpdate->bindParam(':id', $id);
        $id = $data[id_iscrizione];
        $stmtUpdate->bindParam(':stanza', $stanza);
        $stanza = $data[stanza];

        $stmtUpdate->bindParam(':forced_by', $forced_by);
        $forced_by = $data[forced_by];

        $stmtUpdate->execute();

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Stanza aggiornata!";

        $conn = null;

    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function forzaCambioRuoloById($data) {

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('Change role Iscription Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("update iscrizioni
                                            set ruolo = :ruolo,
                                            forced_at = NOW(),
                                            forced_by = :forced_by
                                        where id = :id
                    ");
        $stmtUpdate->bindParam(':id', $id);
        $id = $data[id_iscrizione];
        $stmtUpdate->bindParam(':ruolo', $ruolo);
        $ruolo = $data[ruolo];

        $stmtUpdate->bindParam(':forced_by', $forced_by);
        $forced_by = $data[forced_by];

        $stmtUpdate->execute();

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Ruolo aggiornato!";

        $conn = null;

    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function forzaCambioStatoPagById($data) {

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('Change status Pag Iscription Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("update iscrizioni_dettaglio
                                            set status_pag = :stato_pag,
                                            forced_at = NOW(),
                                            forced_by = :forced_by
                                        where id_iscrizione = :id
                    ");
        $stmtUpdate->bindParam(':id', $id);
        $id = $data[id_iscrizione];
        $stmtUpdate->bindParam(':stato_pag', $stato_pag);
        $stato_pag = $data[stato_pag];

        $stmtUpdate->bindParam(':forced_by', $forced_by);
        $forced_by = $data[forced_by];

        $stmtUpdate->execute();

        if ($data[stato] == "WIL") {            
            $stmtUpdate = $conn->prepare("update iscrizioni
                                                set ruolo = 'W'
                                            where id = :id
                        ");
            $stmtUpdate->bindParam(':id', $id);
            $id = $data[id_iscrizione];
            $stmtUpdate->execute();
        }

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Stato aggiornato!";

        $conn = null;

    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function forza2SettimaneById($data) {

    $returnResult = getIscriptionById($data);
    $inscription = $returnResult->returnObject[0];
    $inscription->firWeek1 = 1;
    $inscription->firWeek2 = 1;
    $inscription->settimana_1 = true;
    $inscription->settimana_2 = true;
    $inscription->cell = $data[cellulare_1];
    $inscription->email = $data[email_1];

    $inscriptionarray = (array) $inscription;

    error_log(print_r('inscriptionarray Data:', true), 0);
    error_log(print_r($inscriptionarray, true), 0);

    $returnResult = saveIscription($inscriptionarray);
    return $returnResult;
}


function invioMailStatoIscrizione($data)
{

    $id_iscrizione = $data[id_iscrizione];

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $returnResult->returnMessages = [];

        $conn = connectToDbPDO();

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $returnResult->result = 1;

        $returnResult = getIscriptionById($data);

        if ( $returnResult->success === 1) {

            $inscription = $returnResult->returnObject[0];
            $inscription->idturno = $inscription->firChoice;
            error_log(print_r($inscription, true), 0);
            $status = $inscription->status;

            error_log(print_r('status Data:', true), 0);
            error_log(print_r($status, true), 0);

            if ($status == 'ANN') {
                // invio mail di conferma della registrazione dell'iscrizione
                $stmtSearch = $conn->prepare("Select * from utenti
                            where id = :id
                                ");
                $stmtSearch->bindParam(':id', $created_by);
                $created_by = $inscription->created_by;
                $stmtSearch->execute();
                $rows = $stmtSearch->fetchAll();
                foreach ($rows as $row) {

                    $mailsto = [];
                    $mailsto[] = $row[username];
                    $mailsto[] = $row[email1];
                    $mailsto[] = $row[email2];

                    $mailInfo = recuperaInfoMailAnnullaIsc((array)$inscription);
                    $returnResult = invioMail($mailsto, $mailInfo->oggetto, $mailInfo->testo);
                }
            }

            if ($status == 'REG') {
                // invio mail di conferma della registrazione dell'iscrizione
                $stmtSearch = $conn->prepare("Select * from utenti
                            where id = :id
                                ");
                $stmtSearch->bindParam(':id', $created_by);
                $created_by = $inscription->created_by;
                $stmtSearch->execute();
                $rows = $stmtSearch->fetchAll();
                foreach ($rows as $row) {

                    $mailsto = [];
                    $mailsto[] = $row[username];
                    $mailsto[] = $row[email1];
                    $mailsto[] = $row[email2];

                    // error_log(print_r('mailsto Data:', true), 0);
                    // error_log(print_r($mailsto, true), 0);

                    $mailInfo = recuperaInfoMailInAttesa((array)$inscription);

                    // error_log(print_r('mailInfo Data:', true), 0);
                    // error_log(print_r($mailInfo, true), 0);

                    $returnResult = invioMail($mailsto, $mailInfo->oggetto, $mailInfo->testo);
                }
            }

            if ($status == 'CONF') {
                $inscription->anagrafica = (array)$inscription;
                $mailInfo = recuperaInfoMailConfermaIsc((array)$inscription);
                error_log(print_r('mailInfo Data:', true), 0);
                error_log(print_r($mailInfo, true), 0);
                $returnResult = invioMail(recuperaMailsDaIsc($id_iscrizione), $mailInfo->oggetto, $mailInfo->testo);
            }

            if ($status == 'LIS') {
                $inscription->anagrafica = (array)$inscription;
                $mailInfo = recuperaInfoMailLisIsc((array)$inscription);
                error_log(print_r('mailInfo Data:', true), 0);
                error_log(print_r($mailInfo, true), 0);
                $returnResult = invioMail(recuperaMailsDaIsc($id_iscrizione), $mailInfo->oggetto, $mailInfo->testo);
            }

        }


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
