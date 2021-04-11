<?php

include "sources.php";

include 'ShellSign.php';

include 'Stringer.php';

//todo: в коллбек параметрах, если параметр строка, то проверять ее на плохую функцию, иначе много ложных ошибок
//todo: разобраться с разделителями в регулярках

define("UD_VARIABLE_CALL" , 500);
define("VARIABLE_CALL" , 501);
define("EVAL_CALL" , 502);
define("PVF_CALL_WITH_UDV" , 503);
define("PVF_CALL" , 504);
define("PVF_CALL_WITH_UDF" , 505);
define("PCRE_WITH_E_BY_SPEC_ARG" , 506);
define("PCRE_STRANGE" , 507);
define("PCRE_WITH_E" , 508);
define("UD_CALLBACK_PARAM" , 509);
define("VARIABLE_AS_CALLBACK" , 510);
define("CONCAT_CALLBACK_PARAM" , 510);
define("STRING_CALLBACK" , 510);
define("PHP_STRING_CALLBACK" , 510);
define("BAD_FUNC_CALL_CALLBACK" , 510);
define("FUNC_CALL_CALLBACK" , 510);
define("CONSTRUCT_CALLBACK" , 510);
define("CALLBACK_NOT_WITH_LAMBDA" , 510);
define("MULTI_DOLLAR_VAR_DEF", 511);
define("FILE_FUNC_CALL", 512);
define("FILE_FUNC_CALL_WITH_UDF", 513);
define("FILE_FUNC_CALL_WITH_UDV", 514);
define("FILE_READ_CALL_WITH_UDV", 515);
define("DB_CALL_WITH_UDF", 516);


//todo: вызов метода invoke и invokeArgs
//todo: COM usage
//todo: include
//todo: ReflectionFunction

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
 * Ищет признаки шеллов в массиве токенов
 */
class Analyzer
{
    public $tokens;

    public $reporter;

    public function __construct(Reporter $reporter, $tokens)
    {
        $this->reporter = $reporter;
        $this->tokens = $tokens;
    }

    public function analyze()
    {
        $this->markup_user_defined_variables();

        return $this->analyze_tokens();
    }

    public function analyze_tokens()
    {

        $to_return = false;

        $tokens_count = count($this->tokens);

        for ($i = 0; $i < $tokens_count; ++$i)
        {
            $type = 0;

            if($this->tokens[$i]->is_var && $this->tokens[$i + 1]->name == "T_PAR_OPEN" ||
                $this->tokens[$i]->name == "T_EVAL" && $this->tokens[$i + 1]->name == "T_PAR_OPEN" ||
                $this->tokens[$i]->name == "T_STRING" && $this->tokens[$i + 1]->name == "T_PAR_OPEN"

            )
            {
                //Ищем закрывающую скобку
                $opened_par = 1;

                $offset = 2;

                $args = array();

                $arg_num = 1;

                $args[$arg_num] = array();

                while ($opened_par != 0)
                {
                    if (!isset($this->tokens[$i + $offset]))
                    {
                        echo "Invalid syntax in string: " . $this->tokens[$i]->str_num . "<br>";
                        break;
                    }
                    if ($this->tokens[$i + $offset]->name == "T_PAR_OPEN")
                    {
                        $args[$arg_num][] = $this->tokens[$i + $offset];
                        ++$opened_par;
                    }
                    elseif ($this->tokens[$i + $offset]->name == "T_PAR_CLOSE")
                    {
                        if ($opened_par != 1)
                        {
                            $args[$arg_num][] = $this->tokens[$i + $offset];
                        }
                        --$opened_par;
                    }
                    elseif ($this->tokens[$i + $offset]->name == "T_COMMA" && $opened_par == 1)
                    {
                        ++$arg_num;
                        $args[$arg_num] = array();
                    }
                    else
                    {
                        $args[$arg_num][] = $this->tokens[$i + $offset];
                    }

                    ++$offset;
                }

                $to_return |= $this->check_function_call($this->tokens[$i], $args);
            }

            //Если типо ${a}
            if($this->tokens[$i]->id == T_DOLLAR && $this->tokens[$i + 1]->id == T_CURLY_PAR_OPEN)
            {
                if($this->tokens[$i + 2]->id == T_STRING || $this->tokens[$i + 2]->id == T_CONSTANT_ENCAPSED_STRING)
                {
                    $type = MULTI_DOLLAR_VAR_DEF;
                    echo "Variable call in line: " . $this->tokens[$i]->str_num . "<br>";
                    $to_return = true;
                }
                else
                {
                    $type = MULTI_DOLLAR_VAR_DEF;
                    echo "Variable call in line: " . $this->tokens[$i]->str_num . "<br>";
                    $to_return = true;
                }
            }
            if($type != 0)
            {
                $sign = new ShellSign($this->tokens[$i], 0, 0, $type);
                $this->reporter->add_sign($sign);
            }
        }

        return $to_return;
    }

    public function check_eval_call($function_token, $arguments)
    {
        $type = EVAL_CALL;
        echo "EVAL call in line: " . $function_token->str_num . "<br>";

        $sign = new ShellSign($function_token, $arguments, 0, $type);
        $this->reporter->add_sign($sign);

        return true;
    }

    public function check_variable_call($function_token, $arguments)
    {
        if ($function_token->is_user_defined)
        {
            $type = UD_VARIABLE_CALL;
            echo "Call of user-defined variable in line:" . $function_token->str_num . "<br>";
            $to_return = true;
        }
        else
        {
            $type = VARIABLE_CALL;
            echo "Variable call in line: " . $function_token->str_num . "<br>";
            $to_return = true;
        }
        if($type != 0)
        {
            $sign = new ShellSign($function_token, $arguments, 0, $type);
            $this->reporter->add_sign($sign);
        }

        return $to_return;
    }

    public function check_function_call($function_token, $arguments)
    {
        $to_return = false;

        $function_token->orig_str = strtolower($function_token->orig_str);

        if($function_token->orig_str == "eval")
        {
            return $this->check_eval_call($function_token, $arguments);
        }

        if($function_token->is_var)
        {
            return $this->check_variable_call($function_token, $arguments);
        }

        //Функция - опасная PHP-функция (system, shell и тд)
        if(key_exists($function_token->orig_str, VulnFunctions::$PVF))
        {
            return $this->check_unwanted_function_call($function_token, $arguments);
        }

        //Функция для работы с регулярными выражениями
        //todo: другие способы указывать модификаторы
        elseif(key_exists($function_token->orig_str, VulnFunctions::$pcre_functions))
        {
            return $this->check_pcre_function_call($function_token, $arguments);
        }

        //Функция, поддерживающая callback-параметр
        //todo: добавить объявление через array( => 'badfunc)
        elseif(key_exists($function_token->orig_str, VulnFunctions::$callbackable))
        {
            return $this->check_function_with_callback_call($function_token, $arguments);
        }

        //Вызов функций работы с бд
        elseif(key_exists($function_token->orig_str, VulnFunctions::$db_functions))
        {
            return $this->check_dbms_function_call($function_token, $arguments);
        }

        //Загрузка файлов, по другому никак, поскольку простого вызова этой функции хватит, чтобы загрузить новый шелл
        elseif(key_exists($function_token->orig_str, VulnFunctions::$file_uploading))
        {
            return $this->check_file_uploading_function_call($function_token, $arguments);
        }

        elseif(key_exists($function_token->orig_str, VulnFunctions::$file_write_functions))
        {
            return $this->check_file_write_function_call($function_token, $arguments);
        }

        elseif(key_exists($function_token->orig_str, VulnFunctions::$file_read_functions))
        {
            return $this->check_file_read_function_call($function_token, $arguments);
        }

        return $to_return;
    }

    public function check_unwanted_function_call($function_token, $arguments)
    {
        //Тип опасности. Нужен, чтобы вывести соответствующее предупреждение
        $type = 0;

        //В этом списке хранятся массивы формата имя функции => (кол-во аргументов, уязвимый аргуметн)
        $function_pattern = VulnFunctions::$PVF[$function_token->orig_str];

        // Ноль - указатель на переменное число параметров функции
        if($function_pattern[1] != 0)
        {
            //Если их конечнное число, то уязвимый параметр указан по индексу 1
            $vuln_arg_number = $function_pattern[1];
        }
        else
        {
            //Если их переменное число и указан 0 - значит уязвимый - предпоследний
            if($function_pattern[0] == 0)
            {
                $vuln_arg_number = count($arguments);
            }
        }

        /*
         * Теперь мы знаем, какой параметр нужно проверять - $vuln_arg_number
         * Им может быть один токен system($_GET[1])
         * или массив токенов system(boo().foo().$a)
         * Эти варианты нужно рассмотреть отдельно
        */


        //Если аргумент - переменная
        if(count($arguments[$vuln_arg_number]) == 1 && $arguments[$vuln_arg_number][0]->is_var)
        {
            //Если она получена от пользователя
            if($arguments[$vuln_arg_number][0]->is_user_defined
                || in_array($arguments[$vuln_arg_number][0]->orig_str, Sources::$user_defined))
            {
                $type = PVF_CALL_WITH_UDV;
                //echo "PVF call with user-defined variable in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
            else
            {
                //В любом случае отмечаем это как признак
                $type = PVF_CALL;
                //echo "PVF call in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
        }

        //Если больше одного токена и в аргументе вызов функции
        elseif($arguments[$vuln_arg_number][0]->name == "T_STRING" &&
            $arguments[$vuln_arg_number][1]->name == "T_PAR_OPEN")
        {
            //Если эта функция предоставляет данные, определяемые пользователем
            if(in_array($arguments[$vuln_arg_number][0]->orig_str, Sources::$user_defined_functions))
            {
                $type = PVF_CALL_WITH_UDF;
                //echo "PVF call with user-defined by function in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
            else
            {
                //В любом случае отмечаем это как признак, даже если любая другая функция
                $type = PVF_CALL;
                //echo "PVF call in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
        }
        else
        {
            //В любом случае отмечаем это как признак, даже если аргумент - не вызов функции
            $type = PVF_CALL;
            //echo "PVF call in line: " . $function_token->str_num . "<br>";
            $to_return = true;
        }

        //формируем признак
        if($type != 0) {
            $sign = new ShellSign($function_token, $arguments, $vuln_arg_number, $type);

            //и записываем
            $this->reporter->add_sign($sign);
        }

        return $to_return;
    }

    public function check_pcre_function_call($function_token, $arguments)
    {

        $to_return = false;

        //Тип опасности. Нужен, чтобы вывести соответствующее предупреждение
        $type = 0;

        /*
         * Многие (но не все) функции для регулярных выражений поддерживают указание
         * модификаторов как в строке паттерна, так и отдельным параметром.
         * В списке VulnFunctions::$pcre_functions лежит массив имя функции => (кол-во параметров, параметр паттерна),
         * если функция не поддерживает указание модификаторов отдельным параметром
         * Или имя функции => (кол-во параметров, параметр паттерна, параметр для модификаторов), если функция
         * поддерживает такое указание
         */
        $function_pattern = VulnFunctions::$pcre_functions[$function_token->orig_str];


        //Если в массиве два значения, значит первое - количство параметров, а второе - параметр с паттерном
        if(count($function_pattern) == 2)
        {
            $pattern_arg_number = $function_pattern[1];

            $pattern_arg = $arguments[$pattern_arg_number];
        }

        //Если параметра 3 значения, значит первое - количство параметров,
        //второе параметр с паттерном
        //третье - параметр для модификаторов
        if(count($function_pattern) == 3)
        {
            $pattern_arg = $arguments[$function_pattern[1]];
            $modifier_param = $function_pattern[2];

            //если модификаторы заданы отдельными параметрами
            if(isset($arguments[$modifier_param]))
            {
                //если они заданы одной строкой
                if(count($arguments[$modifier_param]) == 1 && $arguments[$modifier_param][0]->name == "T_CONSTANT_ENCAPSED_STRING")
                {
                    //есть ли в этой строке модификатор e
                    if(stripos($arguments[$modifier_param][0]->orig_str, 'e') !== false)
                    {
                        $type = PCRE_WITH_E_BY_SPEC_ARG;
                        //echo "PCRE function with e modifier by special argument called in line " . $function_token->str_num . "<br>";
                        $to_return = true;
                        //return $to_return;

                    }
                }
                //Если они заданы не одной строкой - это подозрительно
                else
                {
                    $type = PCRE_STRANGE;
                    //echo "PCRE function with strange called in line " . $function_token->str_num . "<br>";
                    $to_return = true;
                }
            }
        }
        /*
         * Теперь нужно проверить модификаторы в строке паттерна
         */

        //Если паттерн указан одной строкой
        if(count($pattern_arg) == 1 && $pattern_arg[0]->name == "T_CONSTANT_ENCAPSED_STRING")
        {
            //Выделяем строку паттерна
            $pattern_string = $pattern_arg[0]->orig_str;

            $matches = array();

            //todo: добавить всякий бред в модификаторах, типо \х65, конкатенаций и тд

            //регулярка забирает, все между // (или ||) - в первый карман т.е. сам шаблон, а во второй - модификаторы
            preg_match("/[\/|](.*)[\/|](.*)/i", $pattern_string, $matches);

            //Если модификаторы вообще есть
            if(isset($matches[2]))
            {
                $modifiers = $matches[2];

                //если среди них есть e
                if (strpos($modifiers, 'e') !== false)
                {
                    $type = PCRE_WITH_E;
                    //echo "PCRE function with e modifier called in line " . $function_token->str_num . "<br>";
                    $to_return = true;
                }
            }
        }
        //Если паттерн указан не одной строкой - это странно
        else
        {
            $type = PCRE_STRANGE;
            //echo "PCRE function with strange called in line " . $function_token->str_num . "<br>";
            $to_return = true;
            return $to_return;
        }
        if($type != 0)
        {//Формируем и запоминаем признак
        $sign = new ShellSign($function_token, $arguments, $pattern_arg_number, $type);

        $this->reporter->add_sign($sign);
        }

        return $to_return;
    }

    public function check_function_with_callback_call($function_token, $arguments)
    {
        //Тип
        $type = 0;

        $to_return = false;

        /*
         * В этом случае нужно проверить что из себя представляет callback-параметр этой функции
         * В массиве VulnFunctions::$callbackable лежит массив имя_функции => (кол-во параметров, callback-параметр),
         * Если кол-во параметров равно 0, значит их переменное количество
         * Если callback-параметр равен 0, значит он последний из переменного числа параметров
        */

        $function_pattern = VulnFunctions::$callbackable[$function_token->orig_str];


        //если конечное число аргументов
        if ($function_pattern[0] != 0)
        {
            //то номер лежит в [1]
            $callback_arg_number = $function_pattern[1];
        }
        //если аргуметов переменное число
        else
        {
            //показывает, что последний
            if ($function_pattern[1] == 0)
            {
                //Колбек - дпоследний параметр
                $callback_arg_number = count($arguments);
            }
            else
            {
                //иначе он указан явно
                $callback_arg_number = $function_pattern[1];
            }

        }

        //Если колбек параметр не задан
        if(!isset($arguments[$callback_arg_number]) || count($arguments[$callback_arg_number]) == 0)
        {
            return false;
        }

        /*
         * Теперь можно рассмотреть токены, описывающие callback-параметр
         */

        //Докапываемся до этого параметра, только если он определен не через лямбда-функцию
        if($arguments[$callback_arg_number][0]->orig_str !== "function")
        {
            //Если callback-параметр определен одним токеном
            if (count($arguments[$callback_arg_number]) == 1)
            {
                //если коллбек - переменная
                if ($arguments[$callback_arg_number][0]->is_var)
                {

                    //и она передана от пользователя
                    if ($arguments[$callback_arg_number][0]->is_user_defined)
                    {
                        $type = UD_CALLBACK_PARAM;
                        $to_return = true;
                    }

                    //или она конкатенирована
                    if ($arguments[$callback_arg_number][0]->is_concateneted)
                    {
                        $type = CONCAT_CALLBACK_PARAM;
                        $to_return = true;
                    }
                    else
                    {
                        $type = VARIABLE_AS_CALLBACK;
                        $to_return = true;
                    }

                }

                //коллбек - строка (string)
                if ($arguments[$callback_arg_number][0]->name == "T_CONSTANT_ENCAPSED_STRING")
                {
                    $type = STRING_CALLBACK;
                    //todo: проверка по белому словарю
                    $to_return = true;
                }

                //коллбек - строка-название в PHP (passthru, system и тд)
                if ($arguments[$callback_arg_number][0]->name == "T_STRING")
                {
                    $type = PHP_STRING_CALLBACK;
                    $to_return = true;
                }
            }
            /*
             * Теперь случай, если callback определен не через лямбда-функцию и задается больше,
             * чем одним токеном
             */
            else
            {
                //todo: если коллбек есть $a = bad_func()

                //если в начале коллбека вызывается функция
                if ($arguments[$callback_arg_number][0]->name == "T_STRING")
                {
                    //если эта функция -  функция преобразования
                    if (in_array($arguments[$callback_arg_number][0]->orig_str, VulnFunctions::$coding_decoding))
                    {
                        $type = BAD_FUNC_CALL_CALLBACK;
                        //echo "String callback with bad func call call in line: " . $function_token->str_num . "<br>";
                        $to_return = true;
                    }

                    $type = FUNC_CALL_CALLBACK;
                    //echo "String callback with bad func call call in line: " . $function_token->str_num . "<br>";
                    $to_return = true;
                }

                //Если колбек конкатенируется из строк, переменных и чисел
                if($this->check_for_construct($arguments[$callback_arg_number]))
                {
                    $type = CONSTRUCT_CALLBACK;
                    //echo "Callback parameter is multi concatenated " . $function_token->str_num . "<br>";
                    $to_return = true;
                }
            }

            //В любом случае отмечаем как подозрительный параметр
            $type = CALLBACK_NOT_WITH_LAMBDA;
            echo "Callback parameter defined not with lambda function: " . $function_token->str_num . "<br>";
            $to_return = true;

        }
        if($type != 0) {
            $sign = new ShellSign($function_token, $arguments, $callback_arg_number, $type);
            $this->reporter->add_sign($sign);
        }
        return $to_return;


    }

    public function check_dbms_function_call($function_token, $arguments)
    {
        //Тип опасности. Нужен, чтобы вывести соответствующее предупреждение
        $type = 0;
        $to_return = false;

        //В этом списке хранятся массивы формата имя функции => (кол-во аргументов, уязвимый аргуметн)
        $function_pattern = VulnFunctions::$db_functions[$function_token->orig_str];

        // Ноль - указатель на переменное число параметров функции
        if($function_pattern[1] != 0)
        {
            //Если их конечнное число, то уязвимый параметр указан по индексу 1
            $vuln_arg_number = $function_pattern[1];
        }
        else
        {
            //Если их переменное число и указан 0 - значит уязвимый - предпоследний
            if($function_pattern[0] == 0)
            {
                $vuln_arg_number = count($arguments);
            }
        }

        //Если аргумент - переменная
        if(count($arguments[$vuln_arg_number]) == 1 && $arguments[$vuln_arg_number][0]->is_var)
        {
            //Если она получена от пользователя
            if($arguments[$vuln_arg_number][0]->is_user_defined
                || in_array($arguments[$vuln_arg_number][0]->orig_str, Sources::$user_defined))
            {
                $type = PVF_CALL_WITH_UDV;
                //echo "PVF call with user-defined variable in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
            else
            {
                //В любом случае отмечаем это как признак
                $type = PVF_CALL;
                //echo "PVF call in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }
        }

        //Если больше одного токена и в аргументе вызов функции
        elseif($arguments[$vuln_arg_number][0]->name == "T_STRING" &&
            $arguments[$vuln_arg_number][1]->name == "T_PAR_OPEN")
        {
            //Если эта функция предоставляет данные, определяемые пользователем
            if(in_array($arguments[$vuln_arg_number][0]->orig_str, Sources::$user_defined_functions))
            {
                $type = DB_CALL_WITH_UDF;
                //echo "PVF call with user-defined by function in line: " . $function_token->str_num . "<br>";
                $to_return = true;
            }

        }

        //формируем признак
        if($type != 0) {
            $sign = new ShellSign($function_token, $arguments, $vuln_arg_number, $type);

            //и записываем
            $this->reporter->add_sign($sign);
        }

        return $to_return;

    }

    public function check_file_uploading_function_call($function_token, $arguments)
    {
        $type = 0;

        $to_return = false;

        if($function_token->orig_str == "move_uploaded_file")
        {
            $type = 10;
            $to_return = true;
        }

        if($function_token->orig_str == "copy" && $arguments[1][0]->orig_str == '$_FILES')
        {
            $type = 10;
            $to_return = true;
        }
        if($type)
        {
            $sign = new ShellSign($function_token, "", 0, $type);

            $this->reporter->add_sign($sign);
        }

        return $to_return;
    }

    public function check_for_construct($tokens)
    {
        foreach ($tokens as $token)
        {
            //добавить соответсвтующие функции chr и тд
            if(!in_array($token->name, Sources::$string_constructing))
            {
                return false;
            }
        }

        return true;
    }

    public function markup_user_defined_variables()
    {
        $tokens_count = count($this->tokens);

        for($i = 0; $i < $tokens_count; ++$i)
        {
            //Если переменной передается какое-то значение
            if($this->tokens[$i]->is_var && in_array($this->tokens[$i + 1]->id, TokenTypes::$T_EQUALS))
            {
                //Проверка на юзер дефайнд
                if (in_array($this->tokens[$i + 2]->orig_str, Sources::$user_defined))
                {
                    $this->tokens[$i]->is_user_defined = true;
                }

                if (in_array($this->tokens[$i + 2]->orig_str, Sources::$user_defined_functions))
                {
                    $this->tokens[$i]->is_user_defined = true;
                }


                //Если переменной присваевается значение другой юзер-дефайнд переменной
                if ($this->tokens[$i + 2]->is_var && $this->tokens[$i + 2]->is_user_defined)
                {
                    $this->tokens[$i]->is_user_defined = true;
                }
            }
        }
    }

    public function check_file_write_function_call($function_token, $arguments)
    {
        //Тип опасности. Нужен, чтобы вывести соответствующее предупреждение
        $type = 0;

        //В этом списке хранятся массивы формата имя функции => (кол-во аргументов, аргумент для записи в файл)
        $function_pattern = VulnFunctions::$file_write_functions[$function_token->orig_str];

        //Ноль - указатель на переменное число параметров функции
        if($function_pattern[1] != 0)
        {
            //Если их конечнное число, то параметр для записи в файл указан по индексу 1
            $vuln_arg_number = $function_pattern[1];
        }
        else
        {
            //Если их переменное число и указан 0 - значит искомый аргумент - предпоследний
            if($function_pattern[0] == 0)
            {
                $vuln_arg_number = count($arguments);
            }
        }

        //Если аргумент - переменная
        if(count($arguments[$vuln_arg_number]) == 1 && $arguments[$vuln_arg_number][0]->is_var)
        {
            //Если она получена от пользователя
            if($arguments[$vuln_arg_number][0]->is_user_defined
                || in_array($arguments[$vuln_arg_number][0]->orig_str, Sources::$user_defined))
            {
                $type = FILE_FUNC_CALL_WITH_UDV;
                $to_return = true;
            }
            else
            {
                //В любом случае отмечаем это как признак
                $type = FILE_FUNC_CALL;
                $to_return = true;
            }
        }

        //Если аргумент - строка
        if(count($arguments[$vuln_arg_number]) == 1 && $arguments[$vuln_arg_number][0]->id == "T_CONSTANT_ENCAPSED_STRING")
        {

            $is_shell = false;

            $stringer = new Stringer($arguments[$vuln_arg_number][0]->orig_str);

            if($stringer->analyze_code() == true)
            {
                $is_shell = true;
            }

            if($is_shell)
            {
                $type = FILE_FUNC_CALL_WITH_UDV;
                $to_return = true;
            }
        }

        //Если больше одного токена и в аргументе вызов функции
        elseif($arguments[$vuln_arg_number][0]->name == "T_STRING" &&
            $arguments[$vuln_arg_number][1]->name == "T_PAR_OPEN")
        {
            //Если эта функция предоставляет данные, определяемые пользователем
            if(in_array($arguments[$vuln_arg_number][0]->orig_str, Sources::$user_defined_functions))
            {
                $type = FILE_FUNC_CALL_WITH_UDF;
                $to_return = true;
            }
            //Если преобразующая функция
            if(in_array($arguments[$vuln_arg_number][0]->orig_str, VulnFunctions::$coding_decoding))
            {
                $type = FILE_FUNC_CALL_WITH_UDF;
                $to_return = true;
            }
            else
            {
                //В любом случае отмечаем это как признак, даже если любая другая функция
                $type = FILE_FUNC_CALL;
                $to_return = true;
            }
        }
        else
        {
            //В любом случае отмечаем это как признак, даже если аргумент - не вызов функции
            $type = FILE_FUNC_CALL;
            //echo "PVF call in line: " . $function_token->str_num . "<br>";
            $to_return = true;
        }

        //формируем признак
        if($type != 0) {
            $sign = new ShellSign($function_token, $arguments, $vuln_arg_number, $type);

            //и записываем
            $this->reporter->add_sign($sign);
        }

        return $to_return;
    }

    public function check_file_read_function_call($function_token, $arguments)
    {
        //Тип опасности. Нужен, чтобы вывести соответствующее предупреждение
        $type = 0;
        $to_return = false;

        //В этом списке хранятся массивы формата имя функции => (кол-во аргументов, имя файла)
        $function_pattern = VulnFunctions::$file_read_functions[$function_token->orig_str];

        //Ноль - указатель на переменное число параметров функции
        if($function_pattern[1] != 0)
        {
            //Если их конечнное число, то параметр с именем файла указан по индексу 1
            $filename_arg_number = $function_pattern[1];
        }
        else
        {
            //Если их переменное число и указан 0 - значит аргумент с именем файла - предпоследний
            if($function_pattern[0] == 0)
            {
                $filename_arg_number = count($arguments);
            }
        }

        if(count($arguments[$filename_arg_number]) == 1 && $arguments[$filename_arg_number][0]->is_var)
        {
            //Если она получена от пользователя
            if($arguments[$filename_arg_number][0]->is_user_defined
                || in_array($arguments[$filename_arg_number][0]->orig_str, Sources::$user_defined))
            {
                $type = FILE_READ_CALL_WITH_UDV;
                $to_return = true;
            }
        }

        if($type != 0) {
            $sign = new ShellSign($function_token, $arguments, $filename_arg_number, $type);

            //и записываем
            $this->reporter->add_sign($sign);
        }

        return $to_return;

    }
}