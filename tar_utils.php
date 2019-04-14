<?php

const TAR_DIRTYPE = '5';
const TAR_FILETYPE = '0';
const TAR_USTAR_MAGIC = 'ustar ';
const TAR_USTAR_VER = '  ';

function tar_write_eof_padding(string &$stream)
{
    $stream .= str_repeat("\x00", 1024);
}

function tar_pack_single(string &$stream, string $file)
{
    $start_len = strlen($stream);

    tar_write_header($stream, $file);

    if (is_file($file))
    {
        $stream .= file_get_contents($file);
        $end_len = strlen($stream);
        $curr_block_len = $end_len - $start_len;
        tar_write_padding($stream, $curr_block_len);
    }
}

function tar_write_padding(string &$stream, $block_len)
{
    $padding_size = $block_len % 512;
    $stream .= str_repeat("\x00", 512 - $padding_size);
}

function tar_generate_header_p1(string $file)
{
    $stats = stat($file);
    $file_mode = sprintf("%07o", $stats['mode']);
    $file_uid = sprintf("%07o", $stats['uid']);
    $file_gid = sprintf("%07o", $stats['gid']);
    $file_mtime = sprintf("%011o", $stats['mtime']);
    $file_size = sprintf("%011o", $stats['size']);

    return pack("a100a8a8a8a12a12",
        $file,
        $file_mode,
        $file_uid,
        $file_gid,
        $file_size,
        $file_mtime);
}

function tar_generate_header_p2(string $file)
{
    $file_type = '0';
    $file_link_dest = '';

    if (is_dir($file))
        $file_type = TAR_DIRTYPE;
    elseif (is_file($file))
        $file_type = TAR_FILETYPE;

    return pack("a1a100a6a2a32a32a8a8a155a12",
        $file_type,
        $file_link_dest,
        TAR_USTAR_MAGIC,
        TAR_USTAR_VER,
        "", "", "", "", "", "");
}

function tar_write_header(string &$stream, $file)
{
    $header_part1 = tar_generate_header_p1($file);
    $header_part2 = tar_generate_header_p2($file);

    $checksum = tar_calc_header_crc($header_part1, $header_part2);
    $stream .= $header_part1;
    $stream .= sprintf("%08o", $checksum);
    $stream .= $header_part2;
}

function tar_read_header(string &$stream, int &$offset)
{
    $part1 = unpack("a100a8a8a8a12a12", $stream, $offset);
    if (false == $part1)
        return false;

    $offset += 148 + 8; // Skip checksum

    $part2 = unpack("a1a100a6a2a32a32a8a8a155a12", $stream, $offset);
    if (false == $part2)
        return false;

    return true;
}

function tar_read_single(int &$offset)
{
    //$parse_header = tar_read_header($stream, &$offset);
}

function tar_calc_header_crc(string $part1, string $part2)
{
    $checksum = 0;

    foreach(str_split($part1) as $c)
        $checksum += ord($c);

    for($i = 0; $i < 8; ++$i)
        $checksum += ord(' ');

    foreach(str_split($part2) as $c)
        $checksum += ord($c);

    return $checksum;
}