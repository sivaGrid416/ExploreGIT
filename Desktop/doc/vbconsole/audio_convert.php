<?php


require('commonConfig.php');

$file_name = $_POST['voice_filename'];

$fileType = substr($file_name,-3);

$tmp = explode("-",$file_name);
$full_dt = $tmp[0];

$fileDateFind = explode("-",$file_name);
$fileDatePart = $fileDateFind[0];


$year = substr($fileDatePart,0,4);
$dt = substr($fileDatePart,6,2);
$monthNum = substr($fileDatePart,4,2);

$finalFilePath = "";
// $commandOutput = shell_exec('ffmpeg (or path to your ffmpeg file) -i file.wav file.mp3')
//echo $fileType;

if(strcmp($fileType,"gsm") == 0)
{
	$fileNameOrg = substr($file_name,0,-4);
	$wavFileName = "$fileNameOrg.wav";
	$output = shell_exec("/usr/bin/sox $targetpath$year/$monthNum/$dt/$file_name -r 44100 -c 1 -b 705 $targetpath"."convertedFiles/$wavFileName");
	$finalFilePath = "../RECORDINGS/convertedFiles/$wavFileName"; 
}
else
{
	$finalFilePath = "../RECORDINGS/$year/$monthNum/$dt/$file_name";
}


echo $finalFilePath;
?>
