<?php
namespace CMS\Loader;

use App;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ViewFinderInterface;
use TwigBridge\Twig\Loader as BridgeLoader;
use YAML;

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

    public function getSource($name)
    {
        $source = parent::getSource($name);

        return $this->parseSource($name, $source);
    }

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

    private function hasSourceOnly($parts)
    {
        return trim($parts[0]);
    }

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

    protected function replaceSyntax($source, $replace, $with)
    {
        return str_ireplace($replace, $with, $source);
    }

    protected function appendSyntax($source, $syntax)
    {
        return $syntax . "\n" . $source;
    }

    protected function prependSyntax($source, $syntax)
    {
        return $source . "\n" . $syntax;
    }
}