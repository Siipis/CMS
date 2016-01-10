<?php
namespace CMS\Loader;

use App;
use \CMS_Helper as Helper;
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
    protected $name;

    protected $layout;

    protected $content;

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
        $this->setName($name);

        $source = parent::getSource($name);
        return $this->parseSource($source);
    }

    /*
    |--------------------------------------------------------------------------
    | Source parsers
    |--------------------------------------------------------------------------
    |
    | Parses the template contents and returns a Twig source code
    |
    */
    private function parseSource($source)
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
                $source = $this->hasConfig($parts);
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
        $source = trim($parts[0]);

        $this->setContent($source);

        return $source;
    }

    /**
     * Extracts the config and stores it in the CMS helper
     * before returning the Twig template source
     *
     * @param array $parts
     * @return string
     */
    private function hasConfig($parts)
    {
        $config = YAML::parse($parts[0]);
        $source = trim($parts[1]);

        $this->setContent($source);

        // Caches the template config
        Helper::setConfig($this->getName(), $config);

        return $this
            ->addLayout()
            ->addTitle()
            ->getContent();
    }

    /*
    |--------------------------------------------------------------------------
    | Parser helpers
    |--------------------------------------------------------------------------
    |
    | Handle config tags when necessary
    |
    */
    protected function addTitle()
    {
        if ($this->configHas('title')) {
            $this->buffer('title', true);
        }

        return $this;
    }

    protected function addLayout()
    {
        if ($this->configHas('layout')) {
            $name = $this->getName();
            $source = $this->getContent();
            $layout = secure_string($this->configGet('layout'));

            $this->setLayout(config('cms.path.layouts') .'/'. $layout);

            if ($this->bufferHas('page', true) == false) {
                Helper::setBufferKey($layout, 'page', $name);

                $source = $this->appendSyntax($source,
                    "{% layout \"$layout\" %}\n{% block cms_page %}");
                $source = $this->prependSyntax($source,
                    "{% endblock cms_page %}");
            }

            $this->setContent($source);
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper shortcut functions
    |--------------------------------------------------------------------------
    |
    | See \CMS\Layout\Scaffolding for more
    |
    */
    protected function configHas($key)
    {
        return Helper::hasConfigKey($this->getName(), $key);
    }

    protected function configGet($key)
    {
        return Helper::getConfigKey($this->getName(), $key);
    }

    protected function bufferHas($key, $withLayout = false)
    {
        $name = $withLayout ? $this->getLayout() : $this->getName();

        return Helper::hasBufferKey($name, $key);
    }

    protected function buffer($key, $withLayout, $value = null)
    {
        $name = $this->getName();

        // If no value is defined, assume it resides in the config
        if (is_null($value)) {
            $value = Helper::getConfigKey($name, $key);
        }

        if (is_null($value)) {
            throw new \InvalidArgumentException("Cannot buffer an empty value.");
        }

        // If a layout is present, add the value to it's buffer instead
        $name = $withLayout ? $this->getLayout() : $this->getName();

        Helper::setBufferKey($name, $key, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | Syntax functions
    |--------------------------------------------------------------------------
    |
    | Edits the Twig template source
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Getters and setters
    |--------------------------------------------------------------------------
    |
    | Various getters and setters to enable cleaner code
    |
    */

    public function getName()
    {
        if (is_null($this->name)) {
            throw new \UnexpectedValueException("No template name is set.");
        }


        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getLayout()
    {
        if (is_null($this->layout)) {
            throw new \UnexpectedValueException("No layout name is set.");
        }

        return $this->layout;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function getContent()
    {
        if (is_null($this->content)) {
            throw new \UnexpectedValueException("No layout name is set.");
        }

        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

}