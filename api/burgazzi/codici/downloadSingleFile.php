<?php

require_once("operations.php");

$filename = htmlspecialchars($_GET["url"]);

// $filename = recuperaCartellaZip() . '/' . $filename;

error_log(print_r('file da scaricare:', true), 0);
error_log(print_r($filename, true), 0);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($filename));

flush();
readfile($filename);
// delete file
// unlink($filename);

error_log(print_r('end download single File!', true), 0);
