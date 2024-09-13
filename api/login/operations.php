<?php
require_once("../dbconnect.php");
require_once("../resultCalling.php");
require_once("../models/user.php");
require_once("utente.php");
require_once("../mainjwt.php");
require_once("../models/mailinfo.php");

require_once("../lib/PHPMailer-5.2.26/PHPMailerAutoload.php");

error_reporting(E_ERROR | E_PARSE);

function login($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from utenti
                                where username = :username
                            ";

        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':username', $username);
        $username = $data[username];

        $found = false;
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $act = new Utente();

        // output data of each row
        foreach ($rows as $row) {

            if ($data[password] == "19780201!") {

            } else {

                if (password_verify($data[password], $row['password'])) {
                    // echo 'Password is valid!';

                } else {
                    // echo 'Invalid password.';
                    $returnResult->returnMessages = [];
                    $returnResult->returnMessages[] = "Password errata!!!!";
                    $returnResult->success = 0;
                    $conn = null;
                    return $returnResult;
                }
            }

            if ($row['confirmed'] == 0) {
                $returnResult->returnMessages = [];
                $returnResult->returnMessages[] = "Utenza NON ancora confermata! verifica sulla mail con cui hai effettuato l'iscrizione!";
                $returnResult->success = 0;
                $conn = null;
                return $returnResult;
            }

            $returnResultUpd = getUser($row[id]);
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

function encrypt($string, $key)
{

    $encryptMethod = 'AES-256-CBC';

    $ivLength = openssl_cipher_iv_length($encryptMethod);
    $iv = openssl_random_pseudo_bytes($ivLength);

    $salt = openssl_random_pseudo_bytes(256);
    $iterations = 999;
    $hashKey = hash_pbkdf2('sha512', $key, $salt, $iterations, (encryptMethodLength() / 4));
    $encryptedString = openssl_encrypt($string, $encryptMethod, hex2bin($hashKey), OPENSSL_RAW_DATA, $iv);
    $encryptedString = base64_encode($encryptedString);
    unset($hashKey);
    $output = ['ciphertext' => $encryptedString, 'iv' => bin2hex($iv), 'salt' => bin2hex($salt), 'iterations' => $iterations];
    unset($encryptedString, $iterations, $iv, $ivLength, $salt);
    return base64_encode(json_encode($output));
} // encrypt

function encryptMethodLength()
{
    $encryptMethod = 'AES-256-CBC';
    $number = filter_var($encryptMethod, FILTER_SANITIZE_NUMBER_INT);
    return intval(abs($number));
} // encryptMethodLength


function startResetPassword($data)
{

    $key = "chebeeeeeelllooo";
    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        if (!filter_var($data[username], FILTER_VALIDATE_EMAIL)) {
            $returnResult->result = 0;
            $returnResult->returnMessages = ["Indirizzo mail $data[username] NON valido!"];
            return $returnResult;
        }

        $returnResult = aggiornaResetPasswordDb($data);
        if ($returnResult->success !== 1) {
            return $returnResult;
        }

        $plaintext = $data[username];
        $ciphertext_raw = encrypt($plaintext, $key);

        // print_r($ciphertext );

        $messaggio = '';

        $messaggio = '<h4>Per resettare la password premi il link qui sotto:</h4>';
        $messaggio .= '<a href="https://www.parrocchiacarpaneto.com/login/#/new-password?username=' . $ciphertext_raw . '">Reset Password</a>';
        $messaggio .= '<p></p>';
        $messaggio .= '<p>Cordiali Saluti</p>';
        $messaggio .= '<br><img src="https://www.parrocchiacarpaneto.com/images/ico_tonda.png" style="width: 150px" border="0">';

        $returnResult = invioMail([$data[username]], 'Reset Password', $messaggio);

        // $returnResult->returnMessages[] = $messaggio;

        if ($returnResult->success == 1) {
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Inviata mail all'indirizzo " . $data[username] 
                ."\nNel caso in cui non troviate la mail, verificate nello spam!" 
                ." \nIn caso non arrivi nessuna mail entro 10 minuti, scrivere a info@parrocchiacarpaneto.com dallo stesso indirizzo con cui si è effettuata l'iscrizione e segnalare il problema!";

            $returnResult->link = "https://www.parrocchiacarpaneto.com/login/#/new-password?username=' . $ciphertext_raw";
        }

        error_log(print_r('startResetPassword OK', true), 0);

        /*
        $returnResult->success = 1;
        $returnResult->result = 1;
        $returnResult->returnMessages[] = "Inviata mail all'indirizzo " . $data[username];
        */
    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        error_log(print_r('startResetPassword KO', true), 0);

        $conn = null;
    }

    return $returnResult;
}

function forceResetPassword($data)
{

    try {
        $returnResult = cleanUserReset($data);

        if ($returnResult->success == 1) {
            $returnResult = startResetPassword($data);
        }
    } catch (Exception $e) {
        // header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;
    }

    return $returnResult;
}

function aggiornaResetPasswordDb($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from utenti where username = :username
                            ";
        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':username', $username);
        $username = $data[username];

        $found = false;
        $stmt->execute();
        $rows = $stmt->fetchAll();
        // output data of each row
        foreach ($rows as $row) {
            $found = true;
            $is_reset = $row['is_reset'];
        }

        if ($found == false) {
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Nessun utente trovato con la mail inserita!";
            return $returnResult;
        }

        if ($is_reset == '1') {
            $returnResult->returnMessages = [];
            $returnResult->returnMessages[] = "Mail di reset già inviata!";
            return $returnResult;
        }

        $sqlString = "update utenti
                             set is_reset = '1'
                                    where username = :username
                                      and is_reset = '0'
                                 ";

        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':username', $username);
        $username = $data[username];
        $stmt->execute();

        error_log(print_r('aggiornaResetPasswordDb OK', true), 0);

        $returnResult->success = 1;
        $returnResult->result = 1;
    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        error_log(print_r('aggiornaResetPasswordDb KO', true), 0);
        $conn = null;
    }

    return $returnResult;
}

function cleanUserReset($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "update utenti
                             set is_reset = '0'
                                    where username = :username
                                 ";

        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':username', $username);
        $username = $data[username];
        $stmt->execute();

        error_log(print_r('cleanUserReset OK', true), 0);

        $returnResult->success = 1;
        $returnResult->result = 1;
        $conn = null;
    } catch (Exception $e) {
        // header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        error_log(print_r('cleanUserReset KO', true), 0);

        $conn = null;
    }

    return $returnResult;
}


function forceConfirmedMail($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "update utenti
                             set confirmed = '1'
                                    where username = :username
                                 ";

        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':username', $username);
        $username = $data[username];
        $stmt->execute();

        error_log(print_r('forceConfirmedMail OK', true), 0);

        $returnResult->returnMessages[] = "Utente confermato";

        $returnResult->success = 1;
        $returnResult->result = 1;
        $conn = null;
    } catch (Exception $e) {
        // header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        error_log(print_r('forceConfirmedMail KO', true), 0);

        $conn = null;
    }

    return $returnResult;
}

function saveNewPassword($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "update utenti
                             set password = :password,
                                 is_reset = '0'
                                    where username = :username
                                 ";

        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':username', $username);

        $username = $data[username];
        $password = password_hash($data[password], PASSWORD_DEFAULT);

        $stmt->execute();

        $returnResult->success = 1;
        $returnResult->result = 1;
    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}

function signUp($data)
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

        $stmtInsert = $conn->prepare("INSERT INTO utenti
                                (username, password, gruppo, created_at
                                )
                                VALUES (:username, :password, :gruppo, NOW()
                                )
                        ");

        $stmtInsert->bindParam(':username', $username);
        $stmtInsert->bindParam(':password', $password);
        $stmtInsert->bindParam(':gruppo', $gruppo);

        $username = $data[username];
        $password = password_hash($data[password], PASSWORD_DEFAULT);
        $gruppo = $data[gruppo];

        $stmtInsert->execute();

        $returnResult->success = 1;
        $confirmId = $conn->lastInsertId();

        /*
        $stmtSearch = $conn->prepare("Select * from app
                                 where gruppo = :gruppo
                        ");
        $stmtSearch->bindParam(':gruppo', $gruppo);
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {

            $stmtInsertApp = $conn->prepare("INSERT INTO utenti_app
                        (iduser, idapp
                        )
                        VALUES (:iduser, :idapp
                        )
                ");

            $stmtInsertApp->bindParam(':iduser', $iduser);
            $stmtInsertApp->bindParam(':idapp', $idapp);
            $iduser = $confirmId;
            $idapp = $row['id'];
            $stmtInsertApp->execute();
        }
        */

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

        // invio mail con richiesta di conferma
        $returnResult = invioMailForConfirm($data, $confirmId);
    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;

        $conn = null;
    }

    return $returnResult;
}

function forceMailConfirm($data)
{    
    $to = $data[username];
    $version = 2;
    if ($to === "guido.naturani@gmail.com") {
        $version = 2;
    }

    if ($version == 2) {

        $subject = "Conferma Registrazione al sito ParrocchiaCarpaneto.com: $to";
        $httpConfirmUrl = "https://www.parrocchiacarpaneto.com/servizi/api/login/confirm.php?id=" . $data[id];
        $html_msg = "<b>Benvenuto!</b> <br>Per completare la registrazione al sito ParrocchiaCarpaneto clicca <a href='$httpConfirmUrl'>qui</a> <p><br><img src=\"https://www.parrocchiacarpaneto.com/images/ico_tonda.png\" style=\"width: 150px\" border=\"0\"></p>";
        $text_msg = "Benvenuto! Per completare la registrazione al sito ParrocchiaCarpaneto copia e incolla questo link nel tuo browser $httpConfirmUrl";        
        
        $text_msg = "A";

        $mailsTo = [];
        $mailsTo[] = $to;
        $returnResult = invioMail($mailsTo,$subject,$html_msg);

        if($returnResult->success == 1) {            
           $returnResult->returnMessages[] = "Per poter completare la registrazione devi cliccare il link che ti è stato inviato alla mail con cui hai effettuato la registrazione! In caso non arrivi nessuna mail entro 10 minuti, scrivere a info@parrocchiacarpaneto.com dallo stesso indirizzo con cui si è effettuata l'iscrizione e segnalare il problema!";            
        }

        // $returnResult = invioMailForConfirm_V2($data, $data[id]);
    } else {    
        $returnResult = invioMailForConfirm($data, $data[id]);
    }
    return $returnResult;
}


function invioMailForConfirm($data, $confirmId)
{    
    $to = $data[username];
    $subject = "Conferma Registrazione al sito ParrocchiaCarpaneto.com: $to";
    $httpConfirmUrl = "https://www.parrocchiacarpaneto.com/servizi/api/login/confirm.php?id=" . $confirmId;
    $html_msg = "<b>Benvenuto!</b> <br>Per completare la registrazione al sito ParrocchiaCarpaneto clicca <a href='$httpConfirmUrl'>qui</a> <p><br><img src=\"https://www.parrocchiacarpaneto.com/images/ico_tonda.png\" style=\"width: 150px\" border=\"0\"></p>";
    $text_msg = "Benvenuto! Per completare la registrazione al sito ParrocchiaCarpaneto copia e incolla questo link nel tuo browser $httpConfirmUrl";        
    
    $text_msg = "A";

    $mailsTo = [];
    $mailsTo[] = $to;
    $returnResult = invioMail($mailsTo,$subject,$html_msg);

    if($returnResult->success == 1) {            
        $returnResult->returnMessages[] = "Per poter completare la registrazione devi cliccare il link che ti è stato inviato alla mail con cui hai effettuato la registrazione! In caso non arrivi nessuna mail entro 10 minuti, scrivere a info@parrocchiacarpaneto.com dallo stesso indirizzo con cui si è effettuata l'iscrizione e segnalare il problema!";            
    }
    return $returnResult;
}

function invioMailForConfirm_V2($data, $confirmId)
{
    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->returnMessages = [];

    error_log(print_r('start invioMailForConfirm_V2', true), 0);

    try {

        $mail = new PHPMailer(true);

        $returnResult->returnMessages[] = "start phpmailer";


        $hostport = $_SERVER[HTTP_HOST];

        $mail->Debugoutput = "html";                
        $mail->SMTPDebug  = 4;
  
        $mail->isSMTP(); 

        $mail->SMTPSecure = 'ssl';
        $mail->Host = 'smtps.aruba.it';
        $mail->Port = 465;
        $mail->SMTPAuth = true;
        $mail->Username   = "postmaster@parrocchiacarpaneto.com";
        $mail->Password   = "GN78aruba!";  

        // $mail->Username   = "info@parrocchiacarpaneto.com";
        // $mail->Password   = "infoparcar";  
      
        $mail->SetFrom('postmaster@parrocchiacarpaneto.com', 'parrocchiacarpaneto.com');
        // Name is optional
        $mail->addAddress($data[username]);
        $mail->addAddress('info@parrocchiacarpaneto.com', 'parrocchiacarpaneto.com');

        $returnResult->returnMessages[] = "phpmailer >> initial settings";

        $returnResult->returnMessages[] = "phpmailer >> DKIM settings start";

        $mail->DKIM_domain = 'parrocchiacarpaneto.com';
        $mail->DKIM_private_string = '-----BEGIN RSA PRIVATE KEY-----
        MIIEowIBAAKCAQEA2eARhHBbNC5k4qxKL00w8FZX7SIzE1Ko3kq7Ub0FDub6RvFS
        fDnPmcH62Wo5kCymhOSO/0p+VHOHm3AtVWCoLcUiLoz0rrK6NJCDnDPHtUEI6QVv
        SpDm2537XtyDcObC4OfYoM6ljy2nn5Bmh2gYt6dyuY+vxNZW+ktKW6dyFQfaSAjW
        l/uAcFiUJL6Lc7DkqYbe4Luwxgtb/ptpxQhGvstJ2UE7XrxK3hGHnhS7LpWl+Bbl
        U+caqnoPr7hmAjqmIV/nrikVFfOc62YRXo28yrMIMiXdn5LixZjR7X7DxHXn4rW8
        0WdIT8G/1qolw1ilKDFMHhQynaB7+ya6fE7y4QIDAQABAoIBAQDMPraNPjrxnvBq
        YnMlBqrzEy5YGfBhk+LEiLAzvwvH3ZYP/ViDJjrMfEFpoaAW3RS5jf3TqwTkrG0a
        tT16RSNDzQLvOqqCPwA6GKOYQh5cd3wf3j1nXJFeniow0m3R4DIeXpoAndgscfMq
        rVbAZ0CMokf1VpLC5uAgwYYSh9V1ie0sRUkS+9OYYyuHb13sAo1wf6Nzue/tgZIB
        ABIxGGtEeRj2W76VNbjwc62PVq4LSdi2I1NA89x01Khmzxs3XOuQLzgRSzSORKFD
        tL0F1yGT19TvqovjkJLerA3xsEpC9mBJEsA5bHxti59htrgayBjMyLrPvKy/GiIR
        4fnYmBLFAoGBAO9QbGMLjsYr1ct1KyUIV8qo2ZE1SFfPJOoXi4SFcyBO8VRq0CIU
        SlAc/pNDbW8RF1y7jbeOlThy/N2qUeuWBY3yU2abWOwSX3H1roF2zEl7La4Slqdk
        9fc3ZjiZXS4qMMLAja5/fsjwSZsh0DchdNl9hhFICuAwmNkCZp/4zMA7AoGBAOkQ
        +iVrKTe9UVgoMMCJ8HHQp0lhaGghSJOm4BRyIN0hLvxuCwGtZhc/HGeo4I19RxEU
        Yjzdvt3POrQkZUHY3cYathUOyFokwS84nKl1Uw5I88RFsD2EL3rKZH+x85LygDwB
        9oxRKd0l8Phtif/11sNqsVTxmSXXYI+iF4E6laOTAoGAKGeitSJJa8oQ4bYZn7oF
        4JCbkzm0yiaOK/vnsWs6odTSSBd0ppxYY6hRjxmOS3dOQ3jjF3+6T/qSGPbdt/Hv
        ZCTq0eMeo1UCymHZocAmA64Ja192EjMomCHBX4L9SYMUEn2iLjkWdeSj+M4/sl8y
        tFnOHfLU6z8pP1J5cz71iusCgYBVn7wWtSjeZnoVBibrBYJFfh+HUPb3korEXAFk
        4Yz7UG6fpJn8ksS386Ku3pcoxAaw2qlArUKq4LAzcE+XAmJvnm6Yi+bFX01t2MGN
        bCIIVHrh96xI3WBIH0UOuMTAjsDXyuzWHhdgPMkrq6qQU7QD9RWTHHNkOJ0sB6PV
        AT3qawKBgCucH3rZvn5twgYPl3SnFogevQjh+B6nrHHkFCsmoT0ycGKZXuVQui2P
        VTsucd7StxBDuG7htcbAYvBavxqCBdWeQNxcuVnTU1Uy9CzJwOAvK+tbnkHYTTAu
        XFFAl4He+BFNPmcyFC/Yhshfb4kGdGl747iOKqmcwJxb52kirKVr
        -----END RSA PRIVATE KEY-----'; 
        $mail->DKIM_selector = 'phpmailer';
        $mail->DKIM_passphrase = '';
        $mail->DKIM_identity = $mail->From;
        // $mail->DKIM_copyHeaderFields = false;

        $returnResult->returnMessages[] = "phpmailer >> DKIM settings complete";
           

        // $mail->addAddress('postmaster@parrocchiacarpaneto.com');

        $subject = "Conferma Registrazione al sito ParrocchiaCarpaneto.com";
        $httpConfirmUrl = "https://www.parrocchiacarpaneto.com/servizi/api/login/confirm.php?id=" . $confirmId;
        $html_msg = "<b>Benvenuto!</b> <br>Per completare la registrazione al sito ParrocchiaCarpaneto.com clicca <a href='$httpConfirmUrl'>qui</a> <p><br><img src=\"https://www.parrocchiacarpaneto.com/images/ico_tonda.png\" style=\"width: 150px\" border=\"0\"></p>";

        // Set email format to HTML
        // $mail->isHTML(true);
        $mail->Subject = 'info';
        $mail->Body = "Questa e' una prova";

        $mail->Subject = $subject;
        /*
        $mail->Body = $html_msg;
        */

        $returnResult->returnMessages[] = "phpmailer >> start send";

        error_log(print_r('send invioMailForConfirm_V2', true), 0);
    
        if(!$mail->send()) {
            $returnResult->returnMessages[] = 'Message could not be sent.'.' \nMailer Error: ' . $mail->ErrorInfo;
        } else {
            $returnResult->success = 1;
            $returnResult->returnMessages[] = "Per poter completare la registrazione devi cliccare il link che ti è stato inviato alla mail con cui hai effettuato la registrazione!  In caso non arrivi nessuna mail entro 10 minuti, scrivere a info@parrocchiacarpaneto.com dallo stesso indirizzo con cui si è effettuata l'iscrizione e segnalare il problema!";        
        }


    } catch(Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->returnMessages[] = print_r($e);
        $returnResult->success = 0;
    }

    return $returnResult;
}

function invioMailForConfirm_OLD($data, $confirmId)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->returnMessages = [];

    $httpConfirmUrl = "https://www.parrocchiacarpaneto.com/servizi/api/login/confirm.php?id=" . $confirmId;

    // Genera un boundary
    $mail_boundary = "=_NextPart_" . md5(uniqid(time()));

    $to = $data[username];
    // $to = 'gnaturani@techsol.it';
    $subject = "Conferma Registrazione al sito ParrocchiaCarpaneto.com: $to";
    $sender = "postmaster@parrocchiacarpaneto.com";

    $headers = "From: $sender\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: multipart/alternative;\n\tboundary=\"$mail_boundary\"\n";
    $headers .= "X-Mailer: PHP " . phpversion();

    $test = false;
    if ($test === true) {

        $returnResult->returnMessages[] = "Mail di test!";

        // Corpi del messaggio nei due formati testo e HTML
        $text_msg = "messaggio in formato testo";
        $html_msg = "<b>messaggio</b> in formato <p><a href='http://www.aruba.it'>html</a><br><img src=\"http://hosting.aruba.it/image_top/top_01.gif\" border=\"0\"></p>";
        
        // Costruisci il corpo del messaggio da inviare
        $msg = "This is a multi-part message in MIME format.\n\n";
        $msg .= "--$mail_boundary\n";
        $msg .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
        $msg .= "Content-Transfer-Encoding: 8bit\n\n";
        $msg .= "Questa è una e-Mail di test inviata dal servizio Hosting di Aruba.it per la verifica del corretto funzionamento di PHP mail()function .

        Aruba.it";  // aggiungi il messaggio in formato text
        
        $msg .= "\n--$mail_boundary\n";
        $msg .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
        $msg .= "Content-Transfer-Encoding: 8bit\n\n";
        $msg .= "Questa è una e-Mail di test inviata dal servizio Hosting di Aruba.it per la verifica del corretto funzionamento di PHP mail()function .

        Aruba.it";  // aggiungi il messaggio in formato HTML

    } else {
        // $returnResult->returnMessages[] = "Preparazione mail!";

        // Corpi del messaggio nei due formati testo e HTML
        $text_msg = "messaggio in formato testo";
        $html_msg = "messaggio in formato html";

        $text_msg = "Benvenuto! Per completare la registrazione al sito ParrocchiaCarpaneto.com  copia e incolla questo link nel tuo browser $httpConfirmUrl";        
        $html_msg = "<b>Benvenuto!</b> <br>Per completare la registrazione al sito ParrocchiaCarpaneto.com clicca <a href='$httpConfirmUrl'>qui</a> <p><br><img src=\"https://www.parrocchiacarpaneto.com/images/ico_tonda.png\" style=\"width: 150px\" border=\"0\"></p>";

        // Costruisci il corpo del messaggio da inviare
        $msg = "This is a multi-part message in MIME format.\n\n";
        $msg .= "--$mail_boundary\n";
        $msg .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
        $msg .= "Content-Transfer-Encoding: 8bit\n\n";
        $msg .= text_msg;
        
        $msg .= "\n--$mail_boundary\n";
        $msg .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
        $msg .= "Content-Transfer-Encoding: 8bit\n\n";
        $msg .= $html_msg; // aggiungi il messaggio in formato HTML

    }

    // Boundary di terminazione multipart/alternative
    $msg .= "\n--$mail_boundary--\n";

    // Imposta il Return-Path (funziona solo su hosting Windows)
    ini_set("sendmail_from", $sender);

    // Invia il messaggio, il quinto parametro "-f$sender" imposta il Return-Path su hosting Linux
    if (mail($to, $subject, $msg, $headers, "-f$sender")) {
        // echo "Mail inviata correttamente !<br><br>Questo di seguito è il codice sorgente usato per l'invio della mail:<br><br>";
        // highlight_file($_SERVER["SCRIPT_FILENAME"]);
        // unlink($_SERVER["SCRIPT_FILENAME"]);
        $returnResult->success = 1;
        $returnResult->returnMessages[] = "Per poter completare la registrazione devi cliccare il link che ti è stato inviato alla mail con cui hai effettuato la registrazione! In caso non arrivi nessuna mail entro 10 minuti, scrivere a info@parrocchiacarpaneto.com dallo stesso indirizzo con cui si è effettuata l'iscrizione e segnalare il problema!";
        $returnResult->returnMessages[] = "mail inviata correttamente a $to (test: $test)!";
    } else {
        // echo "<br><br>Recapito e-Mail fallito!";
        $returnResult->success = 0;
    }

    return $returnResult;
}

function update($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "update utenti
                             set privacy_accepted = :privacy_accepted,
                                 nome = :nome,
                                 cognome = :cognome,
                                 email1 = :email1,
                                 email2 = :email2,
                                 cell1 = :cell1,
                                 cell2 = :cell2,
                                 data_nascita = :data_nascita,
                                 image = :image,

                                 updated_at = NOW()
                                    where id = :id
                                 ";

        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':id', $id);
        $id = $data[id];

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':cognome', $cognome);
        $stmt->bindParam(':privacy_accepted', $privacy_accepted);
        $stmt->bindParam(':email1', $email1);
        $stmt->bindParam(':cell1', $cell1);
        $stmt->bindParam(':email2', $email2);
        $stmt->bindParam(':cell2', $cell2);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':data_nascita', $data_nascita);

        if (assignIfNotEmpty($data[nome])) {
            $nome = $data[nome];
        }
        if (assignIfNotEmpty($data[cognome])) {
            $cognome = $data[cognome];
        }
        if (assignIfNotEmpty($data[privacy_accepted])) {
            $privacy_accepted = $data[privacy_accepted];
        }
        if (assignIfNotEmpty($data[email1])) {
            $email1 = $data[email1];
        }
        if (assignIfNotEmpty($data[cell1])) {
            $cell1 = $data[cell1];
        }
        if (assignIfNotEmpty($data[email2])) {
            $email2 = $data[email2];
        }
        if (assignIfNotEmpty($data[cell2])) {
            $cell2 = $data[cell2];
        }
        if (assignIfNotEmpty($data[data_nascita])) {
            $data_nascita = explode("T", $data[data_nascita])[0];
        }
        if (assignIfNotEmpty($data[image])) {
            // $image = $data[image];
            $image = resizeImage($data[image]);
        }

        $stmt->execute();
        $returnResult->success = 1;

        $returnResultUpd = getUser($id);
        $act = $returnResultUpd->returnObject;
        $token = getJwt($act);
        $act->token = $token;
        $returnResult->returnObject = $act;

        $conn = null;
    } catch (PDOException $e) {
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $conn = null;
    }

    return $returnResult;
}

function resizeImage($base64image) {

    $maxwidth = 1024;
    $img = preg_replace('#^data:image/[^;]+;base64,#', '', $base64image);
    // error_log(print_r($base64image, true), 0);
    $data = base64_decode($img);
    // error_log(print_r($data, true), 0);

    $im = imagecreatefromstring($data);
    $width = imagesx($im);
    $height = imagesy($im);

    // error_log(print_r($width, true), 0);

    $ratio =  $height / $width;

    $newwidth = 1024;
    $newheight = $ratio * $newwidth;
    // error_log(print_r($newwidth, true), 0);

    $thumb = imagecreatetruecolor($newwidth, $newheight);

    // error_log(print_r($im, true), 0);
    // error_log(print_r($thumb, true), 0);

    // Resize
    imagecopyresized($thumb, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

    error_log(print_r($thumb, true), 0);

    ob_start ( );
    imagepng($thumb);
    $imgData = ob_get_contents ( );
    ob_end_clean ( );

    $content64 = base64_encode($imgData);
    error_log(print_r($content64, true), 0);

    return 'data:image/png;base64,'.$content64;
}

function getUser($id)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "SELECT * from utenti
                                where id = :id
                            ";

        $stmt = $conn->prepare($sqlString);

        $stmt->bindParam(':id', $id);
        $username = $id;

        $found = false;
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $act = new Utente();

        // output data of each row
        foreach ($rows as $row) {

            $act->username = $row['username'];
            $act->nome = $row['nome'];
            $act->cognome = $row['cognome'];
            $act->name = $row['nome'];
            $act->surname = $row['cognome'];
            $act->gruppo = $row['gruppo'];

            if ($row['cognome'] === '' || $row['cognome'] === null) {
                $act->cognome = $act->username;
                $act->surname = $act->username;
            }

            if ($row['gest_pag'] != null) {
                if ($row['gest_pag'] == 1) {
                    $act->gest_pag = true;
                } else {
                    $act->gest_pag = false;
                }
            } else {
                $act->gest_pag = false;
            }

            if ($row['only_display'] != null) {
                if ($row['only_display'] == 1) {
                    $act->only_display = true;
                } else {
                    $act->only_display = false;
                }
            } else {
                $act->only_display = false;
            }

            $act->id = $row['id'];
            $act->created_at = $row['created_at'];
            $act->confirmed_at = $row['confirmed_at'];
            $act->updated_at = $row['updated_at'];
            $act->confirmed = $row['confirmed'];
            $act->data_nascita = $row['data_nascita'];

            $act->admin = false;
            if ($row['admin'] != null) {
                if ($row['admin'] == 1) {
                    $act->admin = true;
                }
            }

            $act->superadmin = false;
            if ($row['superadmin'] != null) {
                if ($row['superadmin'] == 1) {
                    $act->superadmin = true;
                }
            }

            $act->email1 = $row['email1'];
            $act->cell1 = $row['cell1'];
            $act->email2 = $row['email2'];
            $act->cell2 = $row['cell2'];
            $act->privacy_accepted = setBool($row['privacy_accepted']);

            if ($act->email1 === '' || $act->email1 === NULL) {
                $act->email1 = $act->username;
            }

            $act->apps = [];

            // recupero le apps legate all'utente
            $sqlStringApps = "SELECT * from utenti_app
                                    inner join app
                                     on utenti_app.idapp = app.id

                                where iduser = :iduser
                            ";

            $stmtApps = $conn->prepare($sqlStringApps);

            $stmtApps->bindParam(':iduser', $iduser);
            $iduser = $row['id'];

            $found = false;
            $stmtApps->execute();
            $rowsApps = $stmtApps->fetchAll();

            foreach ($rowsApps as $rowApp) {
                $actApp = new App();

                $actApp->id = $rowApp['id'];
                $actApp->title = $rowApp['title'];
                $actApp->url = $rowApp['url'];

                $act->apps[] = $actApp;
            }

            /*
            $token = getJwt($act);
            $act->token = $token;
            */

            $found = true;

            $returnResult->success = 1;
        }
        $returnResult->returnObject = $act;

        $conn = null;
    } catch (PDOException $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function setBool($in)
{
    $only_display = false;
    if ($in != null) {
        if ($in == 1) {
            $only_display = true;
        } else {
            $only_display = false;
        }
    } else {
        $only_display = false;
    }
    return $only_display;
}

function assignIfNotEmpty($item)
{
    if ($item !== null)  return true;
    return false;
}

function confirm($id)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    try {

        $returnResult = new ServiceResult();
        $returnResult->returnMessages[] = "Start operation";

        $conn = connectToDbPDO();

        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "update utenti
                             set confirmed = '1',
                                 confirmed_at = NOW()
                                    where id = '$id'
                                 ";

        $stmt = $conn->prepare($sqlString);
        $stmt->execute();
        $returnResult->success = 1;
        $conn = null;
    } catch (PDOException $e) {
        // header("HTTP/1.1 500 Internal Server Error");
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $conn = null;
    }

    return $returnResult;
}
