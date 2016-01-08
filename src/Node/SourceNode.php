<?php
namespace CMS\Node;

use \Twig;
use Twig_Compiler;
use Twig_Node;

class SourceNode extends Twig_Node
{
    public function __construct($line, $tag)
    {
        parent::__construct([], [], $line, $tag);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        try {
            $source = $this->getGlobal('cms.'. $this->tag);
        } catch (\Exception $e) {
            return;
        }

        $compiler->write("echo '$source';");
    }

    private function getGlobal($key)
    {
        $array = Twig::getGlobals();

        return $array[$key];
    }
} 