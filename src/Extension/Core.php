<?php
namespace Siipis\CMS\Extension;

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
            new \Siipis\CMS\Extension\Menu(),
            new \Siipis\CMS\Extension\Page(),
            new \Siipis\CMS\Extension\Partial(),
            new \Siipis\CMS\Extension\Title(),

            new \Siipis\CMS\Extension\OverrideExtends(),
        ];
    }
}