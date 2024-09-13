<?php
header('Access-Control-Allow-Origin: *'); 
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');

$target_path = "uploads/";
 
$target_path = $target_path . basename( $_FILES['file']['name']);
echo $target_path;
echo "<br>";
 
if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
    echo "Upload and move success";
} else {
    echo $target_path;
    echo "There was an error uploading the file, please try again!";
}
?>
