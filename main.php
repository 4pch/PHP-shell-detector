<?php

include 'DirectoryTraverser.php';

set_time_limit(0);


$path = $argv[1];

if(is_dir($path))
{
    $traverser = new DirectoryTraverser($path);

    $traverser->traverse();

    $traverser->print_statistics();
}

if(is_file($path))
{
    $filer = new Filer($path);

    $filer->analyze_file();
}

