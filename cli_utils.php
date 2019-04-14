<?php

function userinput_overwrite_file(string $filename)
{
    echo "File $filename already exists. ";
    echo "Would you like to overwrite it ?\n";

    $uinput = null;

    do
    {
        echo "Y => Overwrite ";
        echo "A => Overwrite All ";
        echo "N => Skip ";
        echo "S => Skip All\n";

        $uinput = fgetc(STDIN);
        if ($uinput != "Y" && $uinput != "A" && $uinput != "N" && $uinput != "S")
        {
            echo "Invalid command.\n";
            $uinput = null;
        }
        else
            return $uinput;
    } while ($uinput == null);

    return false;
}