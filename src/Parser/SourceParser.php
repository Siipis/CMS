<?php
namespace CMS\Parser;


class SourceParser implements SubParserInterface
{
    protected $parent;

    public function __construct(Parser $parent)
    {
        $this->parent = $parent;
    }

    public function parse($source)
    {
        $split = $this->parent->split($source);

        return trim(end($split)); // Return last item of array
    }

} 