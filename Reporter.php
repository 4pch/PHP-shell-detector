<?php

//include 'ShellSign.php';

/*
 * Содержит в себе обнаруженные в файле признаки шелла и
 * выводит их в нормальном виде
 */
class Reporter
{
    public $signs;

    public $filename;

    public function __construct($filename)
    {
        $signs = array();
        $this->filename = $filename;
    }

    public function add_sign($sign)
    {
        $this->signs[] = $sign;
    }

    public function display_signs()
    {
        if($this->signs == null)
        {
            return;
        }
        foreach ($this->signs as $sign)
        {
            $this->display_sign($sign);
        }
    }

    public function display_sign(ShellSign $sign)
    {
        $display_string = "";
        $display_string .= "Web shell sign detected in file: " . $this->filename . " ";
        $display_string .= "with level: " . $sign->level . " ";
        /*switch ($sign->type)
        {
            case STRING_LENGTH_MORE_MAX;
            case STRING_LENGTH_MORE_INSANE;
            case VARIABLE_CALL_AS_FUNCTION;
            case CALLBACK_PARAMETER_FORGERY;
            case CALLBACK_PARAMETER_SUSPICIOUS;
            case UNWANTED_FUNCTION_CALL;
            case EVAL_CALL;
            case E_MODIFIER_IN_PCRE;
            case DBMS_FUNCTIONS_CALL;
            case FILE_UPLOADING;

        }*/

        $display_string .= "in string: " . $sign->function->str_num . " ";
        $display_string .= "string content: " . $sign->function->orig_str . "(";

        if($sign->arguments != 0)
        {
            foreach ($sign->arguments as $argument)
            {
                foreach ($argument as $arg_token)
                {
                    $display_string .= $arg_token->orig_str;
                }
                $display_string .= ", ";
            }
        }
        $display_string .= ")";

        $display_string .= "<br>";

        echo $display_string;
    }
}