<?php
namespace CMS\Extension;

use CMS\Node\SourceNode;
use Twig_Token;
use Twig_TokenParser;

class Page extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        return new SourceNode($token->getLine(), $this->getTag());
    }

    public function getTag()
    {
        return 'page';
    }
} 