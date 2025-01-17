<?php

use Wyzo\GraphQLAPI\WyzoGraphql;

if (! function_exists('wyzo_graphql')) {
    function wyzo_graphql()
    {
        return app()->make(WyzoGraphql::class);
    }
}
