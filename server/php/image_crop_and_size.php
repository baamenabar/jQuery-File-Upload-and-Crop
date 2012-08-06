<?php
include "LiquenImg.php";
require('upload.class.php');

$upload_handler = new UploadHandler();
$lii = new LiquenImg();


/*print_r($_POST);
echo '
<br>dire: '.substr( $upload_handler->getOption('relative_upload_dir'). urldecode(array_pop(array_splice(explode('/','http://localhost/jQuery-File-Upload-and-Crop/server/php/files/DSC02004%20-%20agaricus%20arvensis.JPG'),-1))),1).'
<br>isfile:'.is_file( substr( $upload_handler->getOption('relative_upload_dir'). urldecode(array_pop(array_splice(explode('/','http://localhost/jQuery-File-Upload-and-Crop/server/php/files/DSC02004%20-%20agaricus%20arvensis.JPG'),-1))),1) ).'
<br>';*/

$thePicture = substr( $upload_handler->getOption('relative_upload_dir'). urldecode(array_pop(array_splice(explode('/','http://localhost/jQuery-File-Upload-and-Crop/server/php/files/DSC02004%20-%20agaricus%20arvensis.JPG'),-1))),1);


?>