<?php

require_once("../lib/mail-signature.class.php");
require_once("../lib/mail-signature.config.php");

require_once("../lib/PHPMailer-5.2.26/PHPMailerAutoload.php");

class MailInfo
{
    public $oggetto;
    public $testo;
    public $mailsto;
}

function invioMail_OLD($mailsto, $oggetto, $messaggio)
{
    
    error_reporting(E_ALL);

    $msg = "";

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    // Genera un boundary
    $mail_boundary = "=_NextPart_" . md5(uniqid(time()));

    // $to = $mailsto[0];
    $subject = $oggetto;
    $sender = "postmaster@parrocchiacarpaneto.com";

    $headers = "From: $sender\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: multipart/alternative;\n\tboundary=\"$mail_boundary\"\n";
    $headers .= "X-Mailer: PHP " . phpversion();

    $msg .= "--$mail_boundary\n";
    $msg .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
    $msg .= "Content-Transfer-Encoding: 8bit\n\n";
    $msg .= $messaggio; // aggiungi il messaggio in formato HTML

    // Boundary di terminazione multipart/alternative
    $msg .= "\n--$mail_boundary--\n";

    // Imposta il Return-Path (funziona solo su hosting Windows)
    ini_set("sendmail_from", $sender);

    foreach ($mailsto as $to) {

        if ($to !== null && $to !== NULL && $to !== '') {

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
    return $returnResult;
}


function invioMail_V3($mailsto, $oggetto, $messaggio)
{
    

    error_reporting(E_ALL);

    $msg = "";

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    // Genera un boundary
    $mail_boundary = "=_NextPart_" . md5(uniqid(time()));

    // $to = $mailsto[0];
    $subject = $oggetto;
    $sender = "postmaster@parrocchiacarpaneto.com";

    $headers = "From: $sender\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: multipart/alternative;\n\tboundary=\"$mail_boundary\"\n";
    $headers .= "X-Mailer: PHP " . phpversion();

    $headers =
        'MIME-Version: 1.0
        From: "Sender" $sender
        Content-type: text/html; charset=utf8';

    $msg .= "--$mail_boundary\n";
    $msg .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
    $msg .= "Content-Transfer-Encoding: 8bit\n\n";
    $msg .= $messaggio; // aggiungi il messaggio in formato HTML

    // Boundary di terminazione multipart/alternative
    $msg .= "\n--$mail_boundary--\n";

    $msg = $messaggio;
    $msg = 123;

    // Imposta il Return-Path (funziona solo su hosting Windows)
    ini_set("sendmail_from", $sender);

    // print_r(MAIL_RSA_PRIV);

    $msg = preg_replace('/(?<!\r)\n/', "\r\n", $msg);
    $headers = preg_replace('/(?<!\r)\n/', "\r\n", $headers);

    foreach ($mailsto as $to) {

        if ($to !== null && $to !== NULL && $to !== '') {

            $signature = new mail_signature(
                MAIL_RSA_PRIV,
                MAIL_RSA_PASSPHRASE,
                MAIL_DOMAIN,
                MAIL_SELECTOR
            );
            $signed_headers = $signature->get_signed_headers($to, $subject, $msg, $headers);

            // print_r($signed_headers);
            
            // Invia il messaggio, il quinto parametro "-f$sender" imposta il Return-Path su hosting Linux
            if (mail($to, $subject, $msg, $signed_headers.$headers, "-f$sender")) {
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
    return $returnResult;
}


function invioMail($mailsto, $oggetto, $messaggio)
{
    
    date_default_timezone_set('Etc/UTC');

    $returnResult = new ServiceResult();
    $returnResult->result = 0;
    $returnResult->success = 0;

    //Create a new PHPMailer instance
    $mail = new PHPMailer;

    //Tell PHPMailer to use SMTP
    $mail->isSMTP();

    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 2;

    //Ask for HTML-friendly debug output
    $mail->Debugoutput = 'error_log';

    //Set the hostname of the mail server
    $mail->Host = 'smtp.gmail.com';
    // use
    // $mail->Host = gethostbyname('smtp.gmail.com');
    // if your network does not support SMTP over IPv6

    //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
    $mail->Port = 587;

    //Set the encryption system to use - ssl (deprecated) or tls
    $mail->SMTPSecure = 'tls';

    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;

    //Username to use for SMTP authentication - use full email address for gmail
    $mail->Username = "infoparrocchiacarpaneto@gmail.com";

    //Password to use for SMTP authentication
    $mail->Password = "qgwdgaaqfysxmjes";

    //Set who the message is to be sent from
    $mail->setFrom('infoparrocchiacarpaneto@gmail.com', 'Parrocchia Carpaneto');

    //Set an alternative reply-to address
    $mail->addReplyTo('info@parrocchiacarpaneto.com', 'Parrocchia Carpaneto');

    //Set who the message is to be sent to
    foreach ($mailsto as $to) {
        if ($to !== null && $to !== NULL && $to !== '') {
            $mail->addAddress($to, '');
        }
    }

    //Set the subject line
    $mail->Subject = $oggetto;

    //Read an HTML message body from an external file, convert referenced images to embedded,
    //convert HTML into a basic plain-text alternative body
    // $mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
    $mail->msgHTML($messaggio);

    //Replace the plain text body with one created manually
    $mail->AltBody = 'This is a plain-text message body';

    //send the message, check for errors
    if (!$mail->send()) {
        // echo "Mailer Error: " . $mail->ErrorInfo;

        $returnResult->success = 0;
        $returnResult->result = 0;
        $returnResult->returnMessages = [$mail->ErrorInfo];
    } else {
        // echo "Message sent!";

        $returnResult->success = 1;
        $returnResult->result = 1;
        $returnResult->returnMessages = ["Mail Inviata!"];
        //Section 2: IMAP
        //Uncomment these to save your message in the 'Sent Mail' folder.
        #if (save_mail($mail)) {
        #    echo "Message saved!";
        #}
    }    
    
    return $returnResult;

}

?>