<?php
namespace CMS\Extension;

class Layout extends \Twig_TokenParser
{
    public function parse(\Twig_Token $token)
    {
        if (null !== $this->parser->getParent()) {
            throw new \Twig_Error_Syntax('Multiple parents are forbidden.', $token->getLine(), $this->parser->getFilename());
        }

        $this->parser->setParent($this->setParent($this->parser));

        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
    }

    private function setParent(\Twig_Parser $parser)
    {
        $expr = $parser->getExpressionParser()->parseExpression();
        $parentName = config('cms.path.layouts') .'/'. $expr->getAttribute('value');
        $expr->setAttribute('value', $parentName);

        $helper = \App::make('cms.helper');
        $helper->setBufferKey($parentName, 'page', $parser->getFilename());

        return $expr;
    }

    public function getTag()
    {
        return 'layout';
    }
}
