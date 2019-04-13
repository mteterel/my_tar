<?php

function decompression()
{
$fp = fopen("compressed_file","r");
$data = fread($fp, filesize("compressed_file"));
$dcomp = gzuncompress($data);
var_dump($dcomp);
$dcomp_file = fopen("./test3","w");
$my_file = fwrite($dcomp_file,$dcomp);

}

decompression();

?>