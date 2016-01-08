<?php
namespace CMS\Node;

use \Twig;
use Twig_Compiler;
use Twig_Node;

class StringNode extends Twig_Node
{
    public function __construct($line, $tag)
    {
        parent::__construct([], [], $line, $tag);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        try {
            $string = string_secure($this->getGlobal('cms.'. $this->tag));
        } catch (\Exception $e) {
            $string = "";
        }

        $compiler->raw("echo '$string';");
    }

    private function getGlobal($key)
    {
        $array = Twig::getGlobals();

        return $array[$key];
    }
}