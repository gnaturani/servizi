<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("../commonOperations.php");

require_once("slot.php");
require_once("stanza.php");
require_once("prenotazione.php");

function recuperaPrenotazioni($data)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from pren_date
                                 where id_user = $id_user
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':id_user', $id_user);
        $id_user = $data[id_user];

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Prenotazione();
            $act->id = $row['id'];
            $act->id_stanza = $row['id_stanza'];
            $act->id_user = $row['id_user'];
            $act->inizio = $row['inizio'];
            $act->fine = $row['fine'];
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


function recuperaPrenotazioniPerData($filters)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from pren_date
                                 where data = :input_data
                                    ";

        $returnResult->returnMessages[] = $sqlString;
        $stmt = $conn->prepare($sqlString);

        error_log(print_r($filters, true), 0);

        $stmt->bindParam(':input_data', $input_data);
        $input_data = explode("T", $filters['data'])[0];
        error_log(print_r($input_data, true), 0);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        
        foreach ($rows as $row) {
            $act = new Prenotazione();
            $act->id = $row['id'];
            $act->id_stanza = $row['id_stanza'];
            $act->id_user = $row['id_user'];
            $act->id_slot = $row['id_slot'];
            $act->data = $row['data'];
            $act->quante_persone = $row['quante_persone'];
            $alldata[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->returnObject = $alldata;


        error_log(print_r($returnResult->returnObject, true), 0);

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function recuperaSlots()
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from pren_slot";

        $returnResult->returnMessages[] = $sqlString;
        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Stanza();
            $act->id = $row['id'];
            $act->nome = $row['nome'];
            $act->inizio = $row['inizio'];
            $act->fine = $row['fine'];
            $act->attivo = $row['attivo'];
            $alldata[] = $act;
        }
        $returnResult->result = 1;
        $returnResult->success = true;
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


function recuperaStanze()
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from pren_stanze";

        $returnResult->returnMessages[] = $sqlString;
        $stmt = $conn->prepare($sqlString);

        $stmt->execute();
        $rows = $stmt->fetchAll();
        $alldata = array();
        // output data of each row
        foreach ($rows as $row) {

            $act = new Stanza();
            $act->id = $row['id'];
            $act->nome = $row['nome'];
            $act->capienza = $row['capienza'];
            $alldata[] = $act;
        }
        $returnResult->result = 1;
        $returnResult->success = true;
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


function recuperaStanzePerData($filters)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $returnResultStanze = recuperaStanze();
        $returnResultSlot = recuperaSlots();
        $returnResultPrenotazioni = recuperaPrenotazioniPerData($filters);

        $stanze = $returnResultStanze->returnObject;
        $slots = $returnResultSlot->returnObject;
        $prenotazioni = $returnResultPrenotazioni->returnObject;

        $alldata = array();

        // output data of each row
        foreach ($stanze as $stanza) {
            
            $act = new Stanza();
            $act->id = $stanza->id;
            $act->nome = $stanza->nome;
            $act->capienza = $stanza->capienza;
            $act->slots = [];

            foreach ($slots as $slot) {
                $actSlot = new Slot();
                $actSlot->id = $slot->id;
                $actSlot->nome = $slot->nome;
                $actSlot->inizio = $slot->inizio;
                $actSlot->fine = $slot->fine;
                $actSlot->id_stanza = $act->id;

                $actSlot->posti_occupati = 0;
                $actSlot->posti_liberi = $act->capienza;

                foreach ($prenotazioni as $prenotazione) {
                    if ($prenotazione->id_stanza == $act->id) {
                        if ($prenotazione->id_slot == $actSlot->id) {
                            $actSlot->posti_occupati = $actSlot->posti_occupati + $prenotazione->quante_persone;
                            $actSlot->posti_liberi = $actSlot->posti_liberi - $prenotazione->quante_persone;
                        }
                    }
                }

                $act->slots[] = $actSlot;
            }

            $alldata[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->success = true;
        $returnResult->returnObject = $alldata;

        error_log(print_r($returnResult, true), 0);
        
        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}