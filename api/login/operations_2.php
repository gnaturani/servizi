<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("../models/user.php");
require_once("utente.php");
require_once("../mainjwt.php");
require_once("operations.php");

error_reporting(E_ERROR | E_PARSE);

function loginForGoogleUser($data)
{

    error_log(print_r("START!", true), 0);
    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {
        $returnResult->returnMessages[] = "Start operation";

        $id_token = $data[id_token];
        // error_log(print_r($id_token, true), 0);

        $googleAuth = json_decode(file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=".$id_token));

        if ($googleAuth->aud == "42025098140-cauo0nalpds7p2k15fbj1konl2rtttj5.apps.googleusercontent.com") {
            $username = $googleAuth->email;
            $name = $googleAuth->given_name;
            $surname = $googleAuth->family_name;
        } else {
            // header("HTTP/1.1 500 Internal Server Error");
            $returnResult->returnMessages[] = "Error: Google Token not valid!";
            $returnResult->result = 0;
            return $returnResult;
        }

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from utenti
                                where username = :username
                            ";

        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':username', $username);

        $found = false;
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $act = new Utente();

        // output data of each row
        foreach ($rows as $row) {           
            $returnResultUpd = getUser($row[id]);

            error_log(print_r($returnResultUpd, true), 0);

            $act = $returnResultUpd->returnObject;
            $token = getJwt($act);
            $act->token = $token;

            $found = true;

            $returnResult->success = 1;
        }
        $returnResult->returnObject = $act;

        if ($found == false) {
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Nessun utente trovato con username: $data[username]";

            // devo creare l'utente 
            $data[name] = $name;
            $data[surname] = $surname;
            $data[username] = $username;
            $data[password] = "19780201!";
            signUpFromGoogle($data);

            $sqlString = "SELECT * from utenti
                    where username = :username
                ";

            $stmt = $conn->prepare($sqlString);

            $stmt->bindParam(':username', $username);

            $found = false;
            $stmt->execute();
            $rows = $stmt->fetchAll();
            $act = new Utente();

            // output data of each row
            foreach ($rows as $row) {           
                $returnResultUpd = getUser($row[id]);

                error_log(print_r($returnResultUpd, true), 0);

                $act = $returnResultUpd->returnObject;
                $token = getJwt($act);
                $act->token = $token;

                $found = true;

                $returnResult->success = 1;
            }
            $returnResult->returnObject = $act;            

        }

        $conn = null;
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function signUpFromGoogle($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        if (!filter_var($data[username], FILTER_VALIDATE_EMAIL)) {
            $returnResult->result = 0;
            $returnResult->returnMessages = ["Indirizzo mail $data[username] NON valido!"];
            return $returnResult;
        }

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select * from utenti
                                 where username = :usernameS
                        ");
        $stmtSearch->bindParam(':usernameS', $username);
        $username = $data[username];

        $alreadyExist = false;
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {
            $alreadyExist = true;
        }

        if ($alreadyExist) {
            $returnResult->success = 0;
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Username già utilizzato!";
            $conn = null;
            return $returnResult;
        }

        error_log(print_r("prepare_insert", true), 0);
        error_log(print_r($data, true), 0);
    
        $stmtInsert = $conn->prepare("INSERT INTO utenti
                                (username, password, gruppo, created_at, gest_pag, only_display, confirmed, nome, cognome
                                )
                                VALUES (:username, :password, :gruppo, NOW(), 0, 0, 1, :nome, :cognome
                                )
                        ");

        $stmtInsert->bindParam(':username', $username);
        $stmtInsert->bindParam(':password', $password);
        $stmtInsert->bindParam(':gruppo', $gruppo);
        $stmtInsert->bindParam(':nome', $nome);
        $stmtInsert->bindParam(':cognome', $cognome);

        $username = $data[username];
        $gruppo = $data[gruppo];
        $password = $data[password];
        $nome = $data[name];
        $cognome = $data[surname];

        $stmtInsert->execute();

        $returnResult->success = 1;
        $confirmId = $conn->lastInsertId();

        error_log(print_r("confirmId", true), 0);
        error_log(print_r($confirmId, true), 0);

        $stmtInsertApp = $conn->prepare("INSERT INTO utenti_app
                        (iduser, idapp
                        )
                        VALUES (:iduser, :idapp
                        )
                ");

        $stmtInsertApp->bindParam(':iduser', $iduser);
        $stmtInsertApp->bindParam(':idapp', $idapp);
        $iduser = $confirmId;
        $idapp = 1;
        $stmtInsertApp->execute();

        $stmtInsertApp = $conn->prepare("INSERT INTO utenti_app
                        (iduser, idapp
                        )
                        VALUES (:iduser, :idapp
                        )
                ");

        $stmtInsertApp->bindParam(':iduser', $iduser);
        $stmtInsertApp->bindParam(':idapp', $idapp);
        $iduser = $confirmId;
        $idapp = 5;
        $stmtInsertApp->execute();

        $stmtInsertApp = $conn->prepare("INSERT INTO utenti_app
                        (iduser, idapp
                        )
                        VALUES (:iduser, :idapp
                        )
                ");

        $stmtInsertApp->bindParam(':iduser', $iduser);
        $stmtInsertApp->bindParam(':idapp', $idapp);
        $iduser = $confirmId;
        $idapp = 1001;
        $stmtInsertApp->execute();

        $conn = null;
        $returnResult->success = 1;

    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        error_log(print_r("Error", true), 0);
        error_log(print_r($e->getMessage(), true), 0);

        $conn = null;
    }

    return $returnResult;
}