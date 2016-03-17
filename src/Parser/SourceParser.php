<?php
namespace CMS\Parser;


class SourceParser extends SubParser
{
    public function parse($source)
    {
        $split = $this->parent->split($source);

        return trim(end($split)); // Return last item of array
    }

} 