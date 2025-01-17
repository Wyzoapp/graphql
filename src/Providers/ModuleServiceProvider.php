<?php

namespace Wyzo\GraphQLAPI\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * The models of the GraphQL API module.
     *
     * @var array
     */
    protected $models = [
        \Wyzo\GraphQLAPI\Models\PushNotification::class,
        \Wyzo\GraphQLAPI\Models\PushNotificationTranslation::class,
    ];
}
