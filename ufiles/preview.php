<?php
// Define the upload directory, this should be consistent with uploader.php
$uploadDirectory = "C:\Users\Public";

// Get filename and prevent directory traversal attacks
$prieveFile = basename($_GET['filename']);
$filePath = $uploadDirectory . '\\Uploads' . $prieveFile;

//echo $filePath;//debug

// Check that the file exists in the upload directory
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found.');
}

// Use pathinfo to get file extension, this is safer and avoids the reference error
$fileExt = strtolower(pathinfo($prieveFile, PATHINFO_EXTENSION));

//echo $fileExt; //debug
 //for images
$images = ['jpeg','jpg','png'];
$excel =['xlsx','xlsm','xlsb','xls','xlam'];
$pdf = ['pdf'];
$word = ['docx','docb','doc'];
$txt = ['txt'];

//getcwd();





 if(in_array($fileExt,$images)){
     
    $files = file_get_contents($filePath);
    header("Content-type: image/jpeg");
    //Header("Content-type: application/vnd.ms-paint");


    echo $files;

 }elseif(in_array($fileExt,$excel)){
    $files = file_get_contents($filePath);

    Header("Content-type: application/vnd.ms-excel");

    echo $files;

 }elseif(in_array($fileExt,$word)){

    $files = file_get_contents($filePath);
    Header("Content-type: application/vnd.ms-word");

    echo $files;

    //echo '<iframe src="http://docs.google.com/gview?url='.$files.'&embedded=true" style="width:90%; height:auto;" frameborder="0"></iframe>';
 }elseif(in_array($fileExt,$pdf)){
    $files = file_get_contents($filePath);
    header("Content-type: application/pdf");
    echo $files;
 }elseif(in_array($fileExt,$txt)){
    $files = file_get_contents($filePath);
    header("Content-type: text/plain");
    echo $files;
 }
 
?>