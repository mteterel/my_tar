<?php

function bzipdcompr()
{
	$fp = bzopen("bzipcompr","r");
	$data = bzread($fp, filesize("bzipcompr"));
	$dcomp = bzdecompress($data);
	#var_dump($dcomp);
	$dcomp_file = fopen("./test4","w");
	$my_file = fwrite($dcomp_file,$dcomp);
	#echo $my_file;
	fclose($dcomp_file);
}

bzipdcompr();
?>