<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");


class church{
    var $id_church;
    var $title;
    var $data = array();        
}

class dataChurch{
    var $id_church;
    var $id;
    var $title;
    var $description;        
}

function recuperaEventiSpeciali()
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from site_special_events ";
        $stmt = $conn->prepare($sqlString);

        // echo $sqlString;

        $stmt->execute();
        $rows = $stmt->fetchAll();

        $all = [];

        // output data of each row
        foreach ($rows as $row) {

            $act = new stdClass();
            $act->id = $row['id'];
            $act->title = $row['title'];
            $act->text = $row['text'];
            $act->image_url = $row['image_url'];
            $act->image_base64 = $row['image_base64'];
            $act->display = $row['display'];

            $all[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnObject = $all;

        $conn = null;
    } catch (PDOException $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}



function salvaEventoSpeciale($data)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if($data[id] == NULL) {
            $stmtInsert = $conn->prepare("INSERT into site_special_events
                        (
                            title, text, image_url, image_base64, display
                        )
                        values
                        (
                            :title, :text, :image_url, :image_base64, :display
                        )
                ");
            $stmtInsert->bindParam(':title', $title);
            $stmtInsert->bindParam(':text', $text);
            $stmtInsert->bindParam(':image_url', $image_url);
            $stmtInsert->bindParam(':image_base64', $image_base64);
            $stmtInsert->bindParam(':display', $display);

            $title = $data[title];
            $text = $data[text];
            $image_url = $data[image_url];
            $image_base64 = $data[image_base64];
            $display = $app[display];

            $stmtInsert->execute();
        } else {

            $stmtUpdate = $conn->prepare("UPDATE site_special_events 
                        set title = :title,
                        text = :text,
                        image_url = :image_url,
                        image_base64 = :image_base64,
                        display = :display
                        where id = :id
                ");

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':image_url', $image_url);
            $stmtUpdate->bindParam(':text', $text);
            $stmtUpdate->bindParam(':title', $title);
            $stmtUpdate->bindParam(':image_base64', $image_base64);
            $stmtUpdate->bindParam(':display', $display);
            
            $id = $data[id];
            $title = $data[title];
            $text = $data[text];
            $image_url = $data[image_url];
            $image_base64 = $data[image_base64];
            $display = $data[display];

            $stmtUpdate->execute();
        }

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


function getSitePages($data)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from site_pages ";
        
        $returnResult->returnMessages[] = "$data";
        $page_id = $data["page_id"];
        $returnResult->returnMessages[] = "pageid: <<$page_id>>";

        if ($page_id !== null){
            $sqlString = $sqlString . " where page_id='$page_id'";
        }

        $stmt = $conn->prepare($sqlString);

        // echo $sqlString;

        $stmt->execute();
        $rows = $stmt->fetchAll();

        $all = [];

        // output data of each row
        foreach ($rows as $row) {

            $act = new stdClass();
            $act->id = $row['id'];
            $act->title = $row['title'];
            $act->text = $row['text'];
            $act->text_mobile = $row['text_mobile'];
            $act->image_url = $row['image_url'];
            $act->image_base64 = $row['image_base64'];
            $act->page_id = $row['page_id'];

            $all[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnObject = $all;

        $conn = null;
    } catch (PDOException $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}



function saveSitePage($data)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if($data[id] == NULL) {
            $stmtInsert = $conn->prepare("INSERT into site_pages
                        (
                            title, text, text_mobile, image_url, image_base64, page_id
                        )
                        values
                        (
                            :title, :text, :text_mobile, :image_url, :image_base64, :page_id
                        )
                ");
            $stmtInsert->bindParam(':title', $title);
            $stmtInsert->bindParam(':text', $text);
            $stmtInsert->bindParam(':text_mobile', $text_mobile);
            $stmtInsert->bindParam(':image_url', $image_url);
            $stmtInsert->bindParam(':image_base64', $image_base64);
            $stmtInsert->bindParam(':display', $display);
            $stmtInsert->bindParam(':mobile', $mobile);
            $stmtInsert->bindParam(':desktop', $desktop);
            $stmtInsert->bindParam(':page_id', $page_id);

            $title = $data[title];
            $text = $data[text];
            $text_mobile = $data[text_mobile];
            $image_url = $data[image_url];
            $image_base64 = $data[image_base64];
            $page_id = $app[page_id];

            $stmtInsert->execute();
        } else {

            $stmtUpdate = $conn->prepare("UPDATE site_pages 
                        set title = :title,
                        text = :text,
                        text_mobile = :text_mobile,
                        image_url = :image_url,
                        image_base64 = :image_base64,
                        page_id = :page_id
                        where id = :id
                ");

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':image_url', $image_url);
            $stmtUpdate->bindParam(':text', $text);
            $stmtUpdate->bindParam(':text_mobile', $text_mobile);
            $stmtUpdate->bindParam(':title', $title);
            $stmtUpdate->bindParam(':image_base64', $image_base64);
            $stmtUpdate->bindParam(':page_id', $page_id);
            
            $id = $data[id];
            $title = $data[title];
            $text = $data[text];
            $text_mobile = $data[text_mobile];
            $image_url = $data[image_url];
            $image_base64 = $data[image_base64];
            $page_id = $data[page_id];

            $stmtUpdate->execute();
        }

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


function getChurchesData(){


    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $allDatas = array();
        $allChurch = array();
                
        $sql = "SELECT * FROM church_data";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $singleData = new dataChurch();
            $singleData->id = $row["id"];
            $singleData->id_church = $row["id_church"];
            $singleData->title = $row['title'];
            
            /*
            $singleData->description = htmlentities($row['description'],ENT_QUOTES,'ISO-8859-1' );
            $singleData->title = htmlentities($singleData->title,ENT_QUOTES,'ISO-8859-1' );
            $singleData->description = utf8_encode($singleData->description);
            $singleData->title = utf8_encode($singleData->title);
            */
            $singleData->description = $row['description'];
            $singleData->title = $row['title'];
            
            $allDatas[] = $singleData;
        }

        $sql2 = "SELECT * FROM church_title";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute();
        $rows2 = $stmt2->fetchAll();

        foreach ($rows2 as $row2) {
            $singleChurch = new church();
            $singleChurch->id_church = $row2["id_church"];
            $singleChurch->title = $row2["title"];
            
            for ($i = 0; $i < count($allDatas); $i++){
                $singleData = $allDatas[$i];
                
                if ($singleData->id_church == $singleChurch->id_church){
                    $singleChurch->data[] = $singleData;
                }                
            }
            $allChurch[] = $singleChurch;
        }
        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnObject = $allChurch;

        $conn = null;
    } catch (PDOException $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
    
}

function getCateData($data){
    return recuperaIncontriSettimanali("CATECHISMO");
}

function getGruppiGiovData($data){
    return recuperaIncontriSettimanali("GRUPPI_GIOVANILI");
}

function recuperaIncontriSettimanali($tipo)
{

    try {
        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from incontri_settimanali where tipo ='$tipo'";
        $stmt = $conn->prepare($sqlString);

        // echo $sqlString;

        $stmt->execute();
        $rows = $stmt->fetchAll();

        $all = [];

        // output data of each row
        foreach ($rows as $row) {

            $act = new stdClass();
            $act->nome_gruppo = $row['nome_gruppo'];
            $act->giorno = $row['giorno'];
            $act->orario = $row['orario'];
            $act->dove = $row['dove'];
            $act->animatori = $row['animatori'];
            $act->tipo = $row['tipo'];

            $all[] = $act;
        }

        $returnResult->result = 1;
        $returnResult->success = 1;
        $returnResult->returnObject = $all;

        $conn = null;
    } catch (PDOException $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}
