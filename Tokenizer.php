<?php

include 'TokenTypes.php';
include 'Token.php';

/*
 * Разбивает код на токены и приводит их к удобному виду
 */

class Tokenizer
{
    public $code;

    public $tokens;

    public function __construct($code)
    {
        $this->code = $code;

        //Открывающие теги <? заменяем на <?php, иначе неправильно разбивает на токены сам PHP
        $this->code = preg_replace("/<\?(?!php|=)/", "<?php ", $this->code);

        //До PHP 7 поддерживался синтаксис <script language="php">...</script>, нужно заменить его на нормальные теги
        $this->code = preg_replace("/<\s*script\s*language\s*=\s*\"php\"\s*>(.*)<\/\s*script\s*>/is", "<?php $1 ?>", $this->code);

        $this->tokens = token_get_all($this->code);

        $this->normalize_tokens();

        $this->add_close_tag();

        $this->prepare_tokens();

        $this->transforme_variable_in_string_to_concatenation();

        $this->remove_multi_dollar_pattern();

        $this->array_packing();
    }

    private function add_close_tag()
    {
        if($this->tokens[count($this->tokens) - 1]->id != T_CLOSE_TAG)
        {
            $token = array(T_CLOSE_TAG, "?>", $this->tokens[count($this->tokens) - 1]->str_num);
            $this->tokens[count($this->tokens)] = new Token($token);
        }
    }

    /*
     * 1) Преобразует массив токенов в массив объектов класса Token
     * Если изначальный токен - массив, то из него создается объект класса Token
     * иначе - из строкового токена создается массив и на его основе объект
     * 2) Удаляет токены, помеченные как ненужные в Tokens_Types::$IGNORE
     */
    public function normalize_tokens()
    {
        $tokens_count = count($this->tokens);

        for ($i = 0; $i < $tokens_count; ++$i) {
            if (!is_array($this->tokens[$i])) {

                $str_num = 0;

                //пытаемся узнать ID типа токена
                try {
                    if (token_name_for_defines($this->tokens[$i]) !== null) {
                        $id = constant(token_name_for_defines($this->tokens[$i]));
                        $this->tokens[$i] = array($id, $this->tokens[$i], $str_num);
                    } else {
                        echo "Undefined token type for token: " . $this->tokens[$i], " in line: " . $str_num . "<br>";
                    }
                } catch (Exception $e) {
                    echo 'Исключение: ', $e->getMessage(), "<br>";
                    die();
                }
            }

            $token = new Token($this->tokens[$i]);

            $this->tokens[$i] = $token;

            //Удаляем ненужные теги
            if ($token->is_ignore) {
                unset($this->tokens[$i]);
            }
        }

        //Восстанавливаем сплошную иднексацию в массиве токенов
        $this->tokens = array_values($this->tokens);
    }

    /*
     * 1) Связывает переменные в массиве Токенов - после выполнения все Токены, обозначавшие одну
     * переменную будут ссылаться на один и тот же объект типа Token
     * 2) Преобразует обращения к индексам массива из $a{2} в $a[2]
     * 3) Заменяет синтаксис обратных ковычек на вызов псевдофункции backtick
     */
    public function prepare_tokens()
    {
        $tokens_count = count($this->tokens);

        $vars = array();

        $for_removing = array();

        for ($i = 0; $i < $tokens_count; ++$i) {
            //Заносим в список переменных
            if ($this->tokens[$i]->is_var) {

                if (!key_exists($this->tokens[$i]->orig_str, $vars)) {
                    $vars[$this->tokens[$i]->orig_str] = $this->tokens[$i];
                } else {
                    $tokens[$i] = $vars[$this->tokens[$i]->orig_str];
                }

            }

            //Синтаксис $a{'b'} преобразуем в $a['b']
            if ($this->tokens[$i]->orig_str == '{' && ($this->tokens[$i - 1]->is_var || $this->tokens[$i]->orig_str == "]")) {
                $find_closing = 0;
                while ($this->tokens[$i + $find_closing]->orig_str != "}") {
                    ++$find_closing;
                }
                $this->tokens[$i]->orig_str = "[";
                $this->tokens[$i + $find_closing]->orig_str = "]";
            }

            //Синтаксис обратных ковычек `cmd` преобразуем в вызов псевдофункции backticks
            if ($this->tokens[$i]->name == "T_BACKTICK") {
                $find_closing = 1;



                while ( $this->tokens[$i + $find_closing]->name != "T_BACKTICK") {
                    ++$find_closing;

                }

                //Нужно взять с нулевого по $i - 1 (тк $i это символ ковычки), значит
                // ($i - 1) - 0 + 1  (последний - первый + 1)
                $before_backtick = array_slice($this->tokens, 0, $i);

                $back_name = new Token(array(T_STRING, "backtick", $this->tokens[$i]->str_num));
                $open_par = new Token(array(T_PAR_OPEN, "(", $this->tokens[$i]->str_num));
                $back_call = array($back_name, $open_par);

                $middle = array_merge($back_call, array_slice($this->tokens, $i + 1, $find_closing - 1));

                $close_par = new Token(array(T_PAR_CLOSE, ")", $this->tokens[$i]->str_num));

                //в $i + $find_close лежит ковычка, значит оффсет на 1 больше
                //количество = полседний - первый + 1
                $after_backtick = array_merge([$close_par], array_slice($this->tokens, $i + $find_closing + 1, count($this->tokens) - $i - $find_closing - 1 + 1));


                $this->tokens = array_merge($before_backtick, $middle, $after_backtick);
            }

            //Если переменной что-то присваевается
            if ($this->tokens[$i]->is_var && in_array($this->tokens[$i + 1]->name, TokenTypes::$T_EQUALS)) {
                //Проверяем присвоение переменной конкатенации строк
                if ($this->tokens[$i + 2]->name == "T_CONSTANT_ENCAPSED_STRING" && $this->tokens[$i + 3]->name == "T_CONCAT_OP") {
                    $find_last = 1;

                    while ($this->tokens[$i + 3 + $find_last]->name == "T_CONSTANT_ENCAPSED_STRING" && $this->tokens[$i + 3 + $find_last + 1]->name != "T_CONCAT_OP") {
                        ++$find_last;
                    }

                    $concat_part = array_slice($this->tokens, $i + 2, $i + 2 + $find_last + 1 - $i - 3 + 1);

                    $right_string = reverse_concatenation($concat_part);

                    $this->tokens[$i]->value = $right_string;


                }

                if ($this->tokens[$i + 2]->name == "T_CONSTANT_ENCAPSED_STRING" && $this->tokens[$i + 3]->name == "T_SEMICOLON")
                {

                }

            }

        }

        foreach ($for_removing as $index) {
            unset($this->tokens[$index]);
        }

        $this->tokens = array_values($this->tokens);
    }


    /*
     * Преобразует последовательность ${$var} в $$var
     */
    public function remove_multi_dollar_pattern()
    {
        $tokens_count = count($this->tokens);

        $vars = array();

        $for_removing = array();

        for ($i = 0; $i < $tokens_count; ++$i) {
            //Если встречается паттерн ${
            if ($this->tokens[$i]->id == T_DOLLAR && $this->tokens[$i + 1]->id == T_CURLY_PAR_OPEN) {
                //Ищем, где все это дело заканчивается
                $open_curly = $i + 1;

                $opened_par = 1;
                $offset = 1;
                while ($opened_par) {
                    ++$offset;

                    if ($this->tokens[$i + $offset]->id == T_CURLY_PAR_OPEN) {
                        $opened_par += 1;
                    }

                    if ($this->tokens[$i + $offset]->id == T_CURLY_PAR_CLOSE) {
                        $opened_par -= 1;
                    }
                }

                $close_curly = $i + $offset;

                //Удаляем первую и последнюю скобки
                $for_removing[] = $open_curly;
                $for_removing[] = $close_curly;
            }

        }

        foreach ($for_removing as $index) {
            unset($this->tokens[$index]);
        }

        $this->tokens = array_values($this->tokens);
    }

    /*
     * Преобразует использование переменных внутри строки в конкатенацию
     */
    public function transforme_variable_in_string_to_concatenation()
    {
        $tokens_count = count($this->tokens);

        $vars = array();

        $for_removing = array();

        for ($i = 0; $i < count($this->tokens); ++$i)
        {
            if($this->tokens[$i]->id == T_DOUBLE_QUOTES)
            {
                $for_removing[] = $i;
                ++$i;

                //До закрывающих ковычек
                while($this->tokens[$i + 1]->id != T_DOUBLE_QUOTES)
                {
                    if($this->tokens[$i]->id == T_CURLY_OPEN)
                    {
                        $for_removing[] = $i;
                        ++$i;

                        while($this->tokens[$i]->id != T_CURLY_PAR_CLOSE)
                        {
                            ++$i;
                        }

                        $for_removing[] = $i;

                        if($this->tokens[$i + 1]->id == T_DOUBLE_QUOTES)
                        {
                            break;
                        }

                    }

                    $token = new Token(array(T_CONCAT_OP, ".", 0));
                    $this->insert_one_token($i, $token);
                    $i += 2;
                }

                ++$i;

                $for_removing[] = $i;

                ++$i;

            }
        }

        foreach ($for_removing as $index)
        {
            unset($this->tokens[$index]);
        }

        $this->tokens = array_values($this->tokens);


    }

    public function insert_one_token($place, $token)
    {
        $after = array_slice($this->tokens, $place + 1);

        $this->tokens = array_merge(array_slice($this->tokens, 0, $place + 1), array($token), $after);
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
    public function array_packing()
    {
        $tokens_count = count($this->tokens);

        $for_removing = array();

        for ($i = 0; $i < $tokens_count; ++$i) {
            //Синатксис $a['abc'][foo($b)] преобразуется в поле класса Token
            if ($this->tokens[$i]->is_var && $this->tokens[$i + 1]->orig_str == "[") {
                $this->tokens[$i]->arr_indexes[] = "[";

                if (!in_array($i + 1, $for_removing)) {
                    $for_removing[] = $i + 1;
                }

                $arraing_index = 1;

                $opened_braces = 1;

                $offset = 2;

                while ($opened_braces != 0) {
                    if ($this->tokens[$i + $offset]->orig_str == "[") {
                        $opened_braces += 1;
                        if ($opened_braces == 1) {
                            $arraing_index += 1; //new ...[blabla]...
                        }
                    } elseif ($this->tokens[$i + $offset]->orig_str == "]") {
                        $opened_braces -= 1;
                        $this->tokens[$i]->arr_indexes[] = $this->tokens[$i + $offset]->orig_str; //закрывающую скобку тоже записываем

                        if (!in_array($i + $offset, $for_removing)) {
                            $for_removing[] = $i + $offset;
                        }
                        if ($this->tokens[$i + $offset + 1]->orig_str !== "[") //если не открывается новое измерение
                        {
                            break;
                        }
                        $offset += 1;
                        $opened_braces += 1;

                    }

                    $this->tokens[$i]->arr_indexes[] = $this->tokens[$i + $offset]->orig_str;
                    if (!in_array($i + $offset, $for_removing)) {
                        $for_removing[] = $i + $offset;
                    }

                    $offset += 1;
                }

            }

        }

        foreach ($for_removing as $index) {
            unset($this->tokens[$index]);
        }

        $this->tokens = array_values($this->tokens);

    }
}