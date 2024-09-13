<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("../practice/practice.php");
    require_once("work.php");
    require_once('fpdf.php');

    function getWorks($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ( $filters[workid] != null && $filters[workid] != undefined ) {
                $sqlString = "SELECT * from pgs_works where teamid = '$filters[teamid]'
                                                        and id = '$filters[workid]'
                                order by wdate desc limit 100
                             ";
            } else {
                $sqlString = "SELECT * from pgs_works where teamid = '$filters[teamid]'
                                order by wdate desc limit 100
                             ";
            }

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Work();

                $act->id = $row['id'];
                $act->wdate = $row['wdate'];
                $act->teamid = $row['teamid'];
                $act->title = $row['title'];
                $act->created_by = $row['created_by'];
                $act->creation_date = $row['creation_date'];
                $act->update_date = $row['update_date'];

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

    function getWorkForDate($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from pgs_works where teamid = '$filters[teamid]'
                                                    and wdate = '$filters[date]'
                                order by wdate desc limit 1
                             ";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Work();

                $act->id = $row['id'];
                $act->wdate = $row['wdate'];
                $act->teamid = $row['teamid'];
                $act->title = $row['title'];
                $act->created_by = $row['created_by'];
                $act->creation_date = $row['creation_date'];
                $act->update_date = $row['update_date'];

                $returnResult->returnObject = $act;
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

    function getSingleWork($filters){

                try {
                    $returnResult = new ServiceResult();
                    $returnResult->returnMessages[] = "Start operation";

                    $conn = connectToDbPDO();

                    $gruppo = $user[gruppo];

                    // set the PDO error mode to exception
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $sqlString = "SELECT pgs_works.id,
                                           pgs_works.title,
                                           pgs_works.wdate,
                                           pgs_works.teamid,
                                           pgs_work_items.idpractice,
                                           pgs_practices.title as practice_title,
                                           pgs_practices.category as practice_category,
                                           pgs_practices.duration_m as practice_duration_m,
                                           pgs_practices.partial as practice_partial,
                                           pgs_practices.description as practice_description
                                         from pgs_works
                                            left outer join pgs_work_items
                                                on pgs_works.id = pgs_work_items.idwork
                                            left outer join pgs_practices
                                                on pgs_work_items.idpractice = pgs_practices.id
                                    where pgs_works.id = '$filters[id]'
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
                            $act->wdate = $row['wdate'];
                            $act->teamid = $row['teamid'];
                            $act->duration_m = 0;
                            $act->practices = array();
                            $first = false;
                        }

                        if ($row['idpractice'] != NULL){
                            $practice = new Practice();
                            $practice->id = $row['idpractice'];
                            $practice->title = $row['practice_title'];
                            $practice->description = $row['practice_description'];
                            $practice->category = $row['practice_category'];
                            $practice->duration_m = $row['practice_duration_m'];
                            $practice->partial = $row['practice_partial'];
                            $act->practices[] = $practice;

                            $act->duration_m = $act->duration_m + $practice->duration_m;
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

    function saveWork($in){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtInsert = $conn->prepare("INSERT INTO pgs_works
                            (teamid, wdate, title, created_by, creation_date
                            )
                            VALUES (:teamid, :wdate, :title, 'gnaturani', NOW()
                            )
                    ");

            $stmtUpdate = $conn->prepare("UPDATE pgs_works
                            set wdate = :wdate, teamid = :teamid, title = :title,
                            update_date = NOW()
                            where id = :id
                    ");

            $stmtInsert->bindParam(':teamid', $teamid);
            $stmtInsert->bindParam(':wdate', $wdate);
            $stmtInsert->bindParam(':title', $title);

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':teamid', $teamid);
            $stmtUpdate->bindParam(':wdate', $wdate);
            $stmtUpdate->bindParam(':title', $title);

            $id = $in[id];
            $teamid = $in[teamid];
            $wdate = $in[wdate];
            $title = $in[title];

            if ($in[id] != NULL){
                $stmtUpdate->execute();
            } else {
                $stmtInsert->execute();
            }

            $returnWork = new Work();
            if ($in[id] != NULL){
                $returnWork->id = $in[id];
            } else {
                $returnWork->id = $conn->lastInsertId();
            }
            $returnResult->returnObject = $returnWork;

            $stmtDelete = $conn->prepare("DELETE from pgs_work_items
                                WHERE idwork = :idwork
                                    ");
            $stmtDelete->bindParam(':idwork', $idwork);
            $idwork = $returnWork->id;
            $stmtDelete->execute();

            foreach($in[practices] as $item){

                $stmtInsert = $conn->prepare("INSERT INTO pgs_work_items
                        (idpractice, idwork
                        )
                        VALUES (:idpractice, :idwork
                        )
                    ");
                $stmtInsert->bindParam(':idpractice', $idpractice);
                $stmtInsert->bindParam(':idwork', $idwork);

                $idpractice = $item[id];
                $idwork = $returnWork->id;
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


    function deleteWork($in){

                $returnResult = new ServiceResult();
                $returnResult->result = 0;

                try {

                    $conn = connectToDbPDO();
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $stmtDelete = $conn->prepare("DELETE from pgs_works
                    WHERE id = :id
                                            ");
                    $stmtDelete->bindParam(':id', $id);
                    $id = $in[id];
                    $stmtDelete->execute();

                    $stmtDelete = $conn->prepare("DELETE from pgs_work_items
                    WHERE idwork = :idwork
                                            ");
                    $stmtDelete->bindParam(':idwork', $id);
                    $id = $in[id];
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