<?php

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
        $display_string .= "Web shell sign detected ";

        $display_string .= "in string: " . $sign->function->str_num . " ";
        $display_string .= "string content: " . $sign->function->orig_str . "(";

        if($sign->arguments != 0)
        {
            for($i = 1; $i <= count($sign->arguments); ++$i)
            {
                foreach ($sign->arguments[$i] as $arg_token)
                {
                    $display_string .= $arg_token->orig_str;
                }
                if($i < count($sign->arguments))
                {
                    $display_string .= ", ";
                }
            }
        }
        $display_string .= ")";

        $display_string .= "<br>";

        echo $display_string;
    }
}