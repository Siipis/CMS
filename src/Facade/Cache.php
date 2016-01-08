<?php
namespace CMS\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CMS
 */
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cms.cache';
    }
}
