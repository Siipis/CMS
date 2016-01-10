<?php
namespace CMS\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CMS\Template\Scaffolding
 */
class Scaffolding extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cms.helper';
    }
}
