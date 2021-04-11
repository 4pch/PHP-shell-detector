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
define("T_DOLLAR", 1009);
define("T_COMMA", 1010);
define("T_LOGICAL_INVERSE", 1011);
define("T_CONCAT_OP", 1012);
define("T_IS_GREATER", 1013);
define("T_IS_LESS", 1014);
define("T_DOUBLE_QUOTES", 1015);
define("T_FORWARD_SLASH", 1016);
define("T_BYTE_OR", 1017);
define("T_BYTE_AND", 1018);
define("T_ARIF_MOD", 1019);
define("T_MINUS", 1020);
define("T_QUESTION_SIGN", 1021);
define("T_COLON", 1021);
define("T_ERROR_HANDLER", 1022);
define("T_PLUS", 1023);
define("T_BYTE_XOR", 1024);
define("T_MULTIPLE", 1025);
define("T_BYTE_INVERSE", 1026);


class TokenTypes
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
        T_EQUAL,
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
        "$" => "T_DOLLAR",
        "," => "T_COMMA",
        "!" => "T_LOGICAL_INVERSE",
        ">" => "T_IS_GREATER",
        "<" => "T_IS_LESS",
        "\"" => "T_DOUBLE_QUOTES",
        "/" => "T_FORWARD_SLASH",
        "|" => "T_BYTE_OR",
        "&" => "T_BYTE_AND",
        "%" => "T_ARIF_MOD",
        "-" => "T_MINUS",
        "?" => "T_QUESTION_SIGN",
        ":" => "T_COLON",
        "@" => "T_ERROR_HANDLER",
        "+" => "T_PLUS",
        "^" => "T_BYTE_XOR",
        "*" => "T_MULTIPLE",
        "~" => "T_BYTE_INVERSE",
    );
}

function token_name_for_defines($token_string)
{
    return TokenTypes::$DEF_TO_STRING[$token_string];
}