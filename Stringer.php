<?php


class Stringer
{
    public $tokens;

    private $reporter;

    public $tokenizer;

    public $analyzer;

    public $code;

    public function __construct($code)
    {
        $this->tokenizer = new Tokenizer($code);

        $this->tokens = $this->tokenizer->tokens;

        $this->reporter = new Reporter("");

        $this->analyzer =  new Analyzer($this->reporter, $this->tokens);


    }

    public function analyze_code()
    {
        return $this->analyzer->analyze();
    }
}