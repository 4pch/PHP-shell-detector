<?php

include 'DirectoryTraverser.php';

//error_reporting(0);
//set_error_handler('handle_error', E_ALL);


/*function handle_error($errno, $errmsg, $filename, $linenum, $vars)
{
    global $error_list;
    $errors = array(
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error'
    );

    if (in_array($errno, array_keys($errors)))
    {
        $error_list[] = "Error " . $errmsg . " in file: " . $filename . " in line: " . $linenum . "<br>";
    }
}*/


set_time_limit(300);

//$path = $argv[1];

$path = "S:\\Учеба\\Диплом\\shells-clear\\Shells\\webshell-sample-master\\php_3";
//$path = "S:\\Учеба\\Диплом\\shells-clear\\Shells\\test";

//$path = "S:\\Учеба\\Диплом\\clear";

//$path = "S:\\Учеба\\Диплом\\composer-master";



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

