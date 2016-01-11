<?php
namespace CMS\Parser;


class RequestParser implements SubParserInterface
{
    protected $parent;

    public function __construct(Parser $parent)
    {
        $this->parent = $parent;
    }

    public function parse($source)
    {
        return $source;
    }

} 