<?php

include 'tokens_types.php';
include 'Token.php';

/*
 * 1) Преобразует массив токенов в массив объектов класса Token
 * Если изначальный токен - массив, то из него создается объект класса Token
 * иначе - из строкового токена создается массив и на его основе объект
 * 2) Удаляет токены, помеченные как ненужные в Tokens_Types::$IGNORE
 */
function normalize_tokens($tokens)
{
    $tokens_count = count($tokens);

    for ($i = 0; $i < $tokens_count; ++$i)
    {
        if(!is_array($tokens[$i]))
        {
            //пытаемся определить строку
            /*$offset = 0;

            while(!is_array($tokens[$i + $offset]))
            {
                ++$offset;
            }*/

            //$str_num = $tokens[$i + $offset][2];
            $str_num = 0;

            //пытаемся узнать ID типа токена
            try
            {
                if(token_name_for_defines($tokens[$i]) !== null)
                {
                    $id = constant(token_name_for_defines($tokens[$i]));
                    $tokens[$i] = array($id, $tokens[$i], $str_num);
                }
                else
                {
                    echo "Undefined token type for token: " . $tokens[$i] , " in line: " . $str_num . "<br>";
                }
            }
            catch (Exception $e)
            {
                echo 'Выброшено исключение: ',  $e->getMessage(), "<br>";
                die();
            }
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

/*
 * 1) Связывает переменные в массиве Токенов - после выполнения все Токены, обозначавшие одну
 * переменную будут ссылаться на один и тот же объект типа Token
 * 2) Преобразует обращения к индексам массива из $a{2} в $a[2]
 * 3) Заменяет синтаксис обратных ковычек на вызов псевдофункции backtick
 * todo: где оставить работу с переменными - concatenated
 */
function prepare_tokens($tokens)
{
    $tokens_count = count($tokens);

    $vars = array();

    $for_removing = array();

    for ($i = 0; $i < $tokens_count; ++$i)
    {
        //Заносим в список переменных
        if($tokens[$i]->is_var)
        {

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

        //Синтаксис обратных ковычек `cmd` преобразуем в вызов псевдофункции backticks
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
            $open_par = new Token(array(T_PAR_OPEN,"(", $tokens[$i]->str_num));
            $back_call = array($back_name, $open_par);

            $middle = array_merge($back_call, array_slice($tokens, $i + 1, $find_closing - 1));

            $close_par = new Token(array(T_PAR_CLOSE,")",$tokens[$i]->str_num));

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

            if($tokens[$i + 2]->name == "T_CONSTANT_ENCAPSED_STRING" && $tokens[$i + 3]->name == "T_SEMICOLON")
            {

            }

        }

    }

    foreach($for_removing as $index)
    {
        unset($tokens[$index]);
    }

    $tokens = array_values($tokens);

    return $tokens;
}

/*
 * Преобразует обращения к массивам
 * Если в последовательности Токенов встречается $var['a'][2][foo($b, true)], то это
 * будет преобразовано в:
 * $var->arr_indexes[0] = 'a'
 * $var->arr_indexes[1] = 2
 * $var->arr_indexes[2] = foo($b, true)
 * Токены обращения к индексам будут удалены из последовательности
 */
function array_packing($tokens)
{
    $tokens_count = count($tokens);

    $for_removing = array();

    for ($i = 0; $i < $tokens_count; ++$i)
    {
        //Синатксис $a['abc'][foo($b)] преобразуется в поле класса Token
        if ($tokens[$i]->is_var && $tokens[$i + 1]->orig_str == "[")
        {
            $tokens[$i]->arr_indexes[] = "[";

            if (!in_array($i + 1, $for_removing))
            {
                $for_removing[] = $i + 1;
            }

            $arraing_index = 1;

            $array_index = $i;

            $opened_braces = 1;

            //for looking forward on the tokens array
            $offset = 2;

            while ($opened_braces != 0)
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

                    if (!in_array($i + $offset, $for_removing))
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
                if (!in_array($i + $offset, $for_removing))
                {
                    $for_removing[] = $i + $offset;
                }

                $offset += 1;
            }

        }

    }

    foreach ($for_removing as $index)
    {
        unset($tokens[$index]);
    }

    $tokens = array_values($tokens);

    return $tokens;
}

function add_close_tag($tokens)
{
    if($tokens[count($tokens) - 1]->id != T_CLOSE_TAG)
    {
        $token = array(T_CLOSE_TAG, "?>", $tokens[count($tokens) - 1]->str_num);
        $tokens[count($tokens)] = new Token($token);
    }

    return $tokens;
}