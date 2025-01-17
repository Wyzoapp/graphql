<?php

namespace Wyzo\GraphQLAPI\Repositories;

use Wyzo\Core\Eloquent\Repository;
use Wyzo\GraphQLAPI\Contracts\PushNotificationTranslation;

class NotificationTranslationRepository extends Repository
{
    /**
     * Specify model class name.
     *
     * @return mixed
     */
    public function model()
    {
        return PushNotificationTranslation::class;
    }
}
