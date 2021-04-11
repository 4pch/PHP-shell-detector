<?php

include "Analyzer.php";

include "Tokenizer.php";

/*
$code = <<<'EOT'
<?php ${${eval($_GET[ice])}};?>
EOT;


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
*/
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

$abcd = "system";
${"a".f() ."cd"}("whoami");

function f()
{
    return "b";
}