<?php
namespace CMS\Parser;


interface SubParserInterface
{
    public function __construct(Parser $parent);

    public function parse($source);
}