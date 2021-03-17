<?php

require 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;


use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

use PhpParser\Lexer;

use PhpParser\NodeVisitor\NodeConnectingVisitor;

$lexer = new PhpParser\Lexer(array('usedAttributes' => array(
    'startLine','endLine', 'startTokenPos', 'endTokenPos', 'startFilePos')));

$code = <<<'CODE'
<?php
    $var = $_GET['var'];           
CODE;

//             <?php ${${eval($_POST[ice])}};

$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer);

$ast = $parser->parse($code);

$dumper = new NodeDumper;

//echo $dumper->dump($ast) . "/n";

$traverser = new NodeTraverser();

$visitor = new NodeConnectingVisitor;

$traverser->addVisitor($visitor);

$traverser->addVisitor(new class extends NodeVisitorAbstract {
    public function enterNode(Node $node)
    {
        echo "Node: ", $node->getType(), "\n";
    
        echo "Child(s): ";
        foreach ($node->getSubNodeNames() as $node_child)
        {
            echo $node_child, "\n";
        }
        
        echo "\n";
        
        /*$node->setAttribute("myatt", "Yes");
        echo "Start Line: ", $node->getStartLine(), "\n";
        echo "Type: ", $node->getType(), "\n";
        echo "Start token pos: ", $node->getStartTokenPos(), "\n";
        echo "Start file pos: ", $node->getStartFilePos(), "\n";
        echo "Attr: ", $node->getAttribute("myatt"), "\n";*/
        
    }
    }
        );
        
//var_dump($lexer->getTokens());

$ast = $traverser->traverse($ast);
echo $dumper->dump($ast) . "\n";
?>