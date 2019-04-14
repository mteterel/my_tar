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

function tar_calc_padding_size(int $block_len)
{
    return 512 - $block_len % 512;
}

function tar_write_padding(string &$stream, int $block_len)
{
    $padding_size = tar_calc_padding_size($block_len);
    $stream .= str_repeat("\x00", $padding_size);
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
    $part1 = @unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime",
        $stream, $offset);
    if (false == $part1)
        return false;

    $offset += 148 + 8; // Skip checksum

    $part2 = @unpack("a1/a100a6a2a32a32a8a8a155a12", $stream, $offset);
    if (false == $part2)
        return false;

    $offset += 356;
    return array_merge($part1, $part2);
}

function tar_unpack_single(string &$stream, int &$offset, string $out_dir, bool $force = false)
{
    if ($stream[$offset+1] == "\x00")
    {
        return 1337;
    }

    $orig_offset = $offset;
    $read_header = tar_read_header($stream, $offset);

    if (false == $read_header)
    {
        echo "Failed to read header of file\n";
        return false;
    }

    $orig_name = trim($read_header["name"]);
    $dest_name = $out_dir . $orig_name;

    if (file_exists($dest_name) && !$force)
    {
        $offset = $orig_offset;
        return -2;
    }

    $file_size = intval(trim($read_header["size"]), 8);
    $file_content = substr($stream, $offset, $file_size);

    if (false == $file_content)
    {
        echo "Missing data for file $orig_name\n";
        return false;
    }

    $offset += $file_size;
    $padding_size = tar_calc_padding_size($offset - $orig_offset);

    if (strlen($stream) < $offset + $padding_size)
    {
        echo "Data for file $dest_name is present, but there is no padding afterwards.\n";
        return false;
    }

    $offset += $padding_size;
    file_put_contents($dest_name, $file_content);

    echo "Extracted file $dest_name\n";
    return true;
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