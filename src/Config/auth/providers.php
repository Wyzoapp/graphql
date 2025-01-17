<?php

return [
    'customer' => [
        'driver' => 'eloquent',
        'model'  => Wyzo\GraphQLAPI\Models\Customer\Customer::class,
    ],

    'admin' => [
        'driver' => 'eloquent',
        'model'  => Wyzo\GraphQLAPI\Models\Admin\Admin::class,
    ],
];
