<?php

class ShellSign
{
    public $str_number;
    public $function;
    public $level;
    public $type;
    public $arguments;
    public $vuln_arg;


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