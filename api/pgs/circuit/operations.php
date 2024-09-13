<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("circuit.php");
    
    function getCircuits($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            $gruppo = $user[gruppo];

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from pgs_circuit_items
                             ";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Circuit();

                $act->id = $row['id'];
                $act->description = $row['description'];
                $act->body = $row['body'];
                $act->equipment = $row['equipment'];
                $act->url_image = $row['url_image'];
                $alldata[] = $act;
            }

            $returnResult->success = 1;
            $returnResult->returnObject = $alldata;

            $conn = null;

        } catch(PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }

    function getSinglePractice($filters){
        
                try {
                    $returnResult = new ServiceResult();
                    $returnResult->returnMessages[] = "Start operation";
        
                    $conn = connectToDbPDO();
        
                    $gruppo = $user[gruppo];
        
                    // set the PDO error mode to exception
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
                    $sqlString = "SELECT pgs_practices.id,
                                           pgs_practices.title,
                                           pgs_practices.description,
                                           pgs_practices.category,
                                           pgs_practice_items.data_json
                                         from pgs_practices
                                        left outer join pgs_practice_items
                                        on pgs_practices.id = pgs_practice_items.idpractice
                                    where pgs_practices.id = '$filters[id]'
                                     ";
        
                    $stmt = $conn->prepare($sqlString);

                    $act = new Practice();
        
                    $stmt->execute();
                    $rows = $stmt->fetchAll();
                    $alldata = array();

                    $first = true;
                    // output data of each row
                    foreach ($rows as $row) {
        
                        if ($first){
                            $act->id = $row['id'];
                            $act->title = $row['title'];
                            $act->description = $row['description'];
                            $act->category = $row['category'];
                            $act->created_by = $row['created_by'];
                            $act->creation_date = $row['creation_date'];
                            $act->update_date = $row['update_date'];   
                            $act->fieldItems = array();
                            $first = false;                                
                        }

                        if ($row['data_json'] != NULL){
                            $act->fieldItems[] = json_decode($row['data_json']);
                        }

                    }
        
                    $returnResult->success = 1;
                    $returnResult->returnObject = $act;
        
                    $conn = null;
        
                } catch(PDOException $e) {
                    header("HTTP/1.1 500 Internal Server Error");
                    $returnResult->returnMessages[] = "Error: " . $e->getMessage();
                    $returnResult->success = 0;
        
                    $conn = null;
                }
        
                return $returnResult;
            }

    function saveCircuit($in){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtInsert = $conn->prepare("INSERT INTO pgs_circuit_items 
                                (body, description, equipment, url_image
                                ) 
                            VALUES (:body, :description, :equipment, :url_image
                                )
                    ");

            $stmtUpdate = $conn->prepare("UPDATE pgs_circuit_items 
                            set body = :body,
                            description = :description,
                            equipment = :equipment,
                            url_image = :url_image
                            where id = :id
                    ");
                
            $stmtInsert->bindParam(':body', $body);
            $stmtInsert->bindParam(':equipment', $equipment);
            $stmtInsert->bindParam(':description', $description);
            $stmtInsert->bindParam(':url_image', $url_image);
            
            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':body', $body);
            $stmtUpdate->bindParam(':equipment', $equipment);
            $stmtUpdate->bindParam(':description', $description);
            $stmtUpdate->bindParam(':url_image', $url_image);

            $id = $in[id];
            $equipment = $in[equipment];
            $body = $in[body];
            $description = $in[description];
            $url_image = $in[url_image];

            if ($in[id] != NULL){
                $stmtUpdate->execute();  
            } else {
                $stmtInsert->execute();  
            }

            $returnCircuit = new Circuit();
            if ($in[id] != NULL){
                $returnCircuit->id = $in[id]; 
            } else {
                $returnCircuit->id = $conn->lastInsertId(); 
            }
            $returnResult->returnObject = $returnCircuit;

            $returnResult->success = 1;            
            $conn = null;

        } catch(PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }

?>