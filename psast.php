<?php

include 'tokenizer.php';
include 'analyzer.php';
include 'DirectoryTraverser.php';

//error_reporting(0);
//set_error_handler('handle_error', E_ALL);



function handle_error($errno, $errmsg, $filename, $linenum, $vars)
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
}


set_time_limit(300);

$dir = "S:\\Учеба\\Диплом\\shells-clear\\Shells\\test";

//$dir = "S:\\Учеба\\Диплом\\Shells\\GFS_web-shell_ver_3.1.7_-_PRiV8";

/*
function dir_traverse($dirname)
{

    global $not_detected;

    global $ignored;

    if ($directory = opendir($dirname))
    {
        echo "Dir: " . $dirname . "<br>";
        echo "####################################################################################" . "<br>";

        while (($filename = readdir($directory)) !== false)
        {
            if(is_dir($dirname . "\\" . $filename))
            {
                if ($filename == "." || $filename == "..")
                {
                    continue;
                }
                else
                {
                    dir_traverse($dirname . "\\" . $filename);
                }

            }
            if (is_file($dirname . "\\" . $filename))
            {
                $ext = pathinfo($dirname . "\\" . $filename,  PATHINFO_EXTENSION);
                if($ext != "php")
                {
                    $ignored[] = $dirname . "\\" . $filename;
                    continue;
                }

                $php_command = "C: && cd C:\\xampp\\php && php.exe -l ". $dirname . "\\" . $filename;

                $output = array();

                $ret_code = 0;

                exec($php_command , $output, $ret_code);

                $found = false;

                foreach ($output as $line)
                {
                    if(($res = stripos($line, "No syntax errors detected in")) !== false)
                    {
                        $found = true;
                    }
                }

                if($found == false)
                {
                    $ignored[] = $dirname . "\\" . $filename;
                    continue;
                }

                echo "File: " . $filename . "<br>";
                echo "-------------------------------------------------" . "<br>" . "<br>";

                $code = file_get_contents($dirname . "\\" . $filename);

                $code = preg_replace("/<\?(?!php|=)/", "<?php ", $code);

                $tokens = token_get_all($code);

                $tokens = normalize_tokens($tokens);

                //Дописываем в конец закрывающий php тег
                $tokens = add_close_tag($tokens);

                $tokens = prepare_tokens($tokens);

                $tokens = array_packing($tokens);

                $tokens = markup_user_defined_variables($tokens);

                $result = analyze($tokens);

                if(!$result)
                {
                    $not_detected[] = $dirname . "\\" . $filename;
                }

                //echo "####################################################################################" . "<br>";
            }
        }
    }
}

$error_list = array();

$not_detected = array();

$ignored = array();

try
{
    dir_traverse($dir);
}
catch(Exception $e)
{
    echo 'Выброшено исключение: ',  $e->getMessage(), "<br>";
}

echo "Not detected: " . "<br>";

foreach($not_detected as $filename)
{
    echo $filename . "<br>";
}

echo "Ignored: " . "<br>";

foreach($ignored as $filename)
{
    echo $filename . "<br>";
}

echo "Errors: " . "<br>";

foreach($error_list as $err)
{
    echo $err . "<br>";
}

*/


$traverser = new DirectoryTraverser($dir);

$traverser->traverse();

$traverser->print_statistics();


