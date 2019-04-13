<?php

function compression()
{
	$fp = fopen("test2","r");
	$data = fread($fp, filesize("test2"));
	var_dump($data);
	$compressed = gzcompress($data);
	$compressed_file = fopen("./compressed_file","w");
	$my_file = fwrite($compressed_file,$compressed);
}

compression("test2");
_
?>