<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("models.php");


    function getRoomData($filters) {

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($filters[id] !== null && $filters[id] !== ""){
                $sqlString = "SELECT * from g_rooms
                                 where id = '$filters[id]'
                             ";
            } else {
                $sqlString = "SELECT * from g_rooms
                                 where tilename = '$filters[tilename]'
                             ";
            }

            $returnResult->returnMessages[] = "$sqlString";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Room();

                $act->id = $row['id'];
                $act->name = $row['name'];
                $act->description = $row['description'];
                $act->type = $row['type'];
                $act->tilename = $row['tilename'];
                $act->bypass_with_object_id = $row['bypass_with_object_id'];
                $act->obligatory = $row['obligatory'];

                if($act->obligatory == 0) $act->obligatory = false;
                if($act->obligatory == 1) $act->obligatory = true;

                $act->sequences = [];

                $sqlStringSeq = "SELECT * from g_sequences
                                 where idroom = '$act->id'
                                 order by position
                             ";
                $stmtSeq = $conn->prepare($sqlStringSeq);

                $stmtSeq->execute();
                $rowsSeq = $stmtSeq->fetchAll();
                // output data of each row
                foreach ($rowsSeq as $rowSeq) {

                    $actSeq = new Sequence();

                    $actSeq->id = $rowSeq['id'];
                    $actSeq->position = $rowSeq['position'];
                    $actSeq->description = $rowSeq['description'];
                    $actSeq->nextidsequence = $rowSeq['nextidsequence'];
                    $actSeq->question = $rowSeq['question'];
                    $actSeq->url_video = $rowSeq['url_video'];
                    $actSeq->url_image = $rowSeq['url_image'];
                    $actSeq->folder_video = $rowSeq['folder_video'];
                    $actSeq->filename_video = $rowSeq['filename_video'];
                    $actSeq->filename_image = $rowSeq['filename_image'];
                    $actSeq->receive_object_id = $rowSeq['receive_object_id'];
                    $actSeq->deliver_object_id = $rowSeq['deliver_object_id'];
                    $actSeq->special_action = $rowSeq['special_action'];
                    $actSeq->active = true;

                    $actSeq->step_block = false;
                    if ($rowSeq['step_block'] == 1) {
                        $actSeq->step_block = true;
                    } else {
                        $actSeq->step_block = false;
                    }

                    $actSeq->is_open_answer = false;
                    if ($rowSeq['is_open_answer'] == 1) {
                        $actSeq->is_open_answer = true;
                    } else {
                        $actSeq->is_open_answer = false;
                    }
                    
                    $act->sequences[] = $actSeq;
                }

                foreach ($act->sequences as $actSeq) {

                    $actSeq->nextpossequence = getSeequencePositionFromId($actSeq->nextidsequence, $act->sequences);

                    /*
                    // ricerca del video
                    $sqlStringVid = "SELECT * from g_video
                                        where idsequence = '$actSeq->id'
                                    ";
                    $stmtVid = $conn->prepare($sqlStringVid);
                    $stmtVid->execute();
                    $rowsVid = $stmtVid->fetchAll();
                    foreach ($rowsVid as $rowVid) {
                        $actVid = new Video();
                        $actVid->id = $rowVid['id'];
                        $actVid->description = $rowVid['description'];
                        $actVid->url = $rowVid['url'];
                        $actSeq->video = $actVid;
                    }
                    */

                    // ricerca delle risposte
                    $actSeq->answers = [];
                    $sqlStringAns = "SELECT * from g_answers
                                        where idsequence = '$actSeq->id'
                                    ";
                    $stmtAns = $conn->prepare($sqlStringAns);
                    $stmtAns->execute();
                    $rowsAns = $stmtAns->fetchAll();
                    foreach ($rowsAns as $rowAns) {
                        $actAns = new Answer();
                        $actAns->id = $rowAns['id'];
                        $actAns->text = $rowAns['text'];
                        $actAns->nextidsequence = $rowAns['nextidsequence'];
                        $actAns->nextpossequence = getSeequencePositionFromId($actAns->nextidsequence, $act->sequences);
                        $actAns->active = true;
                        $actSeq->answers[] = $actAns;
                    }

                    
                }
            }

            $returnResult->success = 1;
            $returnResult->returnObject = $act;

            $conn = null;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }


    function getObjectData($filters) {

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($filters[id] !== null && $filters[id] !== ""){
                $sqlString = "SELECT * from g_objects
                                 where id = '$filters[id]'
                             ";
            } else {
                $sqlString = "SELECT * from g_objects
                                 where name = '$filters[name]'
                             ";
            }

            $returnResult->returnMessages[] = "$sqlString";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new GameObject();

                $act->id = $row['id'];
                $act->name = $row['name'];
                $act->description = $row['description'];
                $act->url = $row['url'];
                    
            }

            $returnResult->success = 1;
            $returnResult->returnObject = $act;

            $conn = null;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }


    function getSeequencePositionFromId($id, $sequences){
        foreach ($sequences as $seq) {
            if ($seq->id == $id) {
                return $seq->position;
            }
        }
        return null;
    }

    function getIdSeequenceFromPosition($position, $sequences){
        foreach ($sequences as $seq) {
            if ($seq[position] == $position) {
                return $seq[id];
            }
        }
        return null;
    }

    function saveActRoom($in) {

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        // error_log(print_r('Room Data:', true), 0);
        // error_log(print_r($in, true), 0);
        // error_log(print_r($in['description'], true), 0);

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // salvataggio stanza
            $stmtInsert = $conn->prepare("INSERT INTO g_rooms 
                                (name, description, tilename, type, bypass_with_object_id, obligatory
                                ) 
                            VALUES (:name, :description, :tilename, :type, :bypass_with_object_id, :obligatory
                                )
                    ");

            $stmtUpdate = $conn->prepare("UPDATE g_rooms 
                            set name = :name,
                            description = :description,
                            tilename = :tilename,
                            type = :type,
                            bypass_with_object_id = :bypass_with_object_id,
                            obligatory = :obligatory
                            where id = :id
                    ");
                
            $stmtInsert->bindParam(':name', $name);
            $stmtInsert->bindParam(':tilename', $tilename);
            $stmtInsert->bindParam(':description', $description);
            $stmtInsert->bindParam(':type', $type);
            $stmtInsert->bindParam(':bypass_with_object_id', $bypass_with_object_id);
            $stmtInsert->bindParam(':obligatory', $obligatory);
            
            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':name', $name);
            $stmtUpdate->bindParam(':tilename', $tilename);
            $stmtUpdate->bindParam(':description', $description);
            $stmtUpdate->bindParam(':type', $type);
            $stmtUpdate->bindParam(':bypass_with_object_id', $bypass_with_object_id);
            $stmtUpdate->bindParam(':obligatory', $obligatory);

            $id = $in[id];
            $name = $in[name];
            $tilename = $in[tilename];
            $description = $in[description];
            $type = $in[type];

            if(checkIsNotNull($in[bypass_with_object_id])) {
                $bypass_with_object_id = $in[bypass_with_object_id];
            }
            $obligatory  = 0;
            if ($in[obligatory] == true) $obligatory = 1;
            if ($in[obligatory] == false) $obligatory = 0;

            if ($in[id] != NULL  && $in[id] != undefined && $in[id] != null){
                $stmtUpdate->execute();  
            } else {
                $stmtInsert->execute();  
            }

            if ($in[id] != NULL){
            } else {
                $in[id] = $conn->lastInsertId(); 
            }

            // salvataggio sequenze
            foreach ($in[sequences] as $actSeq) {

                $stmtInsert = $conn->prepare("INSERT INTO g_sequences
                        (position, nextidsequence, description, question, idroom, url_video,
                            filename_video, folder_video, receive_object_id, deliver_object_id, step_block, special_action,
                            url_image, filename_image, is_open_answer
                        ) 
                    VALUES (:position, :nextidsequence, :description, :question, :idroom, :url_video,
                            :filename_video, :folder_video, :receive_object_id, :deliver_object_id, :step_block, :special_action,
                            :url_image, :filename_image, :is_open_answer
                        )
                        ");

                $stmtUpdate = $conn->prepare("UPDATE g_sequences 
                                set position = :position,
                                nextidsequence = :nextidsequence,
                                description = :description,
                                question = :question,
                                url_video = :url_video,
                                filename_video = :filename_video,
                                url_image = :url_image,
                                filename_image = :filename_image,
                                folder_video = :folder_video,
                                idroom = :idroom,
                                receive_object_id = :receive_object_id,
                                deliver_object_id = :deliver_object_id,
                                step_block = :step_block,
                                special_action = :special_action,
                                is_open_answer = :is_open_answer
                                where id = :id
                        ");
                    
                $stmtInsert->bindParam(':position', $position);
                $stmtInsert->bindParam(':nextidsequence', $nextidsequence);
                $stmtInsert->bindParam(':description', $description);
                $stmtInsert->bindParam(':question', $question);
                $stmtInsert->bindParam(':idroom', $idroom);
                $stmtInsert->bindParam(':url_video', $url_video);
                $stmtInsert->bindParam(':filename_video', $filename_video);
                $stmtInsert->bindParam(':url_image', $url_image);
                $stmtInsert->bindParam(':filename_image', $filename_image);
                $stmtInsert->bindParam(':folder_video', $folder_video);
                $stmtInsert->bindParam(':receive_object_id', $receive_object_id);
                $stmtInsert->bindParam(':deliver_object_id', $deliver_object_id);
                $stmtInsert->bindParam(':step_block', $step_block);
                $stmtInsert->bindParam(':special_action', $special_action);
                $stmtInsert->bindParam(':is_open_answer', $is_open_answer);
                
                $stmtUpdate->bindParam(':id', $id);
                $stmtUpdate->bindParam(':position', $position);
                $stmtUpdate->bindParam(':nextidsequence', $nextidsequence);
                $stmtUpdate->bindParam(':description', $description);
                $stmtUpdate->bindParam(':question', $question);
                $stmtUpdate->bindParam(':idroom', $idroom);
                $stmtUpdate->bindParam(':url_video', $url_video);
                $stmtUpdate->bindParam(':filename_video', $filename_video);                
                $stmtUpdate->bindParam(':url_image', $url_image);
                $stmtUpdate->bindParam(':filename_image', $filename_image);
                $stmtUpdate->bindParam(':folder_video', $folder_video);
                $stmtUpdate->bindParam(':receive_object_id', $receive_object_id);
                $stmtUpdate->bindParam(':deliver_object_id', $deliver_object_id);
                $stmtUpdate->bindParam(':step_block', $step_block);
                $stmtUpdate->bindParam(':special_action', $special_action);
                $stmtUpdate->bindParam(':is_open_answer', $is_open_answer);

                $id = $actSeq[id];
                $position = $actSeq[position];
                if($actSeq[nextidsequence] != undefined && $actSeq[nextidsequence] != "" && $actSeq[nextidsequence] != " " ){
                    $nextidsequence = $actSeq[nextidsequence];
                } else {
                    $nextidsequence = NULL;
                }
                $description = $actSeq[description];
                $question = $actSeq[question];
                $url_video = $actSeq[url_video];
                $filename_video = $actSeq[filename_video];
                $url_image = $actSeq[url_image];
                $filename_image = $actSeq[filename_image];
                $url_video = $actSeq[url_video];
                $folder_video = $actSeq[folder_video];
                $step_block = $actSeq[step_block];
                $special_action = $actSeq[special_action];

                if ($actSeq[step_block] == 1 || $actSeq[step_block] == 'true') {
                    $step_block = 1;
                } else {
                    $step_block = 0;
                }

                if ($actSeq[is_open_answer] == 1 || $actSeq[is_open_answer] == 'true') {
                    $is_open_answer = 1;
                } else {
                    $is_open_answer = 0;
                }
                
                if (checkIsNotNull($actSeq[receive_object_id])){
                    $receive_object_id = $actSeq[receive_object_id];
                } else {
                    $receive_object_id = NULL;
                }
                if (checkIsNotNull($actSeq[deliver_object_id])){
                    $deliver_object_id = $actSeq[deliver_object_id];
                } else {
                    $deliver_object_id = NULL;
                }
                $idroom = $in[id];

                if ($actSeq[id] != NULL && $actSeq[id] != undefined && $actSeq[id] != null){
                    $stmtUpdate->execute();  
                } else {
                    $stmtInsert->execute();  
                }

                if ($actSeq[id] != NULL){
                } else {
                    $actSeq[id] = $conn->lastInsertId(); 
                }
            }


            // salvataggio domanda
            foreach ($in[sequences] as $actSeq) {

                // aggiorno l'eventuale nextidsequence, ora che ho tutte le seq salvate
                if ($actSeq[nextpossequence] !== "" ){
                }


                foreach ($actSeq[answers] as $actAns) {

                    if (!$actAns[active]) {

                        $stmtDelete = $conn->prepare("Delete from g_answers
                                        where id = :id
                                ");

                        $stmtDelete->bindParam(':id', $id);
                        $id = $actAns[id];

                        $stmtDelete->execute();  

                    } else {

                        $stmtInsert = $conn->prepare("INSERT INTO g_answers
                                (idsequence, nextidsequence, text
                                ) 
                            VALUES (:idsequence, :nextidsequence, :text
                                )
                                ");

                        $stmtUpdate = $conn->prepare("UPDATE g_answers 
                                        set idsequence = :idsequence,
                                        nextidsequence = :nextidsequence,
                                        text = :text
                                        where id = :id
                                ");
                            
                        $stmtInsert->bindParam(':idsequence', $idsequence);
                        $stmtInsert->bindParam(':nextidsequence', $nextidsequence);
                        $stmtInsert->bindParam(':text', $text);
                        
                        $stmtUpdate->bindParam(':id', $id);
                        $stmtUpdate->bindParam(':idsequence', $idsequence);
                        $stmtUpdate->bindParam(':nextidsequence', $nextidsequence);
                        $stmtUpdate->bindParam(':text', $text);

                        $id = $actAns[id];
                        $idsequence = $actSeq[id];
                        if($actAns[nextidsequence] !== undefined && $actAns[nextidsequence] !== "" && $actAns[nextidsequence] !== " " ){
                            $nextidsequence = $actAns[nextidsequence];
                        } else {
                            $nextidsequence = NULL;
                        }

                        $text = $actAns[text];

                        if ($actAns[id] != NULL && $actAns[id] != undefined && $actAns[id] != null){
                            $stmtUpdate->execute();  
                        } else {
                            $stmtInsert->execute();  
                        }

                        if ($actAns[id] != NULL){
                        } else {
                            $actAns[id] = $conn->lastInsertId(); 
                        }

                    }

                }
            }

            $returnResult->returnObject = $in;

            $returnResult->success = 1;        
            
            try {
                $stmtDelete = $conn->prepare("DELETE from g_answers where idsequence is null");
                $stmtDelete->execute();  
            } catch(Exception $Err) {}

            try {
                $stmtDelete = $conn->prepare("DELETE from g_rooms where name is null");
                $stmtDelete->execute();  
            } catch(Exception $Err) {}


            $conn = null;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            error_log(print_r('ERROR:', true), 0);
            error_log(print_r($e->getMessage(), true), 0);

            $conn = null;
        }

        return $returnResult;

    }

    function checkIsNotNull($value) {
        if($value != undefined && $value != "" && $value != " " && $value != null){
            return true;
        } else {
            return false;
        }
    }

    function getAllRooms($filters) {

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from g_rooms
                                        order by tilename
                             ";

            $returnResult->returnMessages[] = "$sqlString";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Room();

                $act->id = $row['id'];
                $act->description = $row['description'];
                $act->name = $row['name'];
                $act->type = $row['type'];
                $act->tilename = $row['tilename'];
                $act->bypass_with_object_id = $row['bypass_with_object_id'];
                $act->obligatory = $row['obligatory'];

                if($act->obligatory == 0) {
                    $act->obligatory = false;
                } 
                if($act->obligatory == 1) {
                    $act->obligatory = true;
                }

                $alldata[] = $act;
            }

            $returnResult->success = 1;
            $returnResult->returnObject = $alldata;

            $conn = null;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }


    function getPlayerRoomData($filters) {

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            error_log(print_r("filters:", true), 0);    
            error_log(print_r($filters, true), 0);

            $appoResult = getRoomData($filters);
            $idroom = $appoResult->returnObject->id;

            $returnResult->returnMessages[] = "idroom: ".$idroom;

            // error_log(print_r("appoResult:", true), 0);    
            // error_log(print_r($appoResult, true), 0);

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from g_playerActions
                                where idplayer = $filters[idplayer]
                                  and idroom = $idroom 
                                  and actiontype = 'S'
                                order by date desc
                             ";

            $returnResult->returnMessages[] = "$sqlString";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $lastidsequence = "";
            // output data of each row
            foreach ($rows as $row) {
                $lastidsequence = $row['idsequence'];
                break; 
            }

            error_log(print_r("lastidsequence:", true), 0);    
            error_log(print_r($lastidsequence, true), 0);

            $lastsequencenumber = "";
            $sequences = $appoResult->returnObject->sequences;
            foreach ($sequences as $sequence) {
                if ($sequence->id == $lastidsequence) {
                    $lastsequencenumber = $sequence->position;
                }
            }

            error_log(print_r("lastsequencenumber:", true), 0);    
            error_log(print_r($lastsequencenumber, true), 0);

            $sequencesOk = [];
            foreach ($appoResult->returnObject->sequences as $key => $value) {
                if ($value->position > $lastsequencenumber) {
                    $sequencesOk[] = $value;                    
                }
            }
            $appoResult->returnObject->sequences = $sequencesOk;

            $returnResult->success = 1;
            $returnResult->returnObject = $appoResult->returnObject;

            $conn = null;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }

    function getAllObjects($filters) {

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from g_objects
                                order by name
                             ";

            $returnResult->returnMessages[] = "$sqlString";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new GameObject();

                $act->id = $row['id'];
                $act->description = $row['description'];
                $act->name = $row['name'];
                $act->url = $row['url'];

                $alldata[] = $act;
            }

            $returnResult->success = 1;
            $returnResult->returnObject = $alldata;

            $conn = null;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }


    function saveObjectData($in) {

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        error_log(print_r("save Player data:", true), 0);    
        error_log(print_r($in, true), 0);

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // salvataggio stanza
            $stmtInsert = $conn->prepare("INSERT INTO g_objects
                                (name, description, url
                                ) 
                            VALUES (:name, :description, :url
                                )
                    ");

            $stmtUpdate = $conn->prepare("UPDATE g_objects 
                            set name = :name,
                            description = :description,
                            url = :url
                            where id = :id
                    ");
                
            $stmtInsert->bindParam(':name', $name);
            $stmtInsert->bindParam(':description', $description);
            $stmtInsert->bindParam(':url', $url);

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':name', $name);
            $stmtUpdate->bindParam(':description', $description);
            $stmtUpdate->bindParam(':url', $url);
            
            $id = $in[id];
            $name = $in[name];
            $description = $in[description];
            $url = $in[url];

            if ($in[id] != NULL && $in[id] != undefined && $in[id] != null){
                $stmtUpdate->execute();  
            } else {
                $stmtInsert->execute();  
            }

            if ($in[id] != NULL){
            } else {
                $in[id] = $conn->lastInsertId(); 
            }

            $returnResult->returnObject = $in;

            $returnResult->success = 1;    

            try {
                $stmtDelete = $conn->prepare("DELETE from g_objects where name is null");
                $stmtDelete->execute();  
            } catch(Exception $Err) {}
                    
            $conn = null;
        
        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }


    function setPlayerTime($in) {

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        error_log(print_r("setPlayerTime:", true), 0);    
        error_log(print_r($in, true), 0);

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtUpdate = $conn->prepare("UPDATE g_players 
                            set time = :time
                            where id = :id
                    ");
            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':time', $time);
            
            $id = $in[id];
            $time = $in[time];
            $stmtUpdate->execute();  
            $returnResult->returnObject = $in;

            $returnResult->success = 1;    

            try {
                $stmtDelete = $conn->prepare("DELETE from g_objects where name is null");
                $stmtDelete->execute();  
            } catch(Exception $Err) {}
                    
            $conn = null;
        
        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }


    function addObjectToPlayer($in) {

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        error_log(print_r("addObjectToPlayer:", true), 0);    
        error_log(print_r($in, true), 0);

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            error_log(print_r("30:", true), 0);    
            
            $stmtInsert = $conn->prepare("INSERT INTO g_playerObjects
                                (idplayer, idobject
                                ) 
                            VALUES (:idplayer, :idobject
                                )
                    ");

            $stmtInsert->bindParam(':idplayer', $idplayer);
            $stmtInsert->bindParam(':idobject', $idobject);

            error_log(print_r("40:", true), 0);    

            $idplayer = $in[player][id];
            $idobject = $in[gameObject][id];

            error_log(print_r("50:", true), 0);    

            $stmtInsert->execute();  

            error_log(print_r("60:", true), 0);    
            
            $returnResult->returnObject = $in;

            $returnResult->success = 1;    

            try {
                $stmtDelete = $conn->prepare("DELETE from g_playerObjects where idplayer is null");
                $stmtDelete->execute();  

            } catch(Exception $Err) {}

            error_log(print_r("70:", true), 0);    
                    
            $conn = null;
        
        } catch(Exception $e) {
            // header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        error_log(print_r($returnResult, true), 0);    

        return $returnResult;
    }


    function clearSuperPlayerAction($in) {

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        error_log(print_r("clearSuperPlayerAction:", true), 0);    
        error_log(print_r($in, true), 0);

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // salvataggio stanza
            $stmtInsert = $conn->prepare("DELETE from g_playerActions
                                    where idplayer = :idplayer
                                ");

            $stmtInsert->bindParam(':idplayer', $idplayer);

            $idplayer = $in[id];
            $stmtInsert->execute();
            $returnResult->success = 1;    

            $conn = null;
        
        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }

    function removeObjectFromPlayer($in) {

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        error_log(print_r("removeObjectFromPlayer:", true), 0);    
        error_log(print_r($in, true), 0);

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // salvataggio stanza
            $stmtInsert = $conn->prepare("DELETE from g_playerObjects
                                    where idplayer = :idplayer
                                      and idobject = :idobject
                                ");

            $stmtInsert->bindParam(':idplayer', $idplayer);
            $stmtInsert->bindParam(':idobject', $idobject);

            $idplayer = $in[player][id];
            $idobject = $in[gameObject][id];

            $stmtInsert->execute();
            $returnResult->success = 1;    

            try {
                $stmtDelete = $conn->prepare("DELETE from g_playerObjects where idplayer is null");
                $stmtDelete->execute();  

            } catch(Exception $Err) {}
                    
            $conn = null;
        
        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }

    function addPlayerAction($in) {

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        error_log(print_r("addPlayerAction:", true), 0);    
        error_log(print_r($in, true), 0);

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // salvataggio stanza
            $stmtInsert = $conn->prepare("INSERT INTO g_playerActions
                                (
                                 idplayer, description,
                                 idroom, roomname,
                                 idsequence, sequencename,
                                 idobjectreceived, objectreceivedname,
                                 idobjectdelivered, objectdeliveredname, actiontype
                                ) 
                            VALUES (
                                :idplayer, :description,
                                :idroom, :roomname,
                                :idsequence, :sequencename,
                                :idobjectreceived, :objectreceivedname,
                                :idobjectdelivered, :objectdeliveredname, :actiontype
                                )
                    ");

            $stmtInsert->bindParam(':idplayer', $idplayer);
            $stmtInsert->bindParam(':description', $description);
            $stmtInsert->bindParam(':idroom', $idroom);
            $stmtInsert->bindParam(':roomname', $roomname);
            $stmtInsert->bindParam(':idsequence', $idsequence);
            $stmtInsert->bindParam(':sequencename', $sequencename);
            $stmtInsert->bindParam(':idobjectreceived', $idobjectreceived);
            $stmtInsert->bindParam(':objectreceivedname', $objectreceivedname);
            $stmtInsert->bindParam(':idobjectdelivered', $idobjectdelivered);
            $stmtInsert->bindParam(':objectdeliveredname', $objectdeliveredname);
            $stmtInsert->bindParam(':actiontype', $actiontype);

            $idplayer = $in[idplayer];
            $description = $in[description];
            $roomname = $in[roomname];
            $idroom = $in[idroom];
            $idsequence = $in[idsequence];
            $sequencename = $in[sequencename];
            $idobjectreceived = $in[idobjectreceived];
            $idobjectdelivered = $in[idobjectdelivered];
            $objectdeliveredname = $in[objectdeliveredname];
            $objectreceivedname = $in[objectreceivedname];
            $actiontype = $in[actiontype];

            $stmtInsert->execute();  
            
            $returnResult->returnObject = $in;

            $returnResult->success = 1;    

            try {
                $stmtDelete = $conn->prepare("DELETE from g_playerActions where idplayer is null");
                $stmtDelete->execute();  

            } catch(Exception $Err) {}
                    
            $conn = null;
        
        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }


    function getVideoFolders($filters) {

        $baseFolder = "";
        $hostport = $_SERVER[HTTP_HOST];
        if ($hostport == "localhost:8888"){
            $baseFolder = "/Volumes/Transcend/Projects/Campeggio/game-online/src/assets/video/";
        } else {
            $baseFolder = "/web/htdocs/www.parrocchiacarpaneto.com/home/piazzolasgame/assets/video/";
        }

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";
            $returnResult->returnMessages[] = "BaseFolder: ";
            $returnResult->returnMessages[] = $baseFolder;

            $folders = [];
            $dirs = glob($baseFolder.'*', GLOB_ONLYDIR);

            // error_log(print_r("dirs:", true), 0);    
            // error_log(print_r($dirs, true), 0);

            $toRemove = array('..', '.');

            foreach ($dirs as $dir) {
                $actFolder = new Folder();
                $actFolder->path = str_replace("/Volumes/Transcend/Projects/Campeggio/game-online/src/", "", $dir);
                $actFolder->path = str_replace("/web/htdocs/www.parrocchiacarpaneto.com/home/piazzolasgame/", "", $actFolder->path);
                $actFolder->name = basename($dir);
                $actFolder->files = array_slice(scandir($dir), 2);
                $folders[] = $actFolder;
            }

            chdir($dirs);
            $returnResult->success = 1;
            $returnResult->returnObject = $folders;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;
        }

        return $returnResult;
    }

    function getObjectsUrls($filters) {

        $baseFolder = "";
        $hostport = $_SERVER[HTTP_HOST];
        if ($hostport == "localhost:8888"){
            $baseFolder = "/Volumes/Transcend/Projects/Campeggio/piazzolas/src/assets/objects/";
        } else {
            $baseFolder = "/web/htdocs/www.parrocchiacarpaneto.com/home/piazzolasgame/assets/objects/";
        }

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $files = array_slice(scandir($baseFolder), 2);
            $returnResult->success = 1;
            $returnResult->returnObject = $files;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;
        }

        return $returnResult;
    }


    function getPlayerData($filters) {

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            error_log(print_r( $filters, true), 0);

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from g_players
                                where username = '$filters[username]'
                             ";
            // error_log(print_r( $sqlString, true), 0);
            
            $returnResult->returnMessages[] = "$sqlString";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Player();

                $act->id = $row['id'];
                $act->username = $row['username'];
                $act->time = $row['time'];

                $position = new Position();
                $position->x = $row['positionX'];
                $position->y = $row['positionY'];

                $act->position = $position; 
                $act->gender = $row['gender'];

                $act->objects = [];
                $act->objectsHistory = [];  

                // aggiungo gli oggetti in possesso del player
                // $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                if ($act->username == "SUPERPLAYER") {
                    $sqlStringObj = "SELECT g_objects.id as id,
                                         g_objects.name as name,
                                         g_objects.description as description,
                                         g_objects.url as url                                         
                                        from g_objects
                                ";
                } else {
                    $sqlStringObj = "SELECT g_objects.id as id,
                                         g_objects.name as name,
                                         g_objects.description as description,
                                         g_objects.url as url                                         
                                        from g_playerObjects inner join g_objects
                                            on g_playerObjects.idobject = g_objects.id
                                    where idplayer = '$act->id'
                                ";
                }
                // error_log(print_r( $sqlStringObj, true), 0);
                
                $returnResult->returnMessages[] = "$sqlStringObj";

                $stmtObj = $conn->prepare($sqlStringObj);

                $stmtObj->execute();
                $rowsObj = $stmtObj->fetchAll();
                $alldata = array();
                // output data of each row
                foreach ($rowsObj as $rowObj) {

                    $actObj = new GameObject();
                    $actObj->id = $rowObj['id'];
                    $actObj->name = $rowObj['name'];
                    $actObj->description = $rowObj['description'];
                    $actObj->url = "assets/objects/".$rowObj['url'];

                    $act->objects[] = $actObj;
                }

                // aggiungo lo storico degli oggetti che ha avuto player
                $sqlStringObjHistory = "SELECT g_objects.id as id,
                                         g_objects.name as name,
                                         g_objects.description as description,
                                         g_objects.url as url                                         
                                        from g_playerActions inner join g_objects
                                            on g_playerActions.idobjectreceived = g_objects.id
                                    where g_playerActions.idplayer = '$act->id'
                                      and g_playerActions.actionType = 'R'
                                ";

                // error_log(print_r( $sqlStringObjHistory, true), 0);
                
                $returnResult->returnMessages[] = "$sqlStringObjHistory";

                $stmtObjHistory = $conn->prepare($sqlStringObjHistory);

                $stmtObjHistory->execute();
                $rowsObjHistory = $stmtObjHistory->fetchAll();
                // output data of each row
                foreach ($rowsObjHistory as $rowObjHistory) {

                    $actObjHistory = new GameObject();
                    $actObjHistory->id = $rowObjHistory['id'];
                    $actObjHistory->name = $rowObjHistory['name'];
                    $actObjHistory->description = $rowObjHistory['description'];
                    $act->objectsHistory[] = $actObjHistory;
                }

                $returnResult->success = 1;
            }

            $returnResult->returnObject = $act;

            $conn = null;

        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }

    function deletePlayerHistory($in) {
        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        error_log(print_r("save Player data:", true), 0);    
        error_log(print_r($in, true), 0);

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtDelete1 = $conn->prepare("delete from g_players 
                                    where username = :username
                    ");
                
            $stmtDelete1->bindParam(':username', $username);
            $username = $in[username];
            $stmtDelete1->execute();  

            $stmtDelete2 = $conn->prepare("delete from g_playerActions 
                                    where idplayer = :id
                    ");
                
            $stmtDelete2->bindParam(':id', $id);
            $id = $in[id];
            $stmtDelete2->execute();  

            $stmtDelete3 = $conn->prepare("delete from g_playerObjects 
                                    where idplayer = :id
                    ");
                
            $stmtDelete3->bindParam(':id', $id);
            $id = $in[id];
            $stmtDelete3->execute();  
            
            $returnResult->returnObject = $in;
            $returnResult->success = 1;       
            
            $conn = null;
        
        } catch(Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;

    }

    function savePlayerData($in) {

            $returnResult = new ServiceResult();
            $returnResult->result = 0;
    
            error_log(print_r("save Player data:", true), 0);    
            error_log(print_r($in, true), 0);
    
            try {
    
                $conn = connectToDbPDO();
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
                // salvataggio stanza
                $stmtInsert = $conn->prepare("INSERT INTO g_players 
                                    (username, gender, positionX, positionY, time
                                    ) 
                                VALUES (:username, :gender, :positionX, :positionY, :time
                                    )
                        ");
    
                $stmtUpdate = $conn->prepare("UPDATE g_players 
                                set username = :username,
                                gender = :gender,
                                positionX = :positionX,
                                positionY = :positionY,
                                time = :time,
                                where id = :id
                        ");
                    
                $stmtInsert->bindParam(':username', $username);
                $stmtInsert->bindParam(':gender', $gender);
                $stmtInsert->bindParam(':positionX', $positionX);
                $stmtInsert->bindParam(':positionY', $positionY);
                $stmtInsert->bindParam(':time', $time);
                
                $stmtUpdate->bindParam(':id', $id);
                $stmtUpdate->bindParam(':username', $username);
                $stmtUpdate->bindParam(':gender', $gender);
                $stmtUpdate->bindParam(':positionX', $positionX);
                $stmtUpdate->bindParam(':positionY', $positionY);
                $stmtUpdate->bindParam(':time', $time);
    
                $id = $in[id];
                $username = $in[username];
                $gender = $in[gender];
                $positionX = $in[position][x];
                $positionY = $in[position][y];
                $time = $in[time];
    
                if ($in[id] != NULL && $in[id] != null){
                    $stmtUpdate->execute();  
                } else {
                    $stmtInsert->execute();  
                }
    
                if ($in[id] != NULL){
                } else {
                    $in[id] = $conn->lastInsertId(); 
                }

                $returnResult->returnObject = $in;

                $returnResult->success = 1;       
                
                try {
                    $stmtDelete = $conn->prepare("DELETE from g_players where username is null");
                    $stmtDelete->execute();  
                } catch(Exception $Err) {}

                $conn = null;
            
            } catch(Exception $e) {
                header("HTTP/1.1 500 Internal Server Error");
                $returnResult->returnMessages[] = "Error: " . $e->getMessage();
                $returnResult->success = 0;
    
                $conn = null;
            }
    
            return $returnResult;
        }



        function savePlayerPosition($in) {

            $returnResult = new ServiceResult();
            $returnResult->result = 0;
    
            error_log(print_r("save Player position:", true), 0);    
            error_log(print_r($in, true), 0);


            // error_log(print_r($in[position]->x, true), 0);
    
            try {
    
                $conn = connectToDbPDO();
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
                $stmtUpdate = $conn->prepare("UPDATE g_players 
                                set 
                                positionX = :positionX,
                                positionY = :positionY
                                where id = :id
                        ");
                    
                $stmtUpdate->bindParam(':id', $id);
                $stmtUpdate->bindParam(':positionX', $positionX);
                $stmtUpdate->bindParam(':positionY', $positionY);
    
                $id = $in[id];
                $positionX = $in[position][x];
                $positionY = $in[position][y];
    
                $stmtUpdate->execute();  
                $returnResult->returnObject = $in;

                $returnResult->success = 1;            
                $conn = null;
            
            } catch(Exception $e) {
                header("HTTP/1.1 500 Internal Server Error");
                $returnResult->returnMessages[] = "Error: " . $e->getMessage();
                $returnResult->success = 0;
    
                $conn = null;
            }
    
            return $returnResult;
        }

?>