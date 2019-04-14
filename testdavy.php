<?php

function my_array()
{


$dossier = [
    'Dossier_1' => $fich =['fichier_1'],
    'Dossier_2' => ['Fichier_1', 'Fichier_2'],
    'Dossier_3' => ['Fichier_1', 'Fichier_2', 'Fichier_3'],
    ];

    array_push($dossier['Dossier1'], 'fichier_X');
    var_dump($dossier);
}
my_array();


?>