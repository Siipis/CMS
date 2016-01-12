<?php
namespace CMS\Template;

use CMS_Helper as Helper;

abstract class Template extends \TwigBridge\Twig\Template
{
    public function display(array $context, array $blocks = [])
    {
        $attr = Helper::getAttributes($this->getTemplateName());

        parent::display(array_merge($context, $attr), $blocks);
    }
} 