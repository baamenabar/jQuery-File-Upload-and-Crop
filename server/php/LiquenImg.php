<?php

/*****************************************************************************

Liquen Core Class: LiquenImg

******************************************************************************
MIT License (MIT)
Copyright (c) 2012 Agustin Amenabar

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

******************************************************************************/

/****************************

TO DO:

- error log and error reporting for the class.

*****************************/


class LiquenImg
{
	protected $cacheFolder = 'imgs/';// MUST have trailing slash
	protected $sourceFolder = 'upload/';// MUST have trailing slash
	protected $_errors=array();
	protected $_notices=array();

	protected $shorthand = array(
		'u' =>'url',
		'o' =>'outputFolder',
		'rn'=>'rename',
		'w' =>'width',
		'h' =>'height',
		'p' =>'percent',
		'b' =>'backgroundColor',
		'c' =>'crop',//if true enforces cropping
		'ct'=>'cropType',
		'ft'=>'fileType',
		'oc'=>'overwriteCached',
		'i' =>'interlaced',
		'q' =>'quality',
		'mw'=>'maxWidth',//legacy
		'mh'=>'maxHeight',//legacy
		'sq'=>'square',//legacy
		'ma'=>'max',//maximun side size
		'mi'=>'min',//minimun side size
		's' =>'short',//Short side should measure... this is an alias for min
		'l' =>'long',//Long side should measure... this is an alias for max
		'cx'=>'cropRectangleX',
		'cy'=>'cropRectangleY',
		'cw'=>'cropRectangleWidth',
		'ch'=>'cropRectangleHeight',
		'p' =>'padding',//not yet developed
		'pt'=>'paddingTop',//not yet developed
		'pr'=>'paddingRight',//not yet developed
		'pb'=>'paddingBottom',//not yet developed
		'pl'=>'paddingLeft'//not yet developed
		);

	protected $defaults = array(
		'url'=>NULL,
		'outputFolder'=>NULL,//re-defines the cacheFolder. Must be relative to instanciation of class.
		'rename'=>true,//if we want to mantain the name of the file (potentially rewriting it) pass the atribute as 'false'
		'width'=>0,
		'height'=>0,
		'percent'=>100,
		'backgroundColor'=>'0xFFFFFF',//RGB+Alpha in the following format: #rrggbbaa//could not use hashtag notation due to it being written in the URL string
		'crop'=>1,//cropping is default to true
		'cropType'=>'cc',//options: no-crop(will fill with background),[t|c|b][l|c|r]
		'fileType'=>'j',//options p:png j:jpg g:gif
		'overwriteCached'=>false,
		'interlaced'=>0,
		'quality'=>90,
		'maxWidth'=>0,
		'maxHeight'=>0,
		'square'=>0,
		'max'=>0,
		'min'=>0,
		'short'=>0,
		'long'=>0,
		'cropRectangleX'=>0,
		'cropRectangleY'=>0,
		'cropRectangleWidth'=>0,
		'cropRectangleHeight'=>0,
		'padding'=>0,//not yet developed
		'paddingTop'=>0,//not yet developed
		'paddingRight'=>0,//not yet developed
		'paddingBottom'=>0,//not yet developed
		'paddingLeft'=>0//not yet developed
		);

	protected $fileTypesShorthand=array(
		'j'=>'jpg',
		'p'=>'png',
		'g'=>'gif'
		);
	protected $options;

	protected $configsDefaults = array(
	'extension' => 'jpg',
	'width' => 0,
	'height' => 0,
	'srcX' => 0,
	'srcY' => 0,
	'srcWidth' => 0,
	'srcHeight' => 0,
	'cRectangle' => false,

	'endX' => 0,
	'endY' => 0,
	'endWidth' => 0,
	'endHeight' => 0,
	'dst_width' => 0,
	'dst_height' => 0,
	'quality' => 0,
	'interlace' => 0,
	'background' => 0,
	'srcImage' => 0
	);
	protected $cachedFile;
	protected $configs;

	function __construct(array $dat = NULL)
	{
		error_reporting(E_ALL);
		$this->getConfig();
		if($dat!=NULL && count($dat)){
			$this->options = $dat;
		}elseif(isset($_GET) && count($_GET) && (isset($_GET['u']) || isset($_GET['url']))){
			$this->options = $_GET;
		}else{
			return;
		}
		$this->init();
		$this->processImage();
	}

	public function getCacheFolder(){return $this->cacheFolder;}
	public function getSourceFolder(){return $this->sourceFolder;}

	public function genImage(array $dat, $absoluteURL=false){
		$this->configs = $this->configsDefaults;//resets configs
		if(isset($dat) && count($dat)){
			$this->options = $dat;
		}else{
			return false;
		}
		$respuestaInit = $this->init();
		if($respuestaInit === false){
			return false;
		}elseif ($respuestaInit) {
			return $respuestaInit;
		}
		if($this->processImage() === false)return false;
		if(is_file($this->cachedFile)){
			if ($absoluteURL) {
				return $this->absoluteUrlCachedFile();
			}
			return $this->cachedFile;
		}
		return false;
	}

	protected function getConfig(){
		if(is_file('config_liquen.ini')){
			$config = parse_ini_file('config_liquen.ini');
			if(!$config)return;
			if(isset($config['cacheFolder']) && $config['cacheFolder'])		$this->cacheFolder	=	$config['cacheFolder'];
			if(isset($config['sourceFolder']) && $config['sourceFolder'])	$this->sourceFolder	=	$config['sourceFolder'];

			if(substr($this->cacheFolder,-1)!='/')$this->cacheFolder.='/';
			if(substr($this->sourceFolder,-1)!='/')$this->sourceFolder.='/';
		}
	}

	protected function init(){
		//Normalize shorthand parameters
		foreach ($this->shorthand as $key => $value){
			if( !isset( $this->options[$value] ) && isset( $this->options[$key] ) ){
				$this->options[$value] = $this->options[$key];
			}
		}

		//Verify the file exists
		if(!is_file($this->sourceFolder.$this->options['url'])){
			/*echo '<br>
			'.$this->options['url'].'es acrchivo?'.is_file($this->options['url']).' esta slash en:'.strpos($this->options['url'], '/');//*/
			if(strpos($this->options['url'], '/') !== false && is_file(''.$this->options['url'])){
				$this->sourceFolder='';
			}else{
				/*echo '<br>
				no encontramos el archivo: '.$this->sourceFolder.$this->options['url'];//*/
				return false;//exit('File not found.');
			}
		}

		//define output extension
		if( isset($this->options['fileType']) )$this->configs['extension'] = $this->fileTypesShorthand[substr($this->options['fileType'], 0, 1)];

		//Get name of file
		$name = explode( "/" , $this->options['url'] );
		$name = $name[( sizeof( $name ) - 1 )];

		//if set verify output folder.
		if (isset($this->options['outputFolder']) && $this->options['outputFolder']) {
			if( is_dir($this->options['outputFolder']) ){
				if(substr($this->options['outputFolder'],-1)!='/')$this->options['outputFolder'].='/';
				$this->cacheFolder = $this->options['outputFolder'];
			}
		}

		//build cache name and check if already on cache
		$this->cachedFile=array();
		if( isset($this->options['rename']) && ( $this->options['rename'] === 'false' || !$this->options['rename'] ) ){// if we want to mantain the name of the file (potentially rewriting it)
			$name_no_extension = explode('.', $name);
			array_splice($name_no_extension, -1);//remove the src extension
			$this->cachedFile[]=implode('.', $name_no_extension);
		} else{
			foreach ($this->shorthand as $key => $value){
				if($key == 'u'){
					$this->cachedFile[] = $name;
				}elseif (isset($this->options[$value]) && $key!='oc' && $key!='o' && $key!='rn') {
					$this->cachedFile[]=$key.$this->options[$value];
				}
			}
		}
		$this->cachedFile = $this->cacheFolder.implode('_', $this->cachedFile).'.'.$this->configs['extension'];
		if(is_file($this->cachedFile) && (!isset($this->options['overwriteCached']) || !$this->options['overwriteCached'])){
			return $this->cachedFile;
		}

		//Size and type of the file
		$dimentionsAndTYpe = getimagesize( $this->sourceFolder.$this->options['url'] );
		$this->configs['width'] = $dimentionsAndTYpe[0];
		$this->configs['height'] = $dimentionsAndTYpe[1];
		$type = image_type_to_mime_type($dimentionsAndTYpe[2]);

		//set defaults
		if(isset($this->options['crop']) && $this->options['crop']==='false')$this->options['crop']=0;
		if(isset($this->options['rename']) && $this->options['rename']==='false')$this->options['rename']=false;
		if(isset($this->options['short']) && !isset($this->options['min']))$this->options['min'] = $this->options['short'];
		if(isset($this->options['long']) && !isset($this->options['max']))$this->options['max'] = $this->options['long'];
		foreach ($this->defaults as $key => $value)if( !isset( $this->options[$key] ) )$this->options[$key]=$value;

		switch( $type )
		{
			case 'image/jpeg':
				$loadedImage = imagecreatefromjpeg( $this->sourceFolder.$this->options['url'] );
				break;
			case 'image/gif':
				$loadedImage = imagecreatefromgif( $this->sourceFolder.$this->options['url'] );
				break;
			case 'image/png':
				$loadedImage = imagecreatefrompng( $this->sourceFolder.$this->options['url'] );
				break;
			case 'image/bmp' || 'image/x-ms-bmp':
				$loadedImage = $this->imagecreatefrombmp( $this->sourceFolder.$this->options['url'] );//imagecreatefrombmp is VERY processor intensive and is the fastest I found, use only if needed, it takes it's toll on my 6 core AMD Phenom
				break;
		}

		//Quality
		$this->quality = $this->options['quality'];

		//Interlace
		$this->interlace = false;
		if( $this->options['interlaced'] == '1' )$this->interlace = true;

		//cropping Rectangle
		if( $this->options['cropRectangleWidth']>0 ){
			$this->configs['cRectangle'] = new stdClass();
			$this->configs['cRectangle']->x = $this->options['cropRectangleX'];
			$this->configs['cRectangle']->y = $this->options['cropRectangleY'];
			$this->configs['cRectangle']->width = $this->options['cropRectangleWidth'];
			$this->configs['cRectangle']->height = $this->options['cropRectangleHeight'];
		}

			
		//origin point
		$currentAspectRatio = $this->configs['width'] / $this->configs['height'];

		//Make a Square
		if($this->options['square'])
		{
			$this->configs['endWidth'] = $this->options['square'];
			$this->configs['endHeight'] = $this->options['square'];
			if( $this->configs['width'] >= $this->configs['height'] ){
				$this->configs['width'] = $this->configs['height'];//here the crop is defined
			}else{
				$this->configs['height'] = $this->configs['width'];
			}
				
		}

		//Percent Change
		elseif( $this->options['percent'] != 100 )
		{
			$this->configs['endWidth'] = ( $this->options['percent'] / 100 ) * $this->configs['width'];
			$this->configs['endHeight'] = ( $this->options['percent'] / 100 ) * $this->configs['height'];
		}

		//Width change
		elseif(  $this->options['width'] && !$this->options['height'] )
		{
			$this->configs['endWidth'] = $this->options['width'];
			$this->configs['endHeight'] = ( $this->configs['height'] * $this->configs['endWidth'] ) / $this->configs['width'];
		}

		//Height change
		elseif(  $this->options['height'] && !$this->options['width'] )
		{
			$this->configs['endHeight'] = $this->options['height'];
			$this->configs['endWidth'] = ( $this->configs['width'] * $this->configs['endHeight'] ) / $this->configs['height'];
		}

		//defines a limit for both width AND height without cropping
		elseif( (  $this->options['maxHeight'] ) && (  $this->options['maxWidth'] ) )
		{
			$newAspectRatio = $this->options['maxWidth'] / $this->options['maxHeight'];
			if( $newAspectRatio < $currentAspectRatio ){
				$this->configs['endWidth'] = $this->options['maxWidth'];
				$this->configs['endHeight'] = $this->configs['endWidth'] / $currentAspectRatio;
			}else{
				$this->configs['endHeight'] = $this->options['maxHeight'];
				$this->configs['endWidth'] = $this->configs['endHeight'] * $currentAspectRatio;
			}
		}
		//set the length for the longest side
		elseif ( $this->options['max'] ) {
			if ($this->configs['height'] > $this->configs['width']) {
				$this->configs['endHeight'] = $this->options['max'];
				$this->configs['endWidth'] = $this->configs['endHeight'] * $currentAspectRatio;
			} else {
				$this->configs['endWidth'] = $this->options['max'];
				$this->configs['endHeight'] = $this->configs['endWidth'] / $currentAspectRatio;
			}
			
		}
		//set the length for the shortest side
		elseif ( $this->options['min'] ) {
			if ($this->configs['height'] < $this->configs['width']) {
				$this->configs['endHeight'] = $this->options['min'];
				$this->configs['endWidth'] = $this->configs['endHeight'] * $currentAspectRatio;
			} else {
				$this->configs['endWidth'] = $this->options['min'];
				$this->configs['endHeight'] = $this->configs['endWidth'] / $currentAspectRatio;
			}
		}
		//Width AND height change with probable cropping
		elseif(  $this->options['height'] && $this->options['width'] ){
			$this->configs['endHeight'] = $this->options['height'];
			$this->configs['endWidth'] = $this->options['width'];
		}
		// Cropping rectangle
		else if( $this->configs['cRectangle'] ){
			$this->configs['endHeight'] = $this->configs['cRectangle']->height;
			$this->configs['endWidth'] = $this->configs['cRectangle']->width;
		}else{
			return false;
		}
		$newAspectRatio = $this->configs['endWidth'] / $this->configs['endHeight'];
		if (floor($newAspectRatio*1000000) == floor($currentAspectRatio*1000000)) {//if aspect ratios are approximately the same: crop from top-left
			$this->options['cropType']='tl';
		}

		$this->configs['srcWidth']=$this->configs['width'];
		$this->configs['srcHeight']=$this->configs['height'];

		$this->configs['dst_width']=$this->configs['endWidth'];
		$this->configs['dst_height']=$this->configs['endHeight'];
		
		if (!$this->configs['cRectangle']) {
			//calculate the origin point according to the crop type
			if($this->options['crop']){
				if(strlen($this->options['cropType'])!=2){//if cropType is badly formed it defaults to cc
					$this->options['cropType']='cc';
				}
				$this->options['cropType']=str_split($this->options['cropType']);
				if($currentAspectRatio >= $newAspectRatio){//src image is proportionaly wider than the target size
					$this->configs['width']=$this->configs['height']*$newAspectRatio;
				}else{
					$this->configs['height']=$this->configs['width']/$newAspectRatio;
				}
				
				if($this->options['cropType'][1]=='c'){//horizontal
					if($currentAspectRatio >= $newAspectRatio)$this->configs['srcX']=floor(($srcWidth-$this->configs['width'])/2);
				}else if($this->options['cropType'][1]=='r'){
					if($currentAspectRatio >= $newAspectRatio)$this->configs['srcX']=floor(($srcWidth-$this->configs['width']));
				}

				if($this->options['cropType'][0]=='c'){//vertical
					if($currentAspectRatio < $newAspectRatio)$this->configs['srcY']=floor(($srcHeight-$this->configs['height'])/2);
				}else if($this->options['cropType'][0]=='b'){
					if($currentAspectRatio < $newAspectRatio)$this->configs['srcY']=floor(($srcHeight-$this->configs['height']));
				}
			}else{//cropping disabled is assumed
				if($currentAspectRatio >= $newAspectRatio){//src image is proportionaly wider than the target size
					$dst_height=$dst_width/$currentAspectRatio;
				}else{
					$dst_width=$dst_height*$currentAspectRatio;
				}
			}
		}
		
		if($this->configs['cRectangle']){
			$this->configs['srcX'] = $this->configs['cRectangle']->x;
			$this->configs['srcY'] = $this->configs['cRectangle']->y;
			$this->configs['width'] = $this->configs['srcWidth'] = $this->configs['cRectangle']->width;
			$this->configs['height'] = $this->configs['srcHeight'] = $this->configs['cRectangle']->height;
			//IF there's width set
			if(  $this->options['width'] && !$this->options['height'] )
			{
				$this->configs['endWidth'] = $this->options['width'];
				$this->configs['endHeight'] = ( $this->configs['cRectangle']->height * $this->configs['endWidth'] ) / $this->configs['width'];
			}

			//IF there's Height change
			elseif(  $this->options['height'] && !$this->options['width'])
			{
				$this->configs['endHeight'] = $this->options['height'];
				$this->configs['endWidth'] = ( $this->configs['cRectangle']->width * $this->configs['endHeight'] ) / $this->configs['height'];
			}

			//defines a limit for both width AND height without cropping
			elseif( (  $this->options['height'] ) && (  $this->options['width'] ) )
			{
				$this->configs['endWidth'] = $this->options['width'];
				$this->configs['endHeight'] = $this->options['height'];
			}
			
			$this->configs['dst_width'] = $this->configs['endWidth'];
			$this->configs['dst_height'] = $this->configs['endHeight'];
			
			if( (  $this->options['height'] ) && (  $this->options['width'] ) ){
				$newAspectRatio = $this->options['width'] / $this->options['height'];
				if( $newAspectRatio < $currentAspectRatio ){
					$this->configs['dst_width'] = $this->options['width'];
					$this->configs['dst_height'] = $this->configs['dst_width'] / $currentAspectRatio;
				}else{
					$this->configs['dst_height'] = $this->options['height'];
					$this->configs['dst_width'] = $this->configs['dst_height'] * $currentAspectRatio;
				}
				/*$this->configs['dst_width'] = $this->options['width'];
				$this->configs['dst_height'] = $this->options['height'];*/
			}
		}
		$this->srcImage=$loadedImage;
		$this->background=str_split(substr($this->options['backgroundColor'], 2),2);
		if(count($this->background) < 4)$this->background[]='FF';
	}

	protected function processImage(){
		$newImage = imagecreatetruecolor( $this->configs['endWidth'] , $this->configs['endHeight'] );
		imageinterlace( $newImage , $this->interlace );//activa o desactiva el bit de entrelazamiento con el segundo parámetro
		$backgroundColor = imagecolorallocatealpha($newImage, hexdec($this->background[0]), hexdec($this->background[1]), hexdec($this->background[2]), (((~((int)hexdec($this->background[3]))) & 0xff) >> 1));//The fifth parameter of imagecolorallocatealpha is a 7bit integer // $alpha7 = ((~((int)$alpha8)) & 0xff) >> 1;// http://php.net/manual/es/function.imagecolorallocatealpha.php
		imagefill($newImage, 0, 0, $backgroundColor);
		imagecopyresampled( $newImage , $this->srcImage , $this->configs['endX'] , $this->configs['endY'] , $this->configs['srcX'] , $this->configs['srcY'] , $this->configs['dst_width'] , $this->configs['dst_height'] , $this->configs['width'] , $this->configs['height'] );
		
		//header( "Content-Disposition: inline; filename=" . $name ); 
		//header( "Content-type: image/jpeg");// . $this->configs['extension'] );
		switch( $this->configs['extension'] )
		{
			case 'jpg':
				imagejpeg( $newImage , $this->cachedFile , $this->quality );
				break;
			case 'gif':
				imagegif( $newImage , $this->cachedFile , $this->quality );
				break;
			case 'png':
				imagepng( $newImage , $this->cachedFile , $this->quality );
				break;
		}
		imagedestroy( $newImage );
	}
	
	protected function absoluteUrlCachedFile(){
		/*echo dirname($_SERVER['PHP_SELF']).'/'.$this->cachedFile.'
		<br>';//*/
		if (is_file($this->cachedFile)) {
			$protocol = 'http';
			if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')$protocol = 'https';
			return $protocol.'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']).'/'.$this->cachedFile;
		}
		return $this->cachedFile;
	}

	/*********************************************/
	/* Fonction: ImageCreateFromBMP              */
	/* Author:   DHKold                          */
	/* Contact:  admin@dhkold.com                */
	/* Date:     The 15th of June 2005           */
	/* Version:  2.0B                            */
	/*********************************************/
	//http://php.net/manual/en/function.imagecreate.php

	private function ImageCreateFromBMP($filename){
		//Ouverture du fichier en mode binaire
		if (! $f1 = fopen($filename,"rb")) return FALSE;

		//1 : Chargement des ent�tes FICHIER
		$FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
		if ($FILE['file_type'] != 19778) return FALSE;

		//2 : Chargement des ent�tes BMP
		$BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
		             '/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
		             '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
		$BMP['colors'] = pow(2,$BMP['bits_per_pixel']);
		if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
		$BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
		$BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
		$BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
		$BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
		$BMP['decal'] = 4-(4*$BMP['decal']);
		if ($BMP['decal'] == 4) $BMP['decal'] = 0;

		//3 : Chargement des couleurs de la palette
		$PALETTE = array();
		if ($BMP['colors'] < 16777216)
		{
		$PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
		}

		//4 : Cr�ation de l'image
		$IMG = fread($f1,$BMP['size_bitmap']);
		$VIDE = chr(0);

		$res = imagecreatetruecolor($BMP['width'],$BMP['height']);
		$P = 0;
		$Y = $BMP['height']-1;
		while ($Y >= 0)
		{
		$X=0;
		while ($X < $BMP['width'])
		{
		 if ($BMP['bits_per_pixel'] == 24)
		    $COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
		 elseif ($BMP['bits_per_pixel'] == 16)
		 {  
		    $COLOR = unpack("n",substr($IMG,$P,2));
		    $COLOR[1] = $PALETTE[$COLOR[1]+1];
		 }
		 elseif ($BMP['bits_per_pixel'] == 8)
		 {  
		    $COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
		    $COLOR[1] = $PALETTE[$COLOR[1]+1];
		 }
		 elseif ($BMP['bits_per_pixel'] == 4)
		 {
		    $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
		    if (($P*2)%2 == 0) $COLOR[1] = ($COLOR[1] >> 4) ; else $COLOR[1] = ($COLOR[1] & 0x0F);
		    $COLOR[1] = $PALETTE[$COLOR[1]+1];
		 }
		 elseif ($BMP['bits_per_pixel'] == 1)
		 {
		    $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
		    if     (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
		    elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
		    elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
		    elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
		    elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
		    elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
		    elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
		    elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
		    $COLOR[1] = $PALETTE[$COLOR[1]+1];
		 }
		 else
		    return FALSE;
		 imagesetpixel($res,$X,$Y,$COLOR[1]);
		 $X++;
		 $P += $BMP['bytes_per_pixel'];
		}
		$Y--;
		$P+=$BMP['decal'];
		}

		//Fermeture du fichier
		fclose($f1);

		return $res;
	}
}

?>