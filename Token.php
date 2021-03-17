<?php

class Token
{
    //ID из массива от функции token_get_all
    public $id;

    //Результат token_name или собственного define
    public $name;

    //Оригинальная строка в коде
    public $orig_str;

    //Номер строки
    public $str_num;

    //Если токен - массив, то тут индексы обращения к нему
    public $arr_indexes;

    //Является ли переменной
    public $is_var;

    public $is_ignore;

    //используется, если токен является переменной
    public $value;

    //Менялась ли переменная с момента последнего обновления $value
    public $has_changed;

    //Заполнена ли переменная данными от пользователя
    public $is_user_defined;

    public function __construct($token)
    {
        $this->id = $token[0];

        //todo: проверить нулл ли возвращает
        //Если для этого ID не задано стандартное строковое имя - задаем свое
        if (token_name($this->id) !== "UNKNOWN")
        {
            $this->name = token_name($this->id);
        }
        else
        {
            $this->name = token_name_for_defines($token[1]);
        }

        $this->orig_str = $token[1];

        $this->str_num = $token[2];

        //Формат хранения ('индекс вложенности', 'строка значения индекса')
        $this->arr_indexes = array();

        $this->is_var = $this->name === "T_VARIABLE";

        $this->is_ignore = $this->is_ignore();



    }

    public function is_ignore()
    {
        return in_array($this->name, Tokens_Types::$IGNORE);
    }


}