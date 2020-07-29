<?php
return [
    'main' => null,
    'installed' => [
    ],
    'disabled' => [],
    'slugs' => [
        'Modules' => [
            '*' => 'modules'
        ]
    ],
    'layouts' => [
        '*' => 'layouts.admin-lte'
    ],
    'routeConfig' => [
        'default' => [
            'own' => [
                'middleware' => ['web', 'auth'],
            ],
            'read' => [
                'middleware' => [
                    'web',
                    'auth',
                    'level:90'
                ],
                'prefix' => 'admin',
                'as' => 'admin.',
                'namespace' => 'Admin'
            ],
            'create' => [
                'middleware' => [
                    'web',
                    'auth',
                    'level:90'
                ],
                'prefix' => 'admin',
                'as' => 'admin.',
                'namespace' => 'Admin'
            ],
            'update' => [
                'middleware' => [
                    'web',
                    'auth',
                    'level:90'
                ],
                'prefix' => 'admin',
                'as' => 'admin.',
                'namespace' => 'Admin'
            ],
            'delete' => [
                'middleware' => [
                    'web',
                    'auth',
                    'level:90'
                ],
                'prefix' => 'admin',
                'as' => 'admin.',
                'namespace' => 'Admin'
            ],
        ]
    ]
];
