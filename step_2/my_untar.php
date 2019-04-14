<?php

require_once(dirname(__FILE__).'/../tar_utils.php');
require_once(dirname(__FILE__).'/../cli_utils.php');

function main(int $argc, array $argv)
{
    $archive_name = "output.tar";

    echo "Extracting archive $archive_name ...\n";
    $stream = file_get_contents($archive_name);

    $offset = 0;
    do
    {
        $unpack_res = tar_unpack_single($stream, $offset);

        if ($unpack_res === false)
        {
            echo "Failed to extract file from archive\n";
            return false;
        }
        elseif ($unpack_res === -2)
        {
            $should_overwrite = userinput_overwrite_file("unknown");
            if ($should_overwrite == "Y" || $should_overwrite == "A")
                tar_unpack_single($stream, $offset, true);
        }
        elseif ($unpack_res === 1337)
        {
            echo "End of file reached. Extracting complete.\n";
            break;
        }
    } while(true);

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