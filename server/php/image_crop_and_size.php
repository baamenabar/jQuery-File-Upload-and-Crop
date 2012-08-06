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

$thePicture = substr( $upload_handler->getOption('relative_upload_dir'). urldecode(array_pop(array_splice(explode('/',$_POST['source']['file']),-1))),1);
if (!is_file($thePicture)) echo 'ERROR: Image file not found';

$size=getimagesize($thePicture);
$sizeRatio=$size[0]/$_POST['source']['width'];

$newFile = $lii->genImage(array(
	'url'=>$thePicture,
	'width'=>$_POST['source']['endWidth'],
	//'height'=>$_POST['source']['endHeight'],
	'oc' => '1',
	'cx' => floor($_POST['c']['x']*$sizeRatio),
	'cy' => floor($_POST['c']['y']*$sizeRatio),
	'cw' => floor($_POST['c']['w']*$sizeRatio),
	'ch' => floor($_POST['c']['h']*$sizeRatio)
	));

echo json_encode(array('newFile'=>$newFile));
?>