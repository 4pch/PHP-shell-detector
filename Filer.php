<?php

include 'Reporter.php';
include 'Analyzer.php';
include 'Tokenizer.php';

define("MAX_LINE_LENGTH", 200);
define("INSANE_LINE_LENGTH", 2000);
define("MAX_TAB_COUNT", 30);

class Filer
{
    public $full_file_path;

    public $lines_count;

    public $tokens;

    private $reporter;

    public $tokenizer;

    public $analyzer;

    public $code;

    public function __construct($full_file_path)
    {
        $this->full_file_path = $full_file_path;

        $this->code = file_get_contents($this->full_file_path);

        $this->lines_count = count(file($this->full_file_path));

        echo "File: " . $this->full_file_path . " Lines: " , $this->lines_count . "<br>";
        echo "-------------------------------------------------" . "<br>" . "<br>";


        $this->reporter = new Reporter($this->full_file_path);

        $this->tokenizer = new Tokenizer($this->code);

        $this->tokens = $this->tokenizer->tokens;

        $this->analyzer = new Analyzer($this->reporter, $this->tokens);
    }

    public function analyze_file()
    {

        $lines_result = $this->analyze_file_lines();

        $code_result = $this->analyze_file_code();

        $this->reporter->display_signs();

        return $lines_result || $code_result;
    }

    private function analyze_file_lines()
    {
        $source_file = fopen($this->full_file_path, 'r');

        $current_line = 0;

        $for_return = false;

        while(!feof($source_file))
        {
            $line = fgets($source_file);

            if(strlen($line) > INSANE_LINE_LENGTH)
            {
                echo "Line " . $current_line . " with length more than insane" . "<br>";
                $for_return = true;
            }

            elseif(strlen($line) > MAX_LINE_LENGTH)
            {
                echo "Line " . $current_line . " with length more than max" . "<br>";
                $for_return = true;
            }

            //count of \t
            $tabulate_count = substr_count($line, "\t");

            if($tabulate_count > MAX_TAB_COUNT)
            {
                echo "Line " . $current_line . " with more than max count of tab" . "<br>";
                $for_return = true;
            }

            ++$current_line;
        }

        return $for_return;
    }

    private function analyze_file_code()
    {
        return $this->analyzer->analyze();
    }
}
