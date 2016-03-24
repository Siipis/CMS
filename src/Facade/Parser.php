<?php
namespace Siipis\CMS\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CMS\Parser\CoreParser
 */
class Parser extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cms.parser';
    }
}
