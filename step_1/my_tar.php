<?php

require_once(dirname(__FILE__).'/../cli_utils.php');
require_once(dirname(__FILE__).'/../tar_utils.php');

function parse_args(int $argc, array $argv)
{
    $program_opts = [
        "compressed" => false,
        "out_file" => "output.tar",
        "files" => []
    ];

    for($i = 1; $i < $argc; ++$i)
    {
        switch ($argv[$i])
        {
            case "-o":
                $program_opts["out_file"] = $argv[++$i];
                break;
            case "-z":
                $program_opts["compressed"] = true;
                break;
            default:
                array_push($program_opts["files"], $argv[$i]);
                break;
        }
    }

    return $program_opts;
}

function pack_files(string &$stream, array $files)
{
    if (empty($files))
        return false;

    foreach($files as $file)
    {
        if (!file_exists($file))
            echo "File $file does not exist. Skipping over...\n";
        elseif (is_link($file))
            echo "File $file is a link. Links are not supported.\n";
        elseif (!is_dir($file) && !is_file($file))
            echo "File $file is not a supported file type.\n";
        else
            tar_pack_single($stream, $file);
    }

    return true;
}

function display_help()
{
    echo "Usage : tar [-o FILE] [-z] file...\n";
    echo "-o FILE : Saves the archive as FILE\n";
    echo "-z : Compress the archive with Gzip\n";
}

function main(int $argc, array $argv)
{
    if ($argc < 2)
    {
        display_help();
        return true;
    }

    $opts = parse_args($argc, $argv);

    $stream = "";
    if (false == pack_files($stream, $opts["files"]))
        return false;
    $stream .= str_repeat("\x00", 1024);

    if (true === $opts["compressed"])
    {
        $opts["out_file"] .= ".gz";
        $stream = gzencode($stream);
    }

    if (file_exists($opts["out_file"]))
    {
        $should_overwrite = userinput_overwrite_file($opts["out_file"]);
        if ($should_overwrite == "N" || $should_overwrite == "S")
            return -1;
    }

    file_put_contents($opts["out_file"], $stream);
    return 0;
}

$program_res = main($argc, $argv);
if ($program_res === -1)
{
    echo "Operation cancelled by the user.\n";
    exit(0);
}
elseif ($program_res === false)
{
    echo "An error occured. Terminating ...\n";
    exit(84);
}
else
{
    exit(0);
}