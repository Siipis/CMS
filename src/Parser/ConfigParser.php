<?php
namespace Siipis\CMS\Parser;

use CMS_Helper as Helper;
use YAML;

class ConfigParser extends SubParser
{
    public function parse($source)
    {
        $parts = $this->parent->split($source);

        if (count($parts) > 1) {
            $config = YAML::parse($parts[0]);

            // Caches the template config
            Helper::setConfig($this->parent->getName(), $config);

            $this
                ->addAttributes()
                ->addLayout()
                ->addTitle()
            ;

        }

        return $source;
    }

    /*
    |--------------------------------------------------------------------------
    | Parser helpers
    |--------------------------------------------------------------------------
    |
    | Handle config tags when necessary
    |
    */

    /**
     * Buffers the title
     *
     * @return $this
     */
    protected function addTitle()
    {
        if ($this->parent->configHas('title')) {
            $this->parent->buffer('title', true);

            $this->parent->setAttribute('title', $this->parent->getConfig('title'));
        }

        return $this;
    }

    /**
     * Buffers the layout
     *
     * @return $this
     */
    protected function addLayout()
    {
        if ($this->parent->configHas('layout')) {
            $name = $this->parent->getName();
            $layout = secure_string(config('cms.path.layouts') .'/'. $this->parent->getConfig('layout'));

            $this->parent->setLayout($layout);

            Helper::setBufferKey($layout, 'page', $name);
        }

        return $this;
    }

    /**
     * Buffers the attributes
     *
     * @return $this
     */
    protected function addAttributes()
    {
        if ($this->parent->configHas('with')) {
            $attributes = $this->parent->getConfig('with');
            $old = $this->parent->attributes();
            $attr = [];

            foreach ($attributes as $array)
            {
                $attr = array_merge($attr, $array);
            }

            $this->parent->setAttributes(array_merge($old, $attr));
        }

        return $this;
    }
} 