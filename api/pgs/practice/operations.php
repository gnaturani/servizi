<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("../circuit/circuit.php");
    require_once("practice.php");

    function getPractices($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            $gruppo = $user[gruppo];

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from pgs_practices
                             ";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Practice();

                $act->id = $row['id'];
                $act->title = $row['title'];
                $act->description = $row['description'];
                $act->category = $row['category'];
                $act->created_by = $row['created_by'];
                $act->creation_date = $row['creation_date'];
                $act->update_date = $row['update_date'];
                $act->duration_m = $row['duration_m'];
                $act->partial = $row['partial'];

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
                                           pgs_practices.duration_m,
                                           pgs_practices.partial,
                                           pgs_practice_items.data_json,
                                           pgs_circuit_items.id as step_id,
                                           pgs_circuit_items.body as step_body,
                                           pgs_circuit_items.description as step_description,
                                           pgs_circuit_items.equipment as step_equipment,
                                           pgs_circuit_items.url_image as step_url_image
                                         from pgs_practices
                                            left outer join pgs_practice_items
                                                on pgs_practices.id = pgs_practice_items.idpractice
                                            left outer join pgs_practice_steps
                                                on pgs_practices.id = pgs_practice_steps.idpractice
                                            left outer join pgs_circuit_items
                                                on pgs_practice_steps.idstep = pgs_circuit_items.id

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
                            $act->duration_m = $row['duration_m'];
                            $act->partial = $row['partial'];
                            $act->fieldItems = array();
                            $first = false;
                        }

                        if ($row['data_json'] != NULL){
                            $act->fieldItems[] = json_decode($row['data_json']);
                        }

                        if ($row['step_id'] != NULL){
                            $step = new Circuit();
                            $step->id = $row['step_id'];
                            $step->body = $row['step_body'];
                            $step->description = $row['step_description'];
                            $step->equipment = $row['step_equipment'];
                            $step->url_image = $row['step_iurl_image'];
                            $act->circuitItems[] = $step;
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

    function savePractice($inPractice){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtInsert = $conn->prepare("INSERT INTO pgs_practices
                            (title, category, description, created_by, creation_date,
                            duration_m, partial
                            )
                            VALUES (:title, :category, :description, :created_by, NOW(),
                            :duration_m, :partial
                            )
                    ");

            $stmtUpdate = $conn->prepare("UPDATE pgs_practices
                            set title = :title,
                            category = :category,
                            description = :description,
                            duration_m = :duration_m,
                            partial = :partial,
                            update_date = NOW()
                            where id = :id
                    ");

            $stmtInsert->bindParam(':title', $title);
            $stmtInsert->bindParam(':category', $category);
            $stmtInsert->bindParam(':description', $description);
            $stmtInsert->bindParam(':created_by', $username);
            $stmtInsert->bindParam(':duration_m', $duration_m);
            $stmtInsert->bindParam(':partial', $partial);

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':title', $title);
            $stmtUpdate->bindParam(':category', $category);
            $stmtUpdate->bindParam(':description', $description);
            $stmtUpdate->bindParam(':duration_m', $duration_m);
            $stmtUpdate->bindParam(':partial', $partial);

            $id = $inPractice[id];
            $title = $inPractice[title];
            $category = $inPractice[category];
            //$description = mysql_real_escape_string($inPractice[description]);
            $description = $inPractice[description];
            $username = $inPractice[username];
            $duration_m = $inPractice[duration_m];
            $partial = $inPractice[partial];

            if ($inPractice[id] != NULL){
                $stmtUpdate->execute();
            } else {
                $stmtInsert->execute();
            }

            $returnPractise = new Practice();
            if ($inPractice[id] != NULL){
                $returnPractise->id = $inPractice[id];
            } else {
                $returnPractise->id = $conn->lastInsertId();
            }
            $returnResult->returnObject = $returnPractise;

            $stmtDelete = $conn->prepare("DELETE from pgs_practice_items
                                WHERE idpractice = :idpractice
                                    ");
            $stmtDelete->bindParam(':idpractice', $idpractice);
            $idpractice = $returnPractise->id;
            $stmtDelete->execute();

            $stmtDelete = $conn->prepare("DELETE from pgs_practice_steps
                        WHERE idpractice = :idpractice
                            ");
            $stmtDelete->bindParam(':idpractice', $idpractice);
            $idpractice = $returnPractise->id;
            $stmtDelete->execute();

            foreach($inPractice[fieldItems] as $item){

                $stmtInsert = $conn->prepare("INSERT INTO pgs_practice_items
                        (idpractice, data_json
                        )
                        VALUES (:idpractice, :data_json
                        )
                    ");
                $stmtInsert->bindParam(':idpractice', $idpractice);
                $stmtInsert->bindParam(':data_json', $data_json);

                $idpractice = $returnPractise->id;
                $data_json = json_encode($item);
                $stmtInsert->execute();

            }

            foreach($inPractice[circuitItems] as $item){

                $stmtInsert = $conn->prepare("INSERT INTO pgs_practice_steps
                        (idpractice, idstep
                        )
                        VALUES (:idpractice, :idstep
                        )
                    ");
                $stmtInsert->bindParam(':idpractice', $idpractice);
                $stmtInsert->bindParam(':idstep', $idstep);

                $idpractice = $returnPractise->id;
                $idstep = $item[id];
                $stmtInsert->execute();
            }

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


    function deletePractice($inPractice){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtDelete = $conn->prepare("DELETE from pgs_practices
                        WHERE id = :idpractice
                            ");
            $stmtDelete->bindParam(':idpractice', $idpractice);
            $idpractice = $inPractice[id];
            $stmtDelete->execute();

            $stmtDelete = $conn->prepare("DELETE from pgs_practice_items
                                WHERE idpractice = :idpractice
                                    ");
            $stmtDelete->bindParam(':idpractice', $idpractice);
            $idpractice = $inPractice[id];
            $stmtDelete->execute();

            $stmtDelete = $conn->prepare("DELETE from pgs_practice_steps
                        WHERE idpractice = :idpractice
                            ");
            $stmtDelete->bindParam(':idpractice', $idpractice);
            $idpractice = $inPractice[id];
            $stmtDelete->execute();

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