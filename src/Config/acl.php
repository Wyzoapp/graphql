<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings\PushNotification
    |--------------------------------------------------------------------------
    |
    | All ACLs related to settings\pushnotification will be placed here.
    |
    */
    [
        'key'   => 'settings.push_notification',
        'name'  => 'wyzo_graphql::app.admin.acl.push-notification',
        'route' => 'admin.settings.push_notification.index',
        'sort'  => 9,
    ], [
        'key'   => 'settings.push_notification.create',
        'name'  => 'wyzo_graphql::app.admin.acl.create',
        'route' => 'admin.settings.push_notification.create',
        'sort'  => 1,
    ], [
        'key'   => 'settings.push_notification.edit',
        'name'  => 'wyzo_graphql::app.admin.acl.edit',
        'route' => 'admin.settings.push_notification.edit',
        'sort'  => 2,
    ], [
        'key'   => 'settings.push_notification.delete',
        'name'  => 'wyzo_graphql::app.admin.acl.delete',
        'route' => 'admin.settings.push_notification.delete',
        'sort'  => 3,
    ], [
        'key'   => 'settings.push_notification.massdelete',
        'name'  => 'wyzo_graphql::app.admin.acl.mass-delete',
        'route' => 'admin.settings.push_notification.mass_delete',
        'sort'  => 4,
    ], [
        'key'   => 'settings.push_notification.massupdate',
        'name'  => 'wyzo_graphql::app.admin.acl.mass-update',
        'route' => 'admin.settings.push_notification.mass_update',
        'sort'  => 5,
    ], [
        'key'   => 'settings.push_notification.send',
        'name'  => 'wyzo_graphql::app.admin.acl.send',
        'route' => 'admin.settings.push_notification.send_notification',
        'sort'  => 6,
    ],
];
