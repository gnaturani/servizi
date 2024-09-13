<?php
    require_once("../dbconnect.php");
    require_once("../resultCalling.php");
    require_once("user.php");

    function login($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            error_log ("Start LOGIN Api", 0);

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from pgs_users
                            where username = '$filters[username]'
                              and password = '$filters[password]'
                                ";


            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $loginSuccess = false;

            $act = new User();
            // output data of each row
            foreach ($rows as $row) {
                $act->id = $row['id'];
                $act->name = $row['name'];
                $act->surname = $row['surname'];
                $act->username = $row['username'];
                $act->onlyplayer = $row['onlyplayer'];

                $act->teamsAuthorizations = array();

                $loginSuccess = true;
            }

            error_log (print_r($act,true), 0);

            if ($loginSuccess){

                $sqlString = "SELECT * from pgs_team_users
                    where username = '$filters[username]'
                            ";

                $stmt = $conn->prepare($sqlString);
                $stmt->execute();
                $rows = $stmt->fetchAll();
                foreach ($rows as $row) {
                    $teamA = new TeamAuthorization();

                    $teamA->id = $row['id'];
                    $teamA->teamid = $row['teamid'];
                    $teamA->username = $row['username'];
                    $teamA->display = $row['display'];
                    $teamA->update = $row['update'];
                    $teamA->onlyplayer = $row['onlyplayer'];

                    $act->teamsAuthorizations[] = $teamA;
                }

                $returnResult->success = 1;
                $returnResult->returnObject = $act;
            } else {
                $returnResult->returnMessages[] = "Autenticazione fallita";
                $returnResult->success = 0;
            }

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

                    // set the PDO error mode to exception
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $sqlString = "SELECT pgs_teams.id as team_id,
                                           pgs_teams.name as team_name,
                                           pgs_players.id as player_id,
                                           pgs_players.name,
                                           pgs_players.surname,
                                           pgs_players.nickname,
                                           pgs_players.tnumber,
                                           pgs_players.role,
                                           pgs_players.role2
                                         from pgs_teams
                                            left outer join pgs_players
                                                on pgs_teams.id = pgs_players.idteam
                                    where pgs_teams.id = '$filters[id]'
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
                            $act->name = $row['team_name'];
                            $act->players = array();
                            $first = false;
                        }

                        if ($row['player_id'] != NULL){
                            $player = new Player();
                            $player->id = $row['player_id'];
                            $player->name = $row['name'];
                            $player->surname = $row['surname'];
                            $player->nickname = $row['nickname'];
                            $player->tnumber = $row['tnumber'];
                            $player->role = $row['role'];
                            $player->role2 = $row['role2'];
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
                            (idteam, name, surname, nickname, tnumber, role, role2
                            )
                            VALUES (:idteam, :name, :surname, :nickname, :tnumber, :role, :role2
                            )
                    ");

            $stmtUpdate = $conn->prepare("UPDATE pgs_players
                            set idteam = :idteam, name = :name, surname = :surname,
                            nickname = :nickname, tnumber = :tnumber, role = :role, role2 = :role2
                            where id = :id
                    ");

            $stmtInsert->bindParam(':idteam', $idteam);
            $stmtInsert->bindParam(':name', $name);
            $stmtInsert->bindParam(':surname', $surname);
            $stmtInsert->bindParam(':nickname', $nickname);
            $stmtInsert->bindParam(':tnumber', $tnumber);
            $stmtInsert->bindParam(':role', $role);
            $stmtInsert->bindParam(':role2', $role2);

            $stmtUpdate->bindParam(':id', $id);
            $stmtUpdate->bindParam(':idteam', $idteam);
            $stmtUpdate->bindParam(':name', $name);
            $stmtUpdate->bindParam(':surname', $surname);
            $stmtUpdate->bindParam(':nickname', $nickname);
            $stmtUpdate->bindParam(':tnumber', $tnumber);
            $stmtUpdate->bindParam(':role', $role);
            $stmtUpdate->bindParam(':role2', $role2);

            $id = $in[id];
            $idteam = $in[idteam];
            $name = $in[name];
            $surname = $in[surname];
            $nickname = $in[nickname];
            $tnumber = $in[tnumber];
            $role = $in[role];
            $role2 = $in[role2];

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


    function getPlayerData($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            error_log ("Start getPlayerData Api", 0);

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT pgs_players.*,
                                pgs_teams.logo
                                 from pgs_players
                                  inner join pgs_teams
                                        on pgs_players.idteam = pgs_teams.id
                            where username = '$filters[username]'
                                ";

            $stmt = $conn->prepare($sqlString);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $loginSuccess = false;

            $act = new User();
            // output data of each row
            foreach ($rows as $row) {
                $act->id = $row['id'];
                $act->name = $row['name'];
                $act->surname = $row['surname'];
                $act->username = $row['username'];
                $act->idteam = $row['idteam'];
                $act->onlyplayer = $row['onlyplayer'];
                $act->logo = $row['logo'];

                $act->teamsAuthorizations = array();

                $loginSuccess = true;
            }

            error_log (print_r($act,true), 0);

            if ($loginSuccess){

                $sqlString = "SELECT * from pgs_team_users
                    where username = '$act->id'
                            ";

                $stmt = $conn->prepare($sqlString);
                $stmt->execute();
                $rows = $stmt->fetchAll();
                foreach ($rows as $row) {
                    $teamA = new TeamAuthorization();

                    $teamA->id = $row['id'];
                    $teamA->teamid = $row['teamid'];
                    $teamA->username = $row['username'];
                    $teamA->display = $row['display'];
                    $teamA->update = $row['update'];
                    $teamA->onlyplayer = $row['onlyplayer'];

                    $act->teamsAuthorizations[] = $teamA;
                }

                $returnResult->success = 1;
                $returnResult->returnObject = $act;
            } else {
                $returnResult->returnMessages[] = "Giocatore non trovato";
                $returnResult->success = 0;
            }

            $conn = null;

        } catch(PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: " . $e->getMessage();
            $returnResult->success = 0;

            $conn = null;
        }

        return $returnResult;
    }

    function getAdminData($filters){

        try {
            $returnResult = new ServiceResult();
            $returnResult->returnMessages[] = "Start operation";

            error_log ("Start getAdminData Api", 0);
            error_log (print_r($filters,true), 0);

            $conn = connectToDbPDO();

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sqlString = "SELECT * from pgs_users
                            where main_username = '$filters[username]'
                                ";

            $stmt = $conn->prepare($sqlString);

            error_log (print_r($sqlString,true), 0);

            $stmt->execute();
            $rows = $stmt->fetchAll();
            $loginSuccess = false;

            $act = new User();
            // output data of each row
            foreach ($rows as $row) {
                $act->id = $row['id'];
                $act->name = $row['name'];
                $act->surname = $row['surname'];
                $act->username = $row['username'];
                $act->vusername = $row['username'];

                $act->teamsAuthorizations = array();

                $loginSuccess = true;
            }

            error_log (print_r($act,true), 0);

            if ($loginSuccess){

                $sqlString = "SELECT pgs_team_users.*,
                                        pgs_teams.name as team_name,
                                        pgs_teams.logo as team_logo,
                                        pgs_teams.calendarid as team_calendarid,
                                        pgs_teams.cid as team_cid,
                                        pgs_teams.fipav_name as team_fipav_name
                                     from pgs_team_users
                                            inner join pgs_teams
                                              on pgs_team_users.teamid = pgs_teams.id
                    where username = '$act->vusername'
                            ";

                $stmt = $conn->prepare($sqlString);
                $stmt->execute();
                $rows = $stmt->fetchAll();
                foreach ($rows as $row) {
                    $teamA = new TeamAuthorization();

                    $teamA->id = $row['id'];
                    $teamA->teamid = $row['teamid'];
                    $teamA->username = $row['username'];
                    $teamA->display = $row['display'];
                    $teamA->update = $row['update'];
                    $teamA->onlyplayer = $row['onlyplayer'];
                    $teamA->teamdescription = $row['team_name'];
                    $teamA->teamlogo = $row['team_logo'];
                    $teamA->teamcalendarid = $row['team_calendarid'];
                    $teamA->teamcid = $row['team_cid'];
                    $teamA->teamfipavname = $row['team_fipav_name'];

                    $act->teamsAuthorizations[] = $teamA;
                }

                $returnResult->success = 1;
                $returnResult->returnObject = $act;
            } else {
                $returnResult->returnMessages[] = "Giocatore non trovato";
                $returnResult->success = 0;
            }

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