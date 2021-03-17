<?php

$code = <<<'CODE'
<?php   system($_GET['f']);
 
 function fuf($a)
 {
    return $a;
 }
 
 fuf(10);
 
 $b = "AUF";           
CODE;

$tokens = token_get_all($code);



foreach ($tokens as $token)
{
    if(is_array($token))
    {
        $token[0] = token_name($token[0]);
    }

    var_dump($token);

    echo "<br>";

}

//print_r($tokens);