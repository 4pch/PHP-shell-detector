<?php

define("T_BACKTICK", 1000);
define("T_PAR_OPEN", 1001);
define("T_PAR_CLOSE", 1002);
define("T_EQUAL", 1003);
define("T_SQ_OPEN", 1004);
define("T_SQ_CLOSE", 1005);
define("T_CURLY_PAR_OPEN", 1006);
define("T_CURLY_PAR_CLOSE", 1007);
define("T_SEMICOLON", 1008);

class Tokens_Types
{
    //Пропуск
    public static $IGNORE = array(
        T_WHITESPACE,
        T_DOC_COMMENT,
        T_COMMENT,
    );

    //Способы присваивания
    public static $T_EQUALS = array(
        T_DIV_EQUAL,
        "T_EQUAL",
    );

    //Токены, не определенные в PHP
    public static $DEF_TO_STRING = array(
        '(' => 'T_PAR_OPEN',
        ')' => 'T_PAR_CLOSE',
        '`' => 'T_BACKTICK',
        ';' => 'T_SEMICOLON',
        '[' => 'T_SQ_OPEN',
        ']' => "T_SQ_CLOSE",
        '{' => "T_CURLY_PAR_OPEN",
        '}' => "T_CURLY_PAR_CLOSE",
        '.' => "T_CONCAT_OP",
        "=" => "T_EQUAL",

    );
}

function token_name_for_defines($token_string)
{
    return Tokens_Types::$DEF_TO_STRING[$token_string];
}