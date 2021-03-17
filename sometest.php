<?php

include "analyzer.php";

include "tokenizer.php";


$code = '<?php $a=$_GET["f"]; $a("b")';

$tokens = token_get_all($code);

foreach ($tokens as $index => $token)
{
    if(is_array($token))
    {
        $token[0] = token_name($token[0]);
    }

    print_r($token);
    echo "<br>";
}

echo "<br>";
echo "<br>";
echo "<br>";
echo "Стало";

$tokens = normalize_tokens($tokens);

$tokens = prepare_tokens($tokens);

analyze($tokens);

foreach ($tokens as $index => $token)
{
    if(is_array($token))
    {
        $token[0] = token_name($token[0]);
    }

    print_r($token);
    echo "<br>";
}

var_dump($tokens);

/*
class A
{
    public $a;
}

$A = new A;
$A->a = 10;

$B = new A();
$B->a = 20;

echo $A->a;
echo $B->a;

$tokens = array($A, $B);

$stack = array();
$stack["A"] = $A;

$tokens[1] = $stack ["A"];

unset($stack);

$tokens[0]->a = 100;

echo $tokens[1]->a;
*/