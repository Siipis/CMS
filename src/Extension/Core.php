<?php
namespace CMS\Extension;

use Twig_Extension;
use Twig_SimpleFunction;

class Core extends Twig_Extension
{
    public function getName()
    {
        return "CMS";
    }

    public function getTokenParsers()
    {
        return [
            new \CMS\Extension\Menu(),
            new \CMS\Extension\Partial(),
            new \CMS\Extension\Title(),

            new \CMS\Extension\OverrideExtends(),
        ];
    }
}