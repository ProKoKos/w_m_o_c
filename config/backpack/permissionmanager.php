<?php

return [
    'models' => [
        'permission' => App\Models\Permission::class,
        'role'       => App\Models\Role::class,
        'user'       => App\Models\User::class,
    ],
    'route_prefix' => config('backpack.base.route_prefix'),
    'middleware'   => ['web', 'admin'],
];
