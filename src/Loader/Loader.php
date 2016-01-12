<?php
namespace CMS\Loader;

use CMS_Parser as Parser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ViewFinderInterface;
use TwigBridge\Twig\Loader as BridgeLoader;

/**
 * Class Loader
 * @package CMS\Loader
 *
 * Reads the template from filesystem
 *
 */
class Loader extends BridgeLoader
{
    public function __construct(Filesystem $files, ViewFinderInterface $finder, $extension = 'twig')
    {
        parent::__construct($files, $finder, $extension);
    }

    /**
     * Returns the Twig template
     *
     * @param string $name
     * @return mixed|null|string
     * @throws \Twig_Error_Loader
     */
    public function getSource($name)
    {
        Parser::setName($name);

        $source = parent::getSource($name);
        return Parser::parse($source);
    }

}