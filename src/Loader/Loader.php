<?php
namespace CMS\Loader;

use App;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ViewFinderInterface;
use TwigBridge\Twig\Loader as BridgeLoader;
use YAML;

/**
 * Class Loader
 * @package CMS\Loader
 *
 * Reads the template from filesystem
 * and caches the non-Twig specific configurations
 *
 */
class Loader extends BridgeLoader
{
    /**
     * Template helper
     *
     * @var \CMS_Helper
     */
    protected $helper;

    public function __construct(Filesystem $files, ViewFinderInterface $finder, $extension = 'twig')
    {
        parent::__construct($files, $finder, $extension);

        $this->helper = App::make('cms.helper');

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
        $source = parent::getSource($name);

        return $this->parseSource($name, $source);
    }

    /**
     * Parses the raw source to fit Twig
     *
     * @param string $name
     * @param string $source
     * @return mixed|null|string
     * @throws \Twig_Error_Loader
     */
    private function parseSource($name, $source)
    {
        $parts = explode('===', $source);

        if (count($parts) > 2) {
            throw new \Twig_Error_Loader("Invalid template format.");
        }

        switch (count($parts)) {
            case 1:
                $source = $this->hasSourceOnly($parts);
                break;
            case 2:
                $source = $this->hasConfig($parts, $name);
                break;
            default:
                $source = null;
        }

        $source = $this->replaceSyntax($source, "{% page %}", "{% block cms_page %}{% endblock %}");

        return $source;
    }

    /**
     * If a file has no CMS config, it's returned as such
     *
     * @param array $parts
     * @return string
     */
    private function hasSourceOnly($parts)
    {
        return trim($parts[0]);
    }

    /**
     * Extracts the config and stores it in the CMS helper
     * before returning the Twig template source
     *
     * @param array $parts
     * @param string $name
     * @return string
     */
    private function hasConfig($parts, $name)
    {
        $config = YAML::parse($parts[0]);
        $source = trim($parts[1]);

        $h = $this->helper;

        $h->setConfig($name, $config);

        if ($h->hasConfigKey($name, 'title'))
        {
            $h->setBufferKey($name, 'title', $h->getConfigKey($name, 'title'));
        }

        if ($h->hasConfigKey($name, 'layout'))
        {
            $parentName = $h->getConfigKey($name, 'layout');
            $parentName = secure_string($parentName);

            if ($h->hasBufferKey($parentName, 'page') == false) {
                $h->setBufferKey($parentName, 'page', $name);

                $source = $this->appendSyntax($source,
                    "{% layout \"$parentName\" %}\n{% block cms_page %}");
                $source = $this->prependSyntax($source,
                    "{% endblock cms_page %}");
            }
        }

        return $source;
    }

    /**
     * Replaces parts of the Twig source
     *
     * @param string $source
     * @param string $replace
     * @param string $with
     * @return mixed
     */
    protected function replaceSyntax($source, $replace, $with)
    {
        return str_ireplace($replace, $with, $source);
    }

    /**
     * Appends code to the start of the Twig source
     *
     * @param string $source
     * @param string $syntax
     * @return string
     */
    protected function appendSyntax($source, $syntax)
    {
        return $syntax . "\n" . $source;
    }

    /**
     * Prepends code to the end of the Twig source
     *
     * @param string $source
     * @param string $syntax
     * @return string
     */
    protected function prependSyntax($source, $syntax)
    {
        return $source . "\n" . $syntax;
    }
}