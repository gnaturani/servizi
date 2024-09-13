<?php

use ReallySimpleJWT\Helper\DateTime;

require_once("../../dbconnect.php");
require_once("../../resultCalling.php");
require_once("../../commonOperations.php");
require_once("../../ziplib/src/zip.php");
require_once("jwt_burgazzi.php");
require_once("gruppo.php");

error_reporting(E_ERROR | E_PARSE);

function recuperaGruppo($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        /*
        error_log(print_r('recuperaGruppi Data:', true), 0);
        error_log(print_r($data, true), 0);
        */

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "Select *
                                            from ab_gruppi
                                            where id > 0
                    ";

        if ($data[idgruppo] !== null) {
            $sqlString = $sqlString . " and id = $data[idgruppo]";
        }

        error_log(print_r('sqlString Data:', true), 0);
        error_log(print_r($sqlString, true), 0);

        $stmtSearch = $conn->prepare($sqlString);
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();

        $alldata = array();
        foreach ($rows as $row) {

            $act = new Gruppo();
            $act->id = $row[id];
            $act->nome = $row[nome];
            $act->descrizione = $row[descrizione];
            $act->testo = $row[testo];
            $act->inizio = $row[inizio];
            $act->fine = $row[fine];
            $act->creato_il = $row[creato_il];
            $act->aggiornato_il = $row[aggiornato_il];

            if ($row[privacy_accepted] === 1) {
                $act->privacy_accepted = true;
            } else {
                $act->privacy_accepted = false;
            }

            $alldata[] = $act;
        }

        foreach ($alldata as $act) {
            $sqlString = "Select *
                    from ab_gruppi_files
                    where idgruppo = $act->id";

            error_log(print_r('sqlString Data:', true), 0);
            error_log(print_r($sqlString, true), 0);

            $stmtSearch = $conn->prepare($sqlString);
            $stmtSearch->execute();
            $rows = $stmtSearch->fetchAll();

            $act->files = [];
            foreach ($rows as $row) {
                $actFolder = new File();
                $actFolder->id = $row[id];
                $actFolder->url = $row[url];
                $actFolder->nome = $row[nome];
                $actFolder->is_folder = true;

                $actFolder->files = [];

                recuperaFilesFolder($actFolder->url, $actFolder);

                $act->files[] = $actFolder;
            }
        }


        error_log(print_r('alldata Data:', true), 0);
        error_log(print_r($alldata, true), 0);

        $returnResult->returnObject = $alldata[0];
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Condivisione recuperata!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        error_log(print_r('recuperaGruppo Err:', true), 0);
        error_log(print_r($e, true), 0);

        $conn = null;
    }

    return $returnResult;
}

function generaCodici($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;
    $alldata = [];

    try {

        error_log(print_r('generaCodici Data:', true), 0);
        error_log(print_r($data, true), 0);

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        for ($nr = 1; $nr <= $data[nr_codici]; ++$nr) {

            $codice = new Codice();
            $codice->nr_accessi = 0;
            $codice->nr_accessi_max = $data[nr_accessi];
            $codice->creato_il = date('Y-m-d H:i:sa');
            $codice->idgruppo = $data[idgruppo];
            $codice->code = Burgazzi_getJwt($codice);

            $stmtInsert = $conn->prepare("insert into ab_gruppi_codes
                            ( idgruppo,
                            code,
                            nr_accessi,
                            nr_accessi_max,
                            creato_il
                            )
                            VALUES (
                                :idgruppo,
                                :code,
                                0,
                                :nr_accessi_max,
                                NOW()
                            )
                ");

            $stmtInsert->bindParam(':idgruppo', $idgruppo);
            $stmtInsert->bindParam(':nr_accessi_max', $nr_accessi_max);
            $stmtInsert->bindParam(':code', $code);

            $idgruppo = $codice->idgruppo;
            $nr_accessi_max = $codice->nr_accessi_max;
            $code = $codice->code;

            $stmtInsert->execute();
            $codice->id = $conn->lastInsertId();

            $alldata[] = $codice;
        }
        $returnResult->success = 1;
        $returnResult->result = 1;
        $returnResult->returnObject = $alldata;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Codici generati!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaImpostazioni($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;

    error_log(print_r('recuperaImpostazioni Data:', true), 0);
    error_log(print_r($data, true), 0);

    $chiave = null;
    try {
        $chiave = $data[chiave];
    } catch (Exception $Err) { }

    try {

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtSearch = $conn->prepare("Select *
                                from impostazioni
                    ");

        if ($chiave !== null) {
            $stmtSearch = $conn->prepare("Select *
                                from impostazioni
                                where chiave = '$chiave'
                    ");
        }

        $alldata = array();
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();
        foreach ($rows as $row) {

            $act = new Setting();

            $act->id = $row['id'];
            $act->chiave = $row['chiave'];
            $act->etichetta = $row['etichetta'];
            $act->contenuto = $row['contenuto'];
            $act->tipo = $row['tipo'];
            $alldata[] = $act;
        }

        $returnResult->returnObject = $alldata;
        $returnResult->success = 1;
        $returnResult->result = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Impostazioni recuperate!";
        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function recuperaCartellaFiles()
{
    $hostport = $_SERVER[HTTP_HOST];
    if ($hostport == "localhost:8888") {
        return '../../../../../../Projects/Asilo Burgazzi/burgazzi_files';
    } else {
        return $_SERVER['DOCUMENT_ROOT'] . '/burgazzi_files/';
    }
}

function recuperaCartellaZip()
{
    $hostport = $_SERVER[HTTP_HOST];
    if ($hostport == "localhost:8888") {
        return '../../../../../../Projects/Asilo Burgazzi/burgazzi_zip';
    } else {
        return $_SERVER['DOCUMENT_ROOT'] . '/burgazzi_zip/';
    }
}

function recuperaFilesFolder($path, $folderIn)
{

    error_log(print_r('path:', true), 0);
    error_log(print_r($path, true), 0);

    error_log(print_r('folderIn:', true), 0);
    error_log(print_r($folderIn, true), 0);

    $alldata = array_diff(scandir($path), array('.', '..', '.DS_Store'));

    foreach ($alldata as $act) {

        try {
            if (is_dir($path . '/' . $act)) {

                error_log(print_r('folder:', true), 0);
                error_log(print_r($act, true), 0);

                $folder = new File();
                $folder->url = $path . '/' . $act;
                $folder->name = $act;
                $folder->nome = $act;
                $folder->is_folder = true;
                $folder->is_file = false;
                $folderIn->files[] = $folder;

                recuperaFilesFolder($folder->url, $folder);
            } else {
                $file = new File();
                $file->url = $folderIn->url . '/' . $act;
                $file->name = $act;
                $file->nome = $act;
                $file->is_folder = false;
                $file->is_file = true;
                $file->type_complete = mime_content_type($file->url);
                if (strpos($file->type_complete, 'image') !== false) {
                    $file->type = 'image';
                } else
                if (strpos($file->type_complete, 'video') !== false) {
                    $file->type = 'video';
                } else {
                    $file->type = $file->type_complete;
                }

                $folderIn->files[] = $file;
            }
        } catch (Exception $e) {
            error_log(print_r('Err:', true), 0);
            error_log(print_r($e, true), 0);
        }
    }
    return $folderIn;
}


function aggiornaPrivacyCodice($data)
{
    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('aggiornaPrivacyCodice Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtUpdate = $conn->prepare("update ab_gruppi_codes
                                set privacy = 1
                                where code = :code
                                and idgruppo = :idgruppo
                    ");

        $stmtUpdate->bindParam(':code', $code);
        $stmtUpdate->bindParam(':idgruppo', $idgruppo);

        $idgruppo = $data[idgruppo];
        $code = $data[code];
        $stmtUpdate->execute();

        $data[privacy_accepted] = true;

        $returnResultUpd = recuperaCodice($data);
        $act = $returnResultUpd->returnObject;

        error_log(print_r('recuperaCodice result:', true), 0);
        error_log(print_r($returnResultUpd, true), 0);

        $token = Burgazzi_getJwt($act);
        $act->token = $token;
        $returnResult->returnObject = $act;

        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "codice aggiornato!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}

function recuperaCodice($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        /*
        error_log(print_r('recuperaGruppi Data:', true), 0);
        error_log(print_r($data, true), 0);
        */

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "Select *
                                            from ab_gruppi_codes
                                            where id > 0
                    ";

        if ($data[id] !== null) {
            $sqlString = $sqlString . " and id = $data[id]";
        }

        if ($data[code] !== null) {
            $sqlString = $sqlString . " and code = '$data[code]'";
        }

        error_log(print_r('sqlString Data:', true), 0);
        error_log(print_r($sqlString, true), 0);

        $stmtSearch = $conn->prepare($sqlString);
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();

        $alldata = array();
        foreach ($rows as $row) {

            $act = new Codice();
            $act->id = $row[id];
            $act->idgruppo = $row[idgruppo];
            $act->code = $row[code];
            $act->nr_accessi = $row[nr_accessi];
            $act->nr_accessi_max = $row[nr_accessi_max];
            $act->privacy = $row[privacy];
        }

        $returnResult->returnObject = $act;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Codice recuperato!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaImmagine($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        $exif = exif_read_data($data[url]);
        $b64image = base64_encode(file_get_contents($data[url]));

        if(!empty($exif['Orientation'])) {

            $image_little = imagecreatefromstring(base64_decode($b64image));

            switch($exif['Orientation']) {
                case 8:
                    $image_little = imagerotate($image_little,90,0);
                    break;
                case 3:
                    $image_little = imagerotate($image_little,180,0);
                    break;
                case 6:
                    $image_little = imagerotate($image_little,-90,0);
                    break;
            }

            // start buffering
            ob_start();
            imagejpeg($image_little);
            $contents =  ob_get_contents();
            ob_end_clean();

            $b64image = base64_encode($contents);
        }

        $returnResult->returnObject = 'data:image/jpg;base64,' . $b64image;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Immagine recuperata!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function recuperaImmagineThumb($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        $b64image = base64_encode(file_get_contents($data[url]));
        // $b64image = 'data:image/jpg;base64,' + $b64image;

        $WIDTH                  = 200; // The size of your new image
        $HEIGHT                 = 200;  // The size of your new image
        $QUALITY                = 100; //The quality of your new image

        $exif = exif_read_data($data[url]);

        list($width_orig, $height_orig) = getimagesize($data[url]);

        $ratio =  $height_orig / $width_orig;
        $WIDTH = 200;
        $HEIGHT = $ratio * $WIDTH;

        /*
        error_log(print_r('width_orig Data:', true), 0);
        error_log(print_r($width_orig, true), 0);
        error_log(print_r('height_orig Data:', true), 0);
        error_log(print_r($height_orig, true), 0);
        */

        $theme_image_little = imagecreatefromstring(base64_decode($b64image));
        // error_log(print_r('theme_image_little Data:', true), 0);
        // error_log(print_r($theme_image_little, true), 0);

        $save_WIDTH = $WIDTH;
        $save_HEIGHT = $HEIGHT;

        $image_little = imagecreatetruecolor($WIDTH, $HEIGHT);

        // $org_w and org_h depends of your image, in your case, i guess 800 and 600
        imagecopyresampled($image_little, $theme_image_little, 0, 0, 0, 0, $WIDTH, $HEIGHT, $width_orig, $height_orig);

        if(!empty($exif['Orientation'])) {
            switch($exif['Orientation']) {
                case 8:
                    $image_little = imagerotate($image_little,90,0);
                    // $WIDTH = $save_HEIGHT;
                    // $HEIGHT = $save_WIDTH;
                    break;
                case 3:
                    $image_little = imagerotate($image_little,180,0);
                    break;
                case 6:
                    $image_little = imagerotate($image_little,-90,0);
                    // $WIDTH = $save_HEIGHT;
                    // $HEIGHT = $save_WIDTH;
                    break;
            }
        }

        // Thanks to Michael Robinson
        // start buffering
        ob_start();
        imagejpeg($image_little);
        $contents =  ob_get_contents();
        ob_end_clean();

        $b64image = base64_encode($contents);

        $returnResult->returnObject = 'data:image/jpg;base64,' . $b64image;
        // $returnResult->returnObject = $b64image;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Immagine recuperata!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        $conn = null;
    }

    return $returnResult;
}


function prepareDownloadFiles($data)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        $counter = 0;
        $singleFile = true;
        foreach ($data as $act) {
            if (is_dir($act)) {
                $singleFile = false;
            }
            $counter++;
        }

        if ($counter == 1 && $singleFile) {
            error_log(print_r('single File!', true), 0);
            foreach ($data as $act) {
                $onlyfilename = $act[url];

                error_log(print_r($act, true), 0);
            }
        } else {

            // $zip = new ZipArchive();
            $zip = new Zip();

            $onlyfilename = uniqid('bs_', true) . '.zip';
            $filename = recuperaCartellaZip() . '/' . $onlyfilename;

            $zip->zip_start($filename);
            error_log(print_r('file ZIP creato!', true), 0);

            foreach ($data as $act) {
                $dir = $act[url];
                // Create zip
                createZip($zip, $dir);
            }

            error_log(print_r('Chiudi file ZIP!', true), 0);
            // $zip->close();
            $zip->zip_end();
        }

        error_log(print_r('$onlyfilename: ' . $onlyfilename, true), 0);

        $returnResult->returnObject = $onlyfilename;
        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "File ZIP creato!";
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error ZIP: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;
    }

    return $returnResult;
}

// Create zip
function createZip($zip, $dir)
{
    try {

        if (is_dir($dir)) {

            error_log(print_r('aggiungi directory:', true), 0);
            error_log(print_r($dir, true), 0);

            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {


                    if ($file === '.' || $file === '..' || $file === '.DS_Store') { } else {
                        // If file
                        if (is_file($dir . '/' . $file)) {
                            if ($file != '' && $file != '.' && $file != '..') {

                                error_log(print_r('aggiungi file:', true), 0);
                                error_log(print_r($dir . '/' . $file, true), 0);

                                // $zip->addFile($dir . '/' . $file);
                                $zip->zip_add($dir . '/' . $file);
                            }
                        } else {
                            // If directory
                            if (is_dir($dir . '/' . $file)) {

                                if ($file != '' && $file != '.' && $file != '..') {

                                    /*
                                    // Add empty directory
                                    $zip->addEmptyDir($dir . '/' . $file);

                                    $folder = $dir . '/' . $file . '/';
                                    // Read data of the folder
                                    createZip($zip, $folder);
                                    */
                                    $zip->zip_add($dir . '/' . $file);

                                }
                            }
                        }
                    }
                }
                closedir($dh);
            }
        } else {
            // $zip->addFile($dir);

            error_log(print_r('aggiungi file', true), 0);
            $zip->zip_add($dir);
        }

    } catch (Exception $e) {

        error_log(print_r('ERRORE', true), 0);
        error_log(print_r($e, true), 0);
    }
}

function inviaLinkViaMail($data)
{
    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    // error_log(print_r('inviaLinkViaMail Data:', true), 0);
    // error_log(print_r($data, true), 0);

    $hostport = $_SERVER[HTTP_HOST];
    if ($hostport == "localhost:8888") {
        $url = 'http://localhost:4200/?token=' . $data[token];
    } else {
        $url = 'https://www.parrocchiacarpaneto.com/burgazzi_share/?token=' . $data[token];
    }

    try {
        $oggetto = 'Bugazzi Share: Link';

        $messaggio = 'Eccoti il link per accedere allo share della Scuola Materna Burgazzi!';
        $messaggio .= '<br><a href="' . $url . '">Apri Burgazzi Share!</a>';

        $mailsto = [];
        $mailsto[] = $data[mail];

        // Genera un boundary
        $mail_boundary = "=_NextPart_" . md5(uniqid(time()));

        // $to = $mailsto[0];
        $subject = $oggetto;
        $sender = "postmaster@parrocchiacarpaneto.com";

        $headers = "From: $sender\n";
        $headers .= "MIME-Version: 1.0\n";
        $headers .= "Content-Type: multipart/alternative;\n\tboundary=\"$mail_boundary\"\n";
        $headers .= "X-Mailer: PHP " . phpversion();

        $msg = '';
        $msg .= "--$mail_boundary\n";
        $msg .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
        $msg .= "Content-Transfer-Encoding: 8bit\n\n";
        $msg .= $messaggio; // aggiungi il messaggio in formato HTML

        // Boundary di terminazione multipart/alternative
        $msg .= "\n--$mail_boundary--\n";

        // Imposta il Return-Path (funziona solo su hosting Windows)
        ini_set("sendmail_from", $sender);

        foreach ($mailsto as $to) {

            if ($to !== null && $to !== undefined && $to !== '') {

                // Invia il messaggio, il quinto parametro "-f$sender" imposta il Return-Path su hosting Linux
                if (mail($to, $subject, $msg, $headers, "-f$sender")) {
                    // echo "Mail inviata correttamente !<br><br>Questo di seguito è il codice sorgente usato per l'invio della mail:<br><br>";
                    // highlight_file($_SERVER["SCRIPT_FILENAME"]);
                    // unlink($_SERVER["SCRIPT_FILENAME"]);
                    $returnResult->success = 1;
                    $returnResult->result = 1;
                    $returnResult->returnMessages = ["Mail Inviata!"];
                } else {
                    // echo "<br><br>Recapito e-Mail fallito!";
                    $returnResult->success = 0;
                    $returnResult->result = 0;
                }
            }
        }
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;
    }

    return $returnResult;
}


function checkNumeroAccessi($data)
{
    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('checkNumeroAccessi Data:', true), 0);
        error_log(print_r($data, true), 0);

        $notSave = false;
        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "Select * from ab_gruppi_codes
                                            where id > 0
                           and code = '$data'";

        error_log(print_r('sqlString Data:', true), 0);
        error_log(print_r($sqlString, true), 0);

        $stmtSearch = $conn->prepare($sqlString);
        $stmtSearch->execute();
        $rows = $stmtSearch->fetchAll();

        $alldata = array();
        foreach ($rows as $row) {

            $act = new Codice();
            $act->id = $row[id];
            $act->idgruppo = $row[idgruppo];
            $act->code = $row[code];
            $act->nr_accessi = $row[nr_accessi];
            $act->nr_accessi_max = $row[nr_accessi_max];

            $act->last_access = $row[last_access];
            $lastAccess = $row[last_access];

            $lastAccessDate = substr($lastAccess, 0, 10);

            $todayDate = date("Y-m-d");

            if ($act->nr_accessi > $act->nr_accessi_max) {
                $returnResult->returnObject = $act;
                $returnResult->success = 0;
                $returnResult->returnMessages = [];
                $returnResult->returnMessages[] = "Superato il limite di Accessi Consentiti!";
                $conn = null;
                return $returnResult;
            }

            if ($todayDate === $lastAccessDate) {

                aggiornaDataAccesso($data);

                $returnResult->returnObject = $act;
                $returnResult->success = 1;
                $returnResult->returnMessages = [];
                $returnResult->returnMessages[] = "Accesso giornaliero!";
            } else {

                $act->nr_accessi = $act->nr_accessi + 1;
                incrementaNumeroAccessi($data, $act->nr_accessi);

                if ($act->nr_accessi <= $act->nr_accessi_max) {

                    $returnResult->returnObject = $act;
                    $returnResult->success = 1;
                    $returnResult->returnMessages = [];
                    $returnResult->returnMessages[] = "Nuovo Accesso!";
                } else {
                    $returnResult->returnObject = $act;
                    $returnResult->success = 0;
                    $returnResult->returnMessages = [];
                    $returnResult->returnMessages[] = "Superato il limite di Accessi Consentiti!";
                }
            }
        }

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        error_log(print_r('checkNumeroAccessi Exception:', true), 0);
        error_log(print_r($returnResult, true), 0);

        $conn = null;
    }

    error_log(print_r('checkNumeroAccessi End:', true), 0);
    error_log(print_r($returnResult, true), 0);

    return $returnResult;
}


function incrementaNumeroAccessi($data, $nr_accessi)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('incrementaNumeroAccessi Data:', true), 0);
        error_log(print_r($data . ' ' . $nr_accessi, true), 0);

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "update ab_gruppi_codes
                            set nr_accessi = '$nr_accessi',
                                last_access = NOW()
                        where id > 0
                    and code = '$data'";
        $stmtUpdate = $conn->prepare($sqlString);
        $stmtUpdate->execute();

        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Aggiornato numero Accessi!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;


        error_log(print_r('incrementaNumeroAccessi Exception:', true), 0);
        error_log(print_r($e->getMessage(), true), 0);

        $conn = null;
    }

    return $returnResult;
}


function aggiornaDataAccesso($code)
{

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    try {

        error_log(print_r('aggiornaDataAccesso Data:', true), 0);
        error_log(print_r($code, true), 0);

        $conn = connectToDbPDO();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlString = "update ab_gruppi_codes
                            set last_access = NOW()
                        where id > 0
                    and code = '$code'";
        $stmtUpdate = $conn->prepare($sqlString);
        $stmtUpdate->execute();

        $returnResult->success = 1;
        $returnResult->returnMessages = [];
        $returnResult->returnMessages[] = "Aggiornata Data di Accesso!";

        $conn = null;
    } catch (Exception $e) {
        $returnResult->returnMessages[] = "Error: " . $e->getMessage();
        $returnResult->success = 0;
        $returnResult->result = 0;

        error_log(print_r('aggiornaDataAccesso Exception:', true), 0);
        error_log(print_r($e->getMessage(), true), 0);

        $conn = null;
    }

    return $returnResult;
}
