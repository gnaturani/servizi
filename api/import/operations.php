<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("../dati_anagrafici/anagrafica.php");

    function importDati($anagrafiche){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            $gruppo = $user[gruppo];

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $idturno = 999999;

            foreach($anagrafiche as $inAnagrafica){

                $stmtSelect = $conn->prepare("select * from anag_base where nome = :nome
                                                                        and cognome = :cognome ");

                $stmtSelect->bindParam(':nome', $nome);
                $stmtSelect->bindParam(':cognome', $cognome);

                $nome = $inAnagrafica['nome'];
                $cognome = $inAnagrafica['cognome'];

                $stmtSelect->execute();
                $rows = $stmtSelect->fetchAll();
                $found = false;
                $idanagrafica = 0;
                foreach ($rows as $row) {
                    $found = true;
                    $idanagrafica = $row[id];
                }

                if ($idturno === 999999) {
                    $stmtSelectT = $conn->prepare("select * from turni where id = :idturno ");
                    $stmtSelectT->bindParam(':idturno', $idturno);
                    $idturno = $inAnagrafica['idturno_import'];
                    $stmtSelectT->execute();
                    $rows = $stmtSelectT->fetchAll();
                    foreach ($rows as $row) {
                        $inizioturno = $row[inizio];
                    }
                }

                if ($found == false) {

                    $stmtSelectC = $conn->prepare("select * from anag_comune where descrizione = :comune ");
                    $stmtSelectC->bindParam(':comune', $comune);
                    $comune = $inAnagrafica['comune_nascita'];
                    $nazione = $inAnagrafica['stato_nascita'];

                    $stmtSelectC->execute();
                    $rows = $stmtSelectC->fetchAll();
                    foreach ($rows as $row) {
                        $comune_ques = $row['codice'];
                        $provincia_ques = $row['provincia'];
                    }
                    // echo 'COMUNE IN:'. $inAnagrafica['comune_nascita'] .' comune_ques: '.$comune_ques. ' ';

                    $stmtSelectN = $conn->prepare("select * from anag_nazione where descrizione = :nazione ");
                    $stmtSelectN->bindParam(':nazione', $nazione);

                    $stmtSelectN->execute();
                    $rows = $stmtSelectN->fetchAll();
                    foreach ($rows as $row) {
                        $nazione_ques = $row['codice'];
                    }
                    // echo 'NAZ IN:'. $inAnagrafica['stato_nascita'] .' Naz_ques: '.$nazione_ques. ' ';

                    $stmtInsert = $conn->prepare("INSERT INTO anag_base
                            (nome, cognome, data_nascita, sesso,
                            comune_nascita, provincia_nascita, stato_nascita, cittadinanza,
                            comune_n_ques, provincia_n_ques, stato_n_ques, cittadinanza_n_ques,
                            ruolo, gruppo, creato_da

                            )
                            VALUES (
                            :nome, :cognome, :data_nascita, :sesso,
                            :comune_nascita, :provincia_nascita, :stato_nascita, :cittadinanza,
                            :comune_n_ques, :provincia_n_ques, :stato_n_ques, :cittadinanza_n_ques,
                            :ruolo, :gruppo, :creato_da

                            )
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
                    $stmtInsert->bindParam(':ruolo', $ruolo);
                    $stmtInsert->bindParam(':gruppo', $gruppo);
                    $stmtInsert->bindParam(':creato_da', $creato_da);

                    $nome = $inAnagrafica[nome];
                    $cognome = $inAnagrafica[cognome];

                    $pieces = explode("T", $inAnagrafica[data_nascita]);
                    $data_nascita = $pieces[0];
                    $sesso = $inAnagrafica[sesso];

                    $comune_nascita = $inAnagrafica[comune_nascita];
                    $provincia_nascita = $inAnagrafica[provincia_nascita];
                    $stato_nascita = $inAnagrafica[stato_nascita];
                    $cittadinanza = $nazione_ques;
                    $comune_n_ques = $comune_ques;
                    $provincia_n_ques = $provincia_ques;
                    $stato_n_ques = $nazione_ques;
                    $cittadinanza_n_ques = $nazione_ques;
                    $ruolo = 'R';
                    $gruppo = $inAnagrafica[gruppo];
                    $creato_da = $inAnagrafica[creato_da];

                    /*
                    echo ' da creare anagrafica '.$cognome.' '.$nome.'<br>';
                    echo '               comune '.$comune.'<br>';
                    echo '              nazione '.$nazione.'<br>';
                    echo '              data_nascita '. $data_nascita.'<br>';
                    echo '              sesso '.$sesso.'<br>';
                    echo '              comune_nascita '.$comune_nascita.'<br>';
                    echo '              creato_da '.$creato_da.'<br>';
                    echo '              gruppo '.$gruppo.'<br>';
                    */

                    $stmtInsert->execute();
                    $idanagrafica = $conn->lastInsertId();

                }

                // inserisco un record in presenza turno se già non c'è
                $stmtSelectP = $conn->prepare("select * from presenze_turni where idturno = :idturno
                and idanag = :idanag ");

                $stmtSelectP->bindParam(':idturno', $idturno);
                $stmtSelectP->bindParam(':idanag', $idanag);

                $idturno = $inAnagrafica['idturno_import'];
                $idanag = $idanagrafica;

                $stmtSelectP->execute();
                $rows = $stmtSelectP->fetchAll();
                $found = false;
                foreach ($rows as $row) {
                $found = true;
                }

                if ($found === false) {

                $stmtInsertP = $conn->prepare("INSERT INTO presenze_turni
                (idturno, idanag, data, modificato_da )
                VALUES ( :idturno, :idanag, :inizio, :modificato_da )
                ");

                $stmtInsertP->bindParam(':idturno', $idturno);
                $stmtInsertP->bindParam(':idanag', $idanag);
                $stmtInsertP->bindParam(':inizio', $inizio);
                $stmtInsertP->bindParam(':modificato_da', $modificato_da);
                $idturno = $inAnagrafica['idturno_import'];
                $idanag = $idanagrafica;
                $inizio = $inizioturno;
                $modificato_da = $inAnagrafica[creato_da];

                $stmtInsertP->execute();
                }

            }

            $returnResult->result = 1;
            $returnResult->returnObject = $alldata;

            $conn = null;

        } catch(PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->result = 0;

            $conn = null;
        }

        return $returnResult;
    }


?>