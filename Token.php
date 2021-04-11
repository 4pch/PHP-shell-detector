<?php


/*
 * Представляет из себя один встроенный токен PHP
 */
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

    //Получена ли строка от конкатенации
    public $is_concatenaed;

    public function __construct($token)
    {
        $this->id = $token[0];

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

        $this->is_var = ($this->name === "T_VARIABLE");

        $this->is_ignore = $this->is_ignore();

        $this->is_user_defined = false;

        $this->has_changed = false;

        $this->is_concatenaed = false;
    }

    public function is_ignore()
    {
        return in_array($this->id, TokenTypes::$IGNORE);
    }


}