<?php
namespace Siipis\CMS\Parser;

use CMS_Helper as Helper;

class Parser
{
    protected $parsers;

    protected $name;

    protected $layout;

    public function __construct($parsers = [])
    {
        $this->parsers = [];

        $parsers = array_merge($parsers, config('cms.parsers'));
        foreach ($parsers as $parser)
        {
            $this->addParser($parser);
        }
    }

    public function addParser($class)
    {
        $parser = new $class($this);

        if (!in_array($parser, $this->parsers))
        {
            array_push($this->parsers, $parser);
        }
    }

    public function parse($source)
    {
        foreach ($this->parsers as $parser)
        {
            $source = $parser->parse($source);
        }

        return $source;
    }

    public function split($source)
    {
        return explode('===', $source);
    }

    /*
    |--------------------------------------------------------------------------
    | Getters and setters
    |--------------------------------------------------------------------------
    |
    | Global getters and setters
    |
    */
    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function hasName()
    {
        return isset($this->name);
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function hasLayout()
    {
        return isset($this->layout);
    }


    /*
    |--------------------------------------------------------------------------
    | Helper shortcut functions
    |--------------------------------------------------------------------------
    |
    | See \CMS\Layout\Scaffolding for more
    |
    */

    // Config
    public function configHas($key, $withLayout = false)
    {
        $name = $withLayout ? $this->getLayout() : $this->getName();

        return Helper::hasConfigKey($name, $key);
    }

    public function getConfig($key, $withLayout = false)
    {
        $name = $withLayout ? $this->getLayout() : $this->getName();

        return Helper::getConfigKey($name, $key);
    }

    public function setConfig($key, $value)
    {
        Helper::setConfigKey($this->getName(), $key, $value);
    }

    // Buffer
    public function bufferHas($key, $withLayout = false)
    {
        $name = $withLayout ? $this->getLayout() : $this->getName();

        return Helper::hasBufferKey($name, $key);
    }

    public function getBuffer($key, $withLayout = false)
    {
        $name = $withLayout ? $this->getLayout() : $this->getName();

        return Helper::getBufferKey($name, $key);
    }

    public function setBuffer($key, $value)
    {
        return Helper::setBufferKey($this->getName(), $key, $value);
    }

    public function buffer($key, $withLayout, $value = null)
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

    // Attribute
    public function attributes()
    {
        return Helper::getAttributes($this->getName());
    }

    public function setAttributes($attr)
    {
        Helper::setAttributes($this->getName(), $attr);
    }

    public function setAttribute($key, $value)
    {
        Helper::setAttribute($this->getName(), $key, $value);
    }
}