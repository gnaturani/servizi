<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("../practice/practice.php");
    require_once("team.php");
    require_once("player.php");

    function getTeams($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from pgs_teams ";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $alldata = array();
            // output data of each row
            foreach ($rows as $row) {
                $act = new Team();
                $act->id = $row['id'];
                $act->name = $row['name'];
                $act->calendarId = $row['calendarid'];
                $act->cid = $row['cid'];
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

    function getSingleTeam($filters){

                try {
                    $returnResult = new ServiceResult();
                    $returnResult->returnMessages[] = "Start operation";

                    $conn = connectToDbPDO();

                    $gruppo = $user[gruppo];

                    // set the PDO error mode to exception
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $sqlString = "SELECT pgs_teams.id as team_id,
                                            pgs_teams.name as team_name,
                                           pgs_teams.cid as team_cid,
                                           pgs_players.id as player_id,
                                           pgs_players.name,
                                           pgs_players.surname,
                                           pgs_players.nickname,
                                           pgs_players.tnumber,
                                           pgs_players.username,
                                           pgs_players.role,
                                           pgs_players.role2,
                                           pgs_players.role3
                                         from pgs_teams
                                            left outer join pgs_players
                                                on pgs_teams.id = pgs_players.idteam
                                    where pgs_teams.id = '$filters[id]'
                                      order by pgs_players.surname, pgs_players.name
                                     ";

                    $stmt = $conn->prepare($sqlString);

                    $act = new Team();

                    $stmt->execute();
                    $rows = $stmt->fetchAll();
                    $alldata = array();

                    $first = true;
                    // output data of each row
                    foreach ($rows as $row) {

                        if ($first){
                            $act->id = $row['team_id'];
                            $act->cid = $row['team_cid'];
                            $act->name = $row['team_name'];
                            $act->players = array();
                            $first = false;
                        }

                        if ($row['player_id'] != NULL){
                            $player = new Player();
                            $player->id = $row['player_id'];
                            $player->name = $row['name'];
                            $player->surname = $row['surname'];
                            $player->username = $row['username'];
                            $player->nickname = $row['nickname'];
                            $player->tnumber = $row['tnumber'];
                            $player->role = $row['role'];
                            $player->role2 = $row['role2'];
                            $player->role3 = $row['role3'];
                            $act->players[] = $player;
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

    function saveTeam($in){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtInsert = $conn->prepare("INSERT INTO pgs_teams
                            (teamid, wdate, title, created_by, creation_date
                            )
                            VALUES (:teamid, :wdate, :title, 'gnaturani', NOW()
                            )
                    ");

            $stmtUpdate = $conn->prepare("UPDATE pgs_teams
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


    function savePlayer($in){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtInsert = $conn->prepare("INSERT INTO pgs_players
                            (idteam, name, surname, nickname, tnumber, role, role2, role3, username
                            )
                            VALUES (:idteam, :name, :surname, :nickname, :tnumber, :role, :role2, :role3, :username
                            )
                    ");

            $stmtUpdate = $conn->prepare("UPDATE pgs_players
                            set idteam = :idteam, name = :name, surname = :surname,
                            nickname = :nickname, tnumber = :tnumber, role = :role, role2 = :role2,
                            role3 = :role3,
                            username = :username
                            where id = :id
                    ");

            $stmtInsert->bindParam(':idteam', $idteam);
            $stmtInsert->bindParam(':name', $name);
            $stmtInsert->bindParam(':surname', $surname);
            $stmtInsert->bindParam(':nickname', $nickname);
            $stmtInsert->bindParam(':tnumber', $tnumber);
            $stmtInsert->bindParam(':role', $role);
            $stmtInsert->bindParam(':role2', $role2);
            $stmtInsert->bindParam(':role3', $role3);
            $stmtInsert->bindParam(':username', $username);

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':idteam', $idteam);
            $stmtUpdate->bindParam(':name', $name);
            $stmtUpdate->bindParam(':surname', $surname);
            $stmtUpdate->bindParam(':nickname', $nickname);
            $stmtUpdate->bindParam(':tnumber', $tnumber);
            $stmtUpdate->bindParam(':role', $role);
            $stmtUpdate->bindParam(':role2', $role2);
            $stmtUpdate->bindParam(':role3', $role3);
            $stmtUpdate->bindParam(':username', $username);

            $id = $in[id];
            $idteam = $in[idteam];
            $name = $in[name];
            $surname = $in[surname];
            $nickname = $in[nickname];
            $tnumber = $in[tnumber];
            $role = $in[role];
            $role2 = $in[role2];
            $role3 = $in[role3];
            $username = $in[username];

            if ($in[id] != NULL){
                $stmtUpdate->execute();
            } else {
                $stmtInsert->execute();
            }

            $returnWork = new Player();
            if ($in[id] != NULL){
                $returnWork->id = $in[id];
            } else {
                $returnWork->id = $conn->lastInsertId();
            }
            $returnResult->returnObject = $returnWork;
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

    function deletePlayer($in){

        $returnResult = new ServiceResult();
        $returnResult->result = 0;

        try {

            $conn = connectToDbPDO();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmtDelete = $conn->prepare("DELETE from pgs_players
                                WHERE id = :id
                                    ");
            $stmtDelete->bindParam(':id', $id);
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