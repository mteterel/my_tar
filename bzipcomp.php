<?php

function bzipcomp()
{
	$fp = fopen("testbzip","r");
	$data = fread($fp, filesize("testbzip"));
	var_dump($data);
	$compressed = bzcompress($data);
	$compressed_file = bzopen("./bzipcompr","w");
	$my_file = bzwrite($compressed_file,$compressed);
	bzclose($compressed_file);
}
bzipcomp();

?>