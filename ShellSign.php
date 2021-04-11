<?php

/*case STRING_LENGTH_MORE_MAX;
case STRING_LENGTH_MORE_INSANE;
case USER_DEF_VARIABLE_CALL_AS_FUNCTION;
case VARIABLE_CALL_AS_FUNCTION;
case CALLBACK_PARAMETER_FORGERY;
case CALLBACK_PARAMETER_SUSPICIOUS;
case UNWANTED_FUNCTION_CALL;
case UNWANTED_FUNCTION_WITH_USER_DEF_CALL;
case EVAL_CALL;
case E_MODIFIER_IN_PCRE;
case E_MODIFIER_IN_PCRE_BY_PARAMETER;
case SUSPICIOUS_MODIFIER_IN_PCRE;
case DBMS_FUNCTIONS_CALL;
case FILE_UPLOADING;*/

class ShellSign
{
    public $str_number;
    public $function;
    public $level;
    public $type;
    public $arguments;
    public $vuln_arg;

    //Уровень должен определяться по типу вообще говоря

    public function __construct($function_token, $arguments, $vuln_arg, $type)
    {
        $this->str_number = $function_token->str_num;

        $this->function = $function_token;

        $this->level = 0;

        $this->arguments = $arguments;

        $this->vuln_arg = $vuln_arg;

        $this->type = $type;
    }

    public function set_type($type)
    {
        $this->type = $type;
    }
}