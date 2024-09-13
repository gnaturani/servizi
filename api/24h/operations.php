<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");

function addMailingList($data)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtInsert = $conn->prepare("INSERT into 24h_mails
                        (
                            mail
                        )
                        values
                        (
                            :mail
                        )
                ");

        $stmtInsert->bindParam(':mail', $mail);
        $mail = $data[email];
        $stmtInsert->execute();

        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Ok! Ti aggiorneremo appena avremo news!";
        
        $returnResult->result = 1;
        $returnResult->success = 1;
        $conn = null;

    } catch (PDOException $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}
