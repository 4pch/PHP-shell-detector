<?php

include 'tokens_types.php';
include 'Token.php';

function normalize_tokens($tokens)
{
    $tokens_count = count($tokens);

    for ($i = 0; $i < $tokens_count; ++$i)
    {
        if(!is_array($tokens[$i]))
        {
            //Что с нулями делать?
            $tokens[$i] = array((constant(token_name_for_defines($tokens[$i]))), $tokens[$i], 0);
        }

        $token = new Token($tokens[$i]);

        $tokens[$i] = $token;

        //Удаляем ненужные теги
        if($token->is_ignore)
        {
            unset($tokens[$i]);
        }
    }

    //Восстанавливаем сплошную иднексацию в массиве токенов
    $tokens = array_values($tokens);

    return $tokens;
}

//todo: эту функцию нужно разбить на 2 каким-то образом
//Преобразует токены к однообразному синтаксису
function prepare_tokens($tokens)
{
    $tokens_count = count($tokens);

    $vars = array();

    $for_removing = array();

    for ($i = 0; $i < $tokens_count; ++$i)
    {
        if($tokens[$i]->is_var)
        {
            //Заносим в список переменных
            if(!key_exists($tokens[$i]->orig_str, $vars))
            {
                $vars[$tokens[$i]->orig_str] = $tokens[$i];
            }
            else
            {
                $tokens[$i] = $vars[$tokens[$i]->orig_str];
            }

        }

        //Синтаксис $a{'b'} преобразуем в $a['b']
        if($tokens[$i]->orig_str == '{' && ($tokens[$i - 1]->is_var || $tokens[$i]->orig_str == "]"))
        {
            $find_closing = 0;
            while($tokens[$i + $find_closing]->orig_str != "}")
            {
                ++$find_closing;
            }

            $tokens[$i]->orig_str = "[";
            $tokens[$i + $find_closing]->orig_str = "]";
        }

        //Синатксис $a['abc'][foo($b)] преобразуется в поле класса Token
        if($tokens[$i]->is_var && $tokens[$i + 1]->orig_str == "[")
        {
            $tokens[$i]->arr_indexes[] = "[";

            if(!in_array($i + 1, $for_removing))
            {
                $for_removing[] = $i + 1;
            }

            $arraing_index = 1;

            $array_index = $i;

            $opened_braces = 1;

            //for looking forward on the tokens array
            $offset = 2;

            while($opened_braces != 0)
            {
                if ($tokens[$i + $offset]->orig_str == "[")
                {
                    $opened_braces += 1;
                    if ($opened_braces == 1)
                    {
                        $arraing_index += 1; //new ...[blabla]...
                    }
                }
                elseif ($tokens[$i + $offset]->orig_str == "]")
                {
                    $opened_braces -= 1;
                    $tokens[$i]->arr_indexes[] = $tokens[$i + $offset]->orig_str;//закрывающую скобку тоже записываем

                    if(!in_array($i + $offset, $for_removing))
                    {
                        $for_removing[] = $i + $offset;
                    }
                    if ($tokens[$i + $offset + 1]->orig_str !== "[") //если не открывается новое измерение
                    {
                        break;
                    }
                    $offset += 1;
                    $opened_braces += 1;

                }
                /*elseif (is_array($tokens[$i + $offset]))
                {
                    $opened_braces -= 1;
                }*/

                $tokens[$i]->arr_indexes[] = $tokens[$i + $offset]->orig_str;
                if(!in_array($i+$offset, $for_removing))
                {
                    $for_removing[] = $i + $offset;
                }

                $offset += 1;
            }

        }

        //Синтаксис обратных ковычек `whoami` преобразуем в вызов псевдофункции backticks
        if($tokens[$i]->name == "T_BACKTICK")
        {
            $find_closing = 1;

            while($tokens[$i + $find_closing]->name != "T_BACKTICK")
            {
                ++$find_closing;
            }

            //Нужно взять с нулевого по $i - 1 (тк $i это символ ковычки), значит
            // ($i - 1) - 0 + 1  (последний - первый + 1)
            $before_backtick = array_slice($tokens, 0, $i);

            $back_name = new Token(array(T_BACKTICK,"`", $tokens[$i]->str_num));
            $open_par = new Token(array(T_OPEN_PAR,"(", $tokens[$i]->str_num));
            $back_call = array($back_name, $open_par);

            $middle = array_merge($back_call, array_slice($tokens, $i + 1, $find_closing - 1));

            $close_par = new Token(array(T_CLOSE_PAR,")",$tokens[$i]->str_num));

            //в $i + $find_close лежит ковычка, значит оффсет на 1 больше
            //количество = полседний - первый + 1
            $after_backtick = array_merge([$close_par], array_slice($tokens, $i + $find_closing + 1, count($tokens) - $i - $find_closing - 1 + 1 ));



            $tokens = array_merge($before_backtick, $middle, $after_backtick);
        }

        //Если переменной что-то присваевается
        if($tokens[$i]->is_var && in_array($tokens[$i + 1]->name, Tokens_Types::$T_EQUALS))
        {
            //Проверяем присвоение переменной конкатенации строк
            if($tokens[$i + 2]->name == "T_CONSTANT_ENCAPSED_STRING" && $tokens[$i + 3]->name == "T_CONCAT_OP")
            {
                $find_last = 1;

                while ($tokens[$i + 3 + $find_last]->name == "T_CONSTANT_ENCAPSED_STRING" && $tokens[$i + 3 + $find_last + 1]->name != "T_CONCAT_OP")
                {
                    ++$find_last;
                }

                //check indexes
                $concat_part = array_slice($tokens, $i + 2, $i + 2 + $find_last + 1 - $i - 3 + 1);

                $right_string = reverse_concatenation($concat_part);

                $tokens[$i]->value = $right_string;


            }

            //todo:Контроль над юзер инпутом
            /*if(in_array($tokens[$i + 2]->name, Sources::$user_defined)
            {

            }*/

        }

    }

    foreach($for_removing as $index)
    {
        unset($tokens[$index]);
    }

    $tokens = array_values($tokens);

    return $tokens;
}

function dealing_with_strings($tokens)
{
    $tokens_count = count($tokens);
    for($i = 0; $i < $tokens_count; ++$i)
    {
        if(is_array($tokens[$i]) && $tokens[$i] == "T_ENCAPSED_AND_WHITESPACE")
        {
            $tokens[$i][4] = len($tokens[$i][1]); //заносим длинну этой строки
            $tokens[$i][5] = shennon_entropy($tokens[$i][2]);
        }
    }
}

function shennon_entropy($string)
{
    return 0.5;
}




/*
////////////////////////////////////////////////////////////////////////////////
$code = '<?php `system`';

$tokens = token_get_all($code);

$tokens = normalize_tokens($tokens);

$tokens = prepare_tokens($tokens);

foreach($tokens as $index => $value)
{
    print_r($value);
    echo ("<br>");
}

*/