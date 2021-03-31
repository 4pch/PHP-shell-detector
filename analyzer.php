<?php

include "sources.php";


//todo: вызов метода invoke и invokeArgs
//todo: чекать все строки на длину и энтропию
//todo: чекать вызовы функций зипа, компреса и прочего говна
function analyze($tokens)
{
    $to_return = false;
    $tokens_count = count($tokens);

    for($i = 0; $i < $tokens_count; ++$i)
    {
        //вызов переменной как функции
        if($tokens[$i]->is_var && $tokens[$i + 1]->name == "T_PAR_OPEN")
        {

            if($tokens[$i]->is_user_defined)
            {
                echo "Call of user-defined variable in line:" . $tokens[$i]->str_num . "<br>";
                $to_return = true;
            }
            else
            {
                echo "Variable call in line: " . $tokens[$i]->str_num . "<br>";
                $to_return = true;
            }

        }

        //Вызов eval
        if($tokens[$i]->name == "T_EVAL" && $tokens[$i + 1]->name == "T_PAR_OPEN")
        {
            echo "EVAL call in line: " . $tokens[$i]->str_num . "<br>";
            $to_return = true;
        }

        //Попытка выловить все аргументы функции todo: это нужно доделать
        if($tokens[$i]->name == "T_STRING" && $tokens[$i + 1]->name == "T_PAR_OPEN")
        {
            //Ищем закрывающую скобку
            $opened_par = 1;

            $offset = 2;

            $args = array();

            $arg_num = 1;

            $args[$arg_num] = array();

            while($opened_par != 0)
            {
                if(!isset($tokens[$i + $offset]))
                {
                    echo "Invalid syntax in string: " . $tokens[$i]->str_num . "<br>";
                    break;
                }
                if ($tokens[$i + $offset]->name == "T_PAR_OPEN")
                {
                    $args[$arg_num][] = $tokens[$i + $offset];
                    ++$opened_par;
                }
                elseif ($tokens[$i + $offset]->name == "T_PAR_CLOSE")
                {
                    if($opened_par != 1 )
                    {
                        $args[$arg_num][] = $tokens[$i + $offset];
                    }
                    --$opened_par;
                }
                elseif ($tokens[$i + $offset]->name == "T_COMMA")
                {
                    ++$arg_num;
                    $args[$arg_num] = array();
                }
                else
                {
                    $args[$arg_num][] = $tokens[$i + $offset];
                }

                ++$offset;
            }

            $to_return |= check_function_call($tokens[$i], $args);
        }

        //Если переменной передается какое-то значение
        if($tokens[$i]->is_var && in_array($tokens[$i + 1]->name, Tokens_Types::$T_EQUALS))
        {
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
                //todo: проверять и присваиваемое, и присваемое плюс имеющееся

                //todo: сделать отдельно список плохих строк (их будет много)
                if(in_array($right_string, VulnFunctions::$PVF))
                {
                    $tokens[$i]->is_concatenaed = true;
                    $tokens[$i]->value = $right_string;
                    echo "Bad string" . $right_string . " in string: " . $tokens[$i][2] . "<br>";
                }

            }


            if($tokens[$i + 2]->is_var && $tokens[$i + 2]->is_concatenaed)
            {
                $tokens[$i]->is_concatenaed = true;
            }

        }
    }

    return $to_return;
}

/*
 * По имени функции и переданным ей параметрам определяет является ли она вредоносной
 */
function check_function_call($function_token, $arguments)
{
    $to_return = false;

    $function_token->orig_str = strtolower($function_token->orig_str);

    //Вызов опасных функций
    if(key_exists($function_token->orig_str, VulnFunctions::$PVF))
    {
        $function_pattern = VulnFunctions::$PVF[$function_token->orig_str];

        if($function_pattern[1] != 0)
        {
            $vuln_arg_number = $function_pattern[1];
        }
        else
        {
            if($function_pattern[0] == 0)
            {
                //Предпоследний
                $vuln_arg_number = count($arguments);
            }
        }

        if(count($arguments[$vuln_arg_number]) == 1 && $arguments[$vuln_arg_number][0]->is_var)
        {
            if($arguments[$vuln_arg_number][0]->is_user_defined || in_array($arguments[$vuln_arg_number][0]->orig_str, Sources::$user_defined))
            {
                echo "PVF call with user-defined variable in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
            else
            {
                echo "PVF call in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
        }
        elseif($arguments[$vuln_arg_number][0]->name == "T_STRING" && $arguments[$vuln_arg_number][1]->name == "T_PAR_OPEN")
        {
            if(in_array($arguments[$vuln_arg_number][0]->orig_str, Sources::$user_defined_functions))
            {
                echo "PVF call with user-defined by function in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
            else
            {
                echo "PVF call in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
        }
        else
        {
            echo "PVF call in line: " . $function_token->str_num . "<br>";
            $to_return = true;
        }
    }

    //Вызов функций регулярных выражений, поддерживающих модификатор e
    elseif(key_exists($function_token->orig_str, VulnFunctions::$pcre_functions))
    {
        $function_pattern = VulnFunctions::$pcre_functions[$function_token->orig_str];

        $pattern_arg_number = $function_pattern[1];

        $pattern_arg = $arguments[$pattern_arg_number];

        //если паттерн просто задан строкой
        if(count($pattern_arg) == 1 && $pattern_arg[0]->name == "T_CONSTANT_ENCAPSED_STRING")
        {
            $pattern_string = $pattern_arg[0]->orig_str;

            $matches = array();

            preg_match("/\/(.*)\/(.*)/i", $pattern_string, $matches);


            //проверить на иссет
            $modifiers = $matches[2];

            if(strpos($modifiers,'e') !== false)
            {
                echo "PCRE function with e modifier called in line " . $function_token->str_num . "<br>";
                $to_return = true;
            }
        }
    }

    //Вызов юзер-дефайнд колбеков
    //todo: плохая строка
    elseif(key_exists($function_token->orig_str, VulnFunctions::$callbackable))
    {
        $function_pattern = VulnFunctions::$callbackable[$function_token->orig_str];

        if($function_pattern[1] != 0)
        {
            $vuln_arg_number = $function_pattern[1];
        }
        else
        {
            if($function_pattern[0] == 0)
            {
                //Предпоследний
                $vuln_arg_number = count($arguments);
            }
        }

        if(count($arguments[$vuln_arg_number]) == 1 && $arguments[$vuln_arg_number][0]->is_var)
        {
            if($arguments[$vuln_arg_number][0]->is_user_defined)
            {
                echo "User-defined callback call in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }

            //тут тоже траблы
            if($arguments[$vuln_arg_number][0]->is_concateneted)
            {
                echo "Concatenated callback call in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }

            if($arguments[$vuln_arg_number][0]->name == "T_CONSTANT_ENCAPSED_STRING")
            {
                echo "String callback call in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }


        }


    }

    return $to_return;
}

/*
 * Собирает строку из последовательности подстрок и операторов конкатенации
 */
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


/*
 * Определяет является ли переменная определенная пользователем
 * todo: добавить вызовы функций
 * todo: проверка аргументов, не являющихся переменной
 */
function markup_user_defined_variables($tokens)
{
    $tokens_count = count($tokens);

    for($i = 0; $i < $tokens_count; ++$i)
    {
        //Если переменной передается какое-то значение
        if($tokens[$i]->is_var && in_array($tokens[$i + 1]->id, Tokens_Types::$T_EQUALS))
        {
            //Проверка на юзер дефайнд
            if (in_array($tokens[$i + 2]->orig_str, Sources::$user_defined))
            {
                $tokens[$i]->is_user_defined = true;
            }

            if (in_array($tokens[$i + 2]->orig_str, Sources::$user_defined_functions))
            {
                $tokens[$i]->is_user_defined = true;
            }


            //Если переменной присваевается значение другой юзер-дефайнд переменной
            if ($tokens[$i + 2]->is_var && $tokens[$i + 2]->is_user_defined)
            {
                $tokens[$i]->is_user_defined = true;
            }
        }
    }

    return $tokens;
}