<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("register.php");

    function getSeasons() {
        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            $gruppo = $user[gruppo];

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT DISTINCT YEAR(date) as year FROM pgs_register
                             ";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Season();
                $act->start_year = $row['year'];
                $endYear = $act->start_year + 1;
                $act->title = $act->start_year.'/'.$endYear;
                $act->start_date = $act->start_year.'-'.'08-01';
                $act->end_date = $endYear.'-'.'07-30';
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

    function getPlayerSeasons($data) {

        $seasonsResult = getSeasons();

        $seasons = $seasonsResult->returnObject;

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $alldata = array();
            foreach ($seasons as $season) {

                // $idPlayer = $data[idplayer];
                $idPlayer = 3;

                $sqlString = "select count(at_work) as at_work from pgs_register
                                where idplayer = ".$idPlayer."
                                and date >= '".$season->start_date."'
                                and date <= '".$season->end_date."'
                             ";
                $returnResult->returnMessages[] = $sqlString;

                $stmt = $conn->prepare($sqlString);

                $stmt->execute();
                $rows = $stmt->fetchAll();
                // output data of each row
                foreach ($rows as $row) {
                    $season->count = $row[at_work];
                }
                $alldata[] = $season;

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

    function getRegisters($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from pgs_register
                                 where date = '$filters[date]'
                                   and idteam = '$filters[idteam]'
                             ";

            if ($filters[idplayer] !== '' && $filters[idplayer] !== NULL) {
                $sqlString = $sqlString . " and idplayer = '$filters[idplayer]'";
            }

            $returnResult->returnMessages[] = "$sqlString";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {

                $act = new Register();

                $act->id = $row['id'];
                $act->idteam = $row['idteam'];
                $act->idplayer = $row['idplayer'];
                $act->at_work = $row['at_work'];
                $act->convocation = $row['convocation'];
                $act->temp_ok = $row['temp_ok'];
                $act->temp = $row['temp'];
                $alldata[] = $act;

            }

            $returnResult->success = 1;
            $returnResult->returnObject = $alldata;

            $conn = null;

        } catch(PDOException $e) {
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;
            $conn = null;
        }

        return $returnResult;
    }

    function saveRegisters($registers){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach($registers as $register){

                $stmtInsert = $conn->prepare("INSERT INTO pgs_register
                                (idteam, idplayer, date, at_work
                                )
                                VALUES (:idteam, :idplayer, :date, :at_work
                                )
                                ON DUPLICATE KEY UPDATE
                                at_work = :at_work
                        ");

                $stmtInsert->bindParam(':idteam', $idteam);
                $stmtInsert->bindParam(':idplayer', $idplayer);
                $stmtInsert->bindParam(':date', $date);
                $stmtInsert->bindParam(':at_work', $at_work);

                $idteam = $register[idteam];
                $idplayer = $register[idplayer];
                $date = $register[date];
                $at_work = $register[at_work];

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


    function saveTemperature($registers){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach($registers as $register){

                $stmtInsert = $conn->prepare("INSERT INTO pgs_register
                                (idteam, idplayer, date, temp_ok, temp
                                )
                                VALUES (:idteam, :idplayer, :date, :temp_ok, :temp
                                )
                                ON DUPLICATE KEY UPDATE
                                temp_ok = :temp_ok,
                                temp = :temp
                        ");

                $stmtInsert->bindParam(':idteam', $idteam);
                $stmtInsert->bindParam(':idplayer', $idplayer);
                $stmtInsert->bindParam(':date', $date);
                $stmtInsert->bindParam(':temp_ok', $temp_ok);
                $stmtInsert->bindParam(':temp', $temp);

                $idteam = $register[idteam];
                $idplayer = $register[idplayer];
                $date = $register[date];
                $temp_ok = $register[temp_ok];
                $temp = $register[temp];

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


    function saveMatchRegisters($registers){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach($registers as $register){

                $stmtInsert = $conn->prepare("INSERT INTO pgs_register
                                (idteam, idplayer, date, at_work, idpartita
                                )
                                VALUES (:idteam, :idplayer, :date, :at_work, :idpartita
                                )
                                ON DUPLICATE KEY UPDATE
                                at_work = :at_work
                        ");

                $stmtInsert->bindParam(':idteam', $idteam);
                $stmtInsert->bindParam(':idplayer', $idplayer);
                $stmtInsert->bindParam(':date', $date);
                $stmtInsert->bindParam(':at_work', $at_work);
                $stmtInsert->bindParam(':idpartita', $idpartita);

                $idteam = $register[idteam];
                $idplayer = $register[idplayer];
                $date = $register[date];
                $at_work = $register[at_work];
                $idpartita = $register[idpartita];

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


    function saveMatchConvocations($registers){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach($registers as $register){

                $stmtInsert = $conn->prepare("INSERT INTO pgs_register
                                (idteam, idplayer, date, convocation
                                )
                                VALUES (:idteam, :idplayer, :date, :convocation                                )
                                ON DUPLICATE KEY UPDATE
                                convocation = :convocation
                        ");

                $stmtInsert->bindParam(':idteam', $idteam);
                $stmtInsert->bindParam(':idplayer', $idplayer);
                $stmtInsert->bindParam(':date', $date);
                $stmtInsert->bindParam(':convocation', $convocation);

                $idteam = $register[idteam];
                $idplayer = $register[idplayer];
                $date = $register[date];
                $convocation = $register[convocation];

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


?>