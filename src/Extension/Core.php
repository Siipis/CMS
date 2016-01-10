<?php
namespace CMS\Extension;

use Twig_Extension;
use Twig_SimpleFunction;

class Core extends Twig_Extension
{
    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return "CMS";
    }

    /**
     * Returns list of token parsers.
     *
     * @return array|\Twig_TokenParserInterface[]
     */
    public function getTokenParsers()
    {
        return [
            new \CMS\Extension\Layout(),
            new \CMS\Extension\Menu(),
            new \CMS\Extension\Partial(),
            new \CMS\Extension\Title(),
        ];
    }

    /**
     * Returns list of functions to add to existing list.
     *
     * @return array|\Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            //
        );
    }
}