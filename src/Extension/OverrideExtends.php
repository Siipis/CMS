<?php
namespace CMS\Extension;

/**
 * Override \Twig_TokenParser_Extends
 *
 */

use CMS_Helper as Helper;

class OverrideExtends extends \Twig_TokenParser
{
    public function parse(\Twig_Token $token)
    {
        if (!$this->parser->isMainScope()) {
            throw new \Twig_Error_Syntax('Cannot extend from a block.', $token->getLine(), $this->parser->getFilename());
        }

        if (null !== $this->parser->getParent()) {
            throw new \Twig_Error_Syntax('Multiple extends tags are forbidden.', $token->getLine(), $this->parser->getFilename());
        }

        $this->parser->setParent($this->parser->getExpressionParser()->parseExpression());

        $parentName = $this->parser->getParent()->getAttribute('value');
        $name = $this->parser->getFilename();

        // If the parent has no buffer values, transfer them from the child template
        if (is_null(Helper::getBuffer($parentName))) {
            Helper::setBuffer($parentName, Helper::getBuffer($name));
        }

        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
    }

    public function getTag()
    {
        return 'extends';
    }
}
