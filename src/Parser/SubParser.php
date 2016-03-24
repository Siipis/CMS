<?php
namespace Siipis\CMS\Parser;


abstract class SubParser
{
    protected $parent;

    public function __construct(Parser $parent)
    {
        $this->parent = $parent;
    }


    abstract public function parse($source);
}