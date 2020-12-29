<?php

namespace TCG\Voyager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Voyager
 *
 * @mixin \TCG\Voyager\Voyager
 */
class Voyager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'voyager';
    }
}
