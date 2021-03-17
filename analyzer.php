<?php

include "sources.php";

//Check for potential vulnerable functions call in dir
function var_call_dir($dirname)
{
    $dir = opendir($dirname);
    
    if(!$dir)
    {
        echo "Somethig bad\n";
        die();
    }
    
    $varcalls = 0;
    
    while(($e = readdir($dir)) !== false)
    {
        if(!is_dir($e))
        {
            if ($e=='.' || $e == '..') continue;

            if (strripos($e, '.php') != 0)
            {
                echo "Filename: " . $e . "\n";
                $varcalls += var_call_file($dirname.$e);
            }
        }
    }
    
    closedir($dir);
    
    echo "Total count of warcalls: " . $varcalls . "\n"; 
}

//Check for potential vulnerable functions call in file
function var_call_file($filename)
{
    $code = file_get_contents($filename);
    
    $tokens = token_get_all($code);
    
    $tokens = prepare_tokens($tokens);
    
    return var_call_tokens($tokens);
}

function analyze($tokens)
{
    $tokens_count = count($tokens);

    for($i = 0; $i < $tokens_count; ++$i)
    {
        //вызов от переменной
        if($tokens[$i]->is_var && $tokens[$i + 1]->name == "T_PAR_OPEN")
        {
            echo "Call in line: " . $tokens[$i]->str_num . "\n";
            if($tokens[$i]->is_user_defined)
            {
                echo "Call of user-defined variable in line:" . $tokens[$i]->str_num . "<br>";
            }
        }


        //Вызов нежелательной функции
        if($tokens[$i]->name == "T_STRING" && $tokens[$i + 1]->name == "PAR_OPEN" && in_array($tokens[$i]->orig_str,VulnFunctions::$PVF))
        {
            echo "PVF call in line: " . $tokens[$i]->str_name . "\n";
        }

        //Вызов коллбека с юзер-дефайнд коллбеком добавить

        //Вызов функции через конкатенацию ее имени

        //Если переменной передается какое-то значение
        if($tokens[$i]->is_var && in_array($tokens[$i + 1]->name, Tokens_Types::$T_EQUALS))
        {
            //Проверка на юзер дефайнд
            if(in_array($tokens[$i + 2]->orig_str, Sources::$user_defined))
            {
                $tokens[$i]->is_user_defined = true;
            }



            //Если переменной присваевается значение другой юзер-дефайнд переменной
            if($tokens[$i + 2]->is_var && $tokens[$i + 2]->is_user_defined)
            {
                $tokens[$i]->is_user_defined = true;
            }


            //Если присваивается конкатенация от обычных строк
            if($tokens[$i + 2]->name == "T_CONSTANT_ENCAPSED_STRING" && $tokens[$i + 3]->name == "T_CONCAT_OP")
            {
                $find_last = 1;

                while($tokens[$i + 3 + $find_last]->name == "T_CONSTANT_ENCAPSED_STRING" && $tokens[$i + 3 + $find_last + 1]->name != "T_CONCAT_OP")
                {
                    ++$find_last;
                }

                //check indexes
                $concat_part = array_slice($tokens, $i + 2, $i + 2 + $find_last + 1 - $i - 3 + 1);

                $right_string = reverse_concatenation($concat_part);

                if(in_array($right_string, VulnFunctions::$PVF))
                {
                    $tokens[$i]->value = $right_string;
                    echo "Bad string" . $right_string . " in string: " . $tokens[$i][2] . "<br>";
                }

            }


        }
    }
}

function reverse_concatenation($tokens)
{
    $final_string = "";

    foreach($tokens as $token)
    {
        if($token->name == "T_CONCAT_OP")
        {
            continue;
        }

        else
        {
            $str = $token->orig_str;
            $str = trim($str, '"');
            $str = trim($str, '\'');
            $final_string .= $str;
        }

    }

    return $final_string;
}