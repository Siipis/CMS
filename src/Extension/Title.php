<?php
namespace CMS\Extension;

use CMS\Node\StringNode;
use Twig_Token;
use Twig_TokenParser;

class Title extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        return new StringNode($token->getLine(), $this->getTag());
    }

    public function getTag()
    {
        return 'title';
    }
} 