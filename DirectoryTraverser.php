<?php

include 'Filer.php';

//todo: сокетное взаимодействие

class DirectoryTraverser
{
    public $dirpath;

    private $ignored_files;

    private $clear_files;

    private $invalid_php;

    private $malware_files;

    private $scanned_files;

    private $total_lines_scanned;

    public function __construct($target_name)
    {
        $this->dirpath = $target_name;

        $this->ignored_files = array();

        $this->clear_files  = array();

        $this->invalid_php  = array();

        $this->malware_files = array();

        $this->scanned_files = 0;

        $this->total_lines_scanned = 0;
    }

    public function traverse()
    {
        $directory = opendir($this->dirpath);

        if(!$directory)
        {
            //todo: handle
        }

        echo "Dir: " . $this->dirpath . "<br>";
        echo "####################################################################################" . "<br>";

        while (($entity_name = readdir($directory)) !== false)
        {
            if(is_dir($this->dirpath . "\\" . $entity_name))
            {
                if ($entity_name == "." || $entity_name == "..")
                {
                    continue;
                }
                //Если встречаем подпапку, то запускаем аналогичный процесс для нее
                else
                {
                    $traverser = new DirectoryTraverser($this->dirpath . "\\" . $entity_name);

                    $traverser->traverse();

                    //и собираем ее статистику
                    $this->concatenate_statistic($traverser);
                }

            }

            if(is_file($this->dirpath . "\\" . $entity_name))
            {
                $ext = pathinfo($this->dirpath . "\\" . $entity_name,  PATHINFO_EXTENSION);

                //todo: это нужно заменить и проверять еще txt и фотки
                if($ext != "php")
                {
                    $this->ignored_files[] = $this->dirpath . "\\" . $entity_name;
                    continue;
                }

                //Проверяем является ли файл правильным по ситнаксису
                if(!$this->is_php_file_valid($this->dirpath . "\\" . $entity_name))
                {
                    $this->invalid_php[] = $this->dirpath . "\\" . $entity_name;
                    continue;
                }

                $filer = new Filer($this->dirpath . "\\" . $entity_name);

                $this->total_lines_scanned += $filer->lines_count;

                $result = $filer->analyze_file();

                //Если обнаружены признаки шеллов
                if($result)
                {
                    $this->malware_files[] = $this->dirpath . "\\" . $entity_name;
                }
                //Если не обнаружены признаки шеллов
                else
                {
                    $this->clear_files[] = $this->dirpath . "\\" . $entity_name;
                }

                $this->scanned_files += 1;
            }

        }
    }

    public function is_php_file_valid($full_file_path)
    {
        $php_command = "C: && cd C:\\xampp\\php && php.exe -l \"$full_file_path\"";

        $output = array();

        $ret_code = 0;

        exec($php_command , $output, $ret_code);

        $found = false;

        //Если синтаксис верен, то в выводе команды php -l будет содержаться эта фраза
        foreach ($output as $line)
        {
            if(($res = stripos($line, "No syntax errors detected in")) !== false)
            {
                $found = true;
            }
        }

        return $found;
    }

    public function concatenate_statistic(DirectoryTraverser $traverser)
    {
        foreach ($traverser->ignored_files as $filename)
        {
            $this->ignored_files[] = $filename;
        }

        foreach ($traverser->clear_files as $filename)
        {
            $this->clear_files[] = $filename;
        }

        foreach ($traverser->invalid_php as $filename)
        {
            $this->invalid_php[] = $filename;
        }

        foreach ($traverser->malware_files as $filename)
        {
            $this->malware_files[] = $filename;
        }

        $this->scanned_files += $traverser->scanned_files;

        $this->total_lines_scanned += $traverser->total_lines_scanned;
    }

    public function print_statistics()
    {
        echo "Scanned: " . $this->scanned_files . " files" . "<br>";

        echo "Total lines count: " . $this->total_lines_scanned . "<br>";

        echo "Including: " . "<br>";

        echo "Ignored files: " . count($this->ignored_files) . "<br>";

        foreach ($this->ignored_files as $filename)
        {
            echo $filename . "<br>";
        }

        echo "Clear files: " . count($this->clear_files) . "<br>";

        foreach ($this->clear_files as $filename)
        {
            copy($filename, "S:\\Учеба\\Диплом\\clear\\" . pathinfo($filename, PATHINFO_BASENAME));
            echo $filename . "<br>";
        }

        echo "Invalid files: " . count($this->invalid_php) . "<br>";

        foreach ($this->invalid_php as $filename)
        {
            echo $filename . "<br>";
        }

        echo "Malware files: " . count($this->malware_files) . "<br>";

        foreach ($this->malware_files as $filename)
        {
            echo $filename . "<br>";
        }
    }
};

