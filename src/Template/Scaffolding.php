<?php
namespace CMS\Template;

/**
 * Class Scaffolding
 * @package CMS\Template
 *
 * Stores the template config
 * and provides a cache for rendering
 *
 * Can be accessed anywhere by calling App::make('cms.helper');
 *
 */

class Scaffolding
{
    /**
     * Values to be rendered by Twig_Nodes
     *
     * @var array
     */

    protected $buffer;

    /**
     * Template configurations
     *
     * @var array
     */
    protected $configs;

    /**
     * Template variables
     *
     * @var array
     */
    protected $attributes;

    public function __construct()
    {
        $this->buffer = [];
        $this->configs = [];
        $this->attributes = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Config
    |--------------------------------------------------------------------------
    |
    | Getters and setters for the config helper
    |
    */

    public function getConfig($template)
    {
        if (!$this->exists($this->configs, $template)) {
            return null;
        }

        return $this->configs[$template];
    }

    public function setConfig($template, $config)
    {
        if (!is_array($config)) {
            throw new \InvalidArgumentException;
        }

        $this->configs[$template] = $config;
    }

    public function hasConfig($template)
    {
        return $this->exists($this->configs, $template);
    }

    public function hasConfigKey($template, $key)
    {
        return $this->exists($this->configs, $template, $key);
    }

    public function getConfigKey($template, $key)
    {
        if (!$this->exists($this->configs, $template, $key)) {
            return null;
        }

        return $this->configs[$template][$key];
    }

    public function setConfigKey($template, $key, $value)
    {
        $this->configs[$template][$key] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Template attributes
    |--------------------------------------------------------------------------
    |
    | Variables that are being sent to the view
    |
    */

    public function getAttributes($template)
    {
        if (!$this->exists($this->attributes, $template)) {
            return null;
        }

        return $this->attributes[$template];
    }

    public function setAttributes($template, $attr)
    {
        if (!is_array($attr)) {
            throw new \InvalidArgumentException;
        }

        $this->configs[$template] = $attr;
    }

    public function getAttribute($template, $key)
    {
        if (!$this->exists($this->attributes, $template, $key)) {
            return null;
        }

        return $this->attributes[$template][$key];
    }

    public function hasAttribute($template, $key)
    {
        return $this->exists($this->attributes, $template, $key);
    }

    /*
    |--------------------------------------------------------------------------
    | Twig_Node buffer attributes
    |--------------------------------------------------------------------------
    |
    | Setters and getters for Twig_Node buffer attributes
    |
    */

    public function getBuffer($template)
    {
        if (!$this->exists($this->buffer, $template)) {
            return null;
        }

        return $this->buffer[$template];
    }

    public function getBufferKey($template, $key)
    {
        if (!$this->exists($this->buffer, $template, $key)) {
            return null;
        }

        return $this->buffer[$template][$key];
    }

    public function setBufferKey($template, $key, $value)
    {
        $this->buffer[$template][$key] = $value;
    }

    public function hasBufferKey($template, $key)
    {
        return $this->exists($this->buffer, $template, $key);
    }

    /*
    |--------------------------------------------------------------------------
    | Array helpers
    |--------------------------------------------------------------------------
    |
    | Finders for array values
    |
    */

    private function exists($array, $template, $key = null) {
        if (is_null($key)) {
            return isset($array[$template]);

        }

        return isset($array[$template][$key]);
    }
}