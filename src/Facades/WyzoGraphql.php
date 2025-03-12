<?php

namespace Wyzo\GraphQLAPI\Facades;

use Illuminate\Support\Facades\Facade;

class WyzoGraphql extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'wyzo_graphql';
    }
}
