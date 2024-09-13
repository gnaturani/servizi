<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("report.php");

    function getReports($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            $gruppo = $user[gruppo];

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "select * from pgs_reports
                            where idteam = '$filters[idteam]'
                            order by id desc limit 10
                             ";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Report();

                $act->id = $row['id'];
                $act->idteam = $row['idteam'];
                $act->description = $row['description'];
                $act->created_by = $row['created_by'];
                $act->created_at = $row['created_at'];
                $act->players = json_decode($row['players']);
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


    function getReportsForMatch($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            $gruppo = $user[gruppo];

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "select * from pgs_reports
                            where idpartita = '$filters[idpartita]'
                            order by idpartita, idset  limit 10
                             ";

            $returnResult->returnMessages[] = $sqlString;

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Report();

                $act->id = $row['id'];
                $act->idteam = $row['idteam'];
                $act->idpartita = $row['idpartita'];
                $act->idset = $row['idset'];
                $act->description = $row['description'];
                $act->created_by = $row['created_by'];
                $act->created_at = $row['created_at'];
                $act->players = json_decode($row['players']);
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

    function saveReport($in){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtInsert = $conn->prepare("INSERT INTO pgs_reports
                                (idteam, description, created_by, created_at, players
                                )
                            VALUES (:idteam, :description, :created_by, NOW(), :players
                                )
                    ");

            $stmtUpdate = $conn->prepare("UPDATE pgs_reports
                            set
                            description = :description,
                            players = :players
                            where id = :id
                    ");

            $stmtInsert->bindParam(':idteam', $idteam);
            $stmtInsert->bindParam(':description', $description);
            $stmtInsert->bindParam(':created_by', $created_by);
            $stmtInsert->bindParam(':players', $players);

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':description', $description);
            $stmtUpdate->bindParam(':players', $players);

            $id = $in[id];
            $idteam = $in[idteam];
            $created_by = $in[created_by];
            $created_at = $in[created_at];
            $description = $in[description];
            $players = json_encode($in[players]);

            if ($in[id] != NULL){
                $stmtUpdate->execute();
            } else {
                $stmtInsert->execute();
            }

            $returnObj = new Report();

            if ($in[id] != NULL){
                $returnObj->id = $in[id];
            } else {
                $returnObj->id = $conn->lastInsertId();
            }
            $returnObj->created_at = date('Y-m-d H:i:s');
            $returnResult->returnObject = $returnObj;

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


    function saveMatchReport($in){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtDelete = $conn->prepare("DELETE from pgs_reports
                        where idpartita = $in[idpartita] and
                              idset = $in[set] ");
            $stmtDelete->execute();

            $stmtInsert = $conn->prepare("INSERT INTO pgs_reports
                                (idteam, description, created_by, created_at, players, idpartita, idset
                                )
                            VALUES (:idteam, :description, :created_by, NOW(), :players, :idpartita, :set
                                )
                    ");

            $stmtInsert->bindParam(':idteam', $idteam);
            $stmtInsert->bindParam(':description', $description);
            $stmtInsert->bindParam(':created_by', $created_by);
            $stmtInsert->bindParam(':players', $players);
            $stmtInsert->bindParam(':idpartita', $idpartita);
            $stmtInsert->bindParam(':set', $set);

            $idteam = $in[idteam];
            $created_by = $in[created_by];
            $created_at = $in[created_at];
            $description = $in[description];
            $idpartita = $in[idpartita];
            $set = $in[set];
            $players = json_encode($in[players]);

            $stmtInsert->execute();

            $returnObj = new Report();

            if ($in[id] != NULL){
                $returnObj->id = $in[id];
            } else {
                $returnObj->id = $conn->lastInsertId();
            }
            $returnObj->created_at = date('Y-m-d H:i:s');
            $returnResult->returnObject = $returnObj;

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