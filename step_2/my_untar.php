<?php

require_once(dirname(__FILE__).'/../tar_utils.php');
require_once(dirname(__FILE__).'/../cli_utils.php');

function parse_args(int $argc, array $argv)
{
    $program_opts = [
        "compressed" => false,
        "out_dir" => "/tmp/wacrush2/",
        "in_file" => "output.tar"
    ];

    for($i = 1; $i < $argc; ++$i)
    {
        switch ($argv[$i])
        {
            case "-o":
                $program_opts["out_dir"] = $argv[++$i];
                break;
            case "-z":
                $program_opts["compressed"] = true;
                break;
            default:
                $program_opts["in_file"] = $argv[$i];
                break;
        }
    }

    return $program_opts;
}

function unpack_archive(string $archive, bool $compressed, string $out_dir)
{
    if (!file_exists($archive))
    {
        echo "Archive $archive does not exists.\n";
        return false;
    }

    echo "Opening archive $archive ...\n";
    $stream = file_get_contents($archive);

    if ($compressed)
        $stream = gzdecode($stream);

    $dupe_action = null;
    $offset = 0;
    do
    {
        $unpack_res = tar_unpack_single($stream, $offset, $out_dir);

        if ($unpack_res === false)
        {
            echo "Failed to extract file from archive\n";
            return false;
        }
        elseif ($unpack_res === -2)
        {
            switch($dupe_action)
            {
                case "A":
                    tar_unpack_single($stream, $offset, $out_dir, true);
                    break;
                case "S":
                    continue;
                default:
                    {
                        $should_overwrite = userinput_overwrite_file("unknown");
                        if ($should_overwrite == "Y" || $should_overwrite == "A")
                            tar_unpack_single($stream, $offset, true);
                        $dupe_action = $should_overwrite;
                    }
            }
        }
        elseif ($unpack_res === 1337)
        {
            echo "End of file reached. Extracting complete.\n";
            break;
        }
    } while(true);

    return true;
}

function display_help()
{
    echo "Usage : my_untar.php [-o FILE] [-z] file\n";
    echo "-o PATH : Extracts in the directory PATH\n";
    echo "-z : Notify that the archive is compressed with Gzip\n";
    echo "file : The archive to extract\n";
}

function main(int $argc, array $argv)
{
    if ($argc < 2)
    {
        display_help();
        return true;
    }

    $opts = parse_args($argc, $argv);
    $archive_name = $opts["in_file"];

    if (!unpack_archive($archive_name, $opts["compressed"], $opts["out_dir"]))
    {
        echo "Error while extracting archive $archive_name";
        return false;
    }

    return true;
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