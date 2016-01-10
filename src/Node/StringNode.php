<?php
namespace CMS\Node;

use CMS_Helper as Helper;

class StringNode extends \Twig_Node
{
    public function __construct($line, $tag)
    {
        parent::__construct([], [], $line, $tag);
    }

    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        try {
            $template = $compiler->getFilename();

            $string = Helper::getBufferKey($template, $this->tag);

        } catch (\Exception $e) {
            $string = "[$this->tag]"; // display a placeholder
        }

        $compiler->raw("echo secure_string('$string');");
    }
}