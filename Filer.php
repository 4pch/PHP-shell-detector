<?php

define("MAX_LINE_LENGTH", 200);
define("INSANE_LINE_LENGTH", 2000);
define("MAX_TAB_COUNT", 30);

class Filer
{
    public $full_file_path;

    public $lines_count;

    public $tokens;

    public function __construct($full_file_path)
    {
        $this->full_file_path = $full_file_path;

        $this->lines_count = count(file($full_file_path));
    }

    public function analyze_file()
    {
        echo "File: " . $this->full_file_path . " Lines: " , $this->lines_count . "<br>";
        echo "-------------------------------------------------" . "<br>" . "<br>";

        $lines_result = $this->analyze_file_lines();

        $code_result = $this->analyze_file_code();

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
        $code = file_get_contents($this->full_file_path);

        //Открывающие теги <? заменяем на <?php, иначе неправильно разбивает на токены сам PHP
        $code = preg_replace("/<\?(?!php|=)/", "<?php ", $code);

        $this->tokens = token_get_all($code);

        $this->tokens = normalize_tokens($this->tokens);

        //Дописываем в конец закрывающий тег \?\>, иначе проблемы с разбиением на токены
        $this->add_close_tag();

        $this->tokens = prepare_tokens($this->tokens);

        $this->tokens = array_packing($this->tokens);

        $this->tokens = markup_user_defined_variables($this->tokens);

        $result = analyze($this->tokens);

        return $result;
    }

    private function add_close_tag()
    {
        if($this->tokens[count($this->tokens) - 1]->id != T_CLOSE_TAG)
        {
            $token = array(T_CLOSE_TAG, "?>", $this->tokens[count($this->tokens) - 1]->str_num);
            $this->tokens[count($this->tokens)] = new Token($token);
        }
    }
}