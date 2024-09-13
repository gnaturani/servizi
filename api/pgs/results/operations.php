<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("result.php");

    function getResults($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            $gruppo = $user[gruppo];

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "select * from pgs_results
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


    function getResultForMatch($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            $gruppo = $user[gruppo];

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "select * from pgs_results
                            where idpartita = '$filters[idpartita]'
                              and idteam = '$filters[idteam]'
                            order by idpartita
                             ";

            $returnResult->returnMessages[] = $sqlString;

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Result();
                $act->id = $row['id'];
                $act->idteam = $row['idteam'];
                $act->idpartita = $row['idpartita'];
                $act->results = $row['results'];
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


    function saveMatchResult($in){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtDelete = $conn->prepare("DELETE from pgs_results
                        where idpartita = $in[idpartita] and
                              idteam = '$in[idteam]' ");
            $stmtDelete->execute();

            $stmtInsert = $conn->prepare("INSERT INTO pgs_results
                                (idteam, idpartita, results
                                )
                            VALUES (:idteam, :idpartita, :results
                                )
                    ");

            $stmtInsert->bindParam(':idteam', $idteam);
            $stmtInsert->bindParam(':idpartita', $idpartita);
            $stmtInsert->bindParam(':results', $results);

            $idteam = $in[idteam];
            $idpartita = $in[idpartita];
            $results = $in[results];
            // $results = json_encode($in[results]);

            $stmtInsert->execute();

            $returnObj = new Result();

            if ($in[id] != NULL){
                $returnObj->id = $in[id];
            } else {
                $returnObj->id = $conn->lastInsertId();
            }
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