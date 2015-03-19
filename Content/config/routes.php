<?php

return [
    '' => [
        '/' => [
            'controller' => 'Content\Controller\IndexController',
            'action'     => 'index'
        ],
        '*' => [
            'controller' => 'Content\Controller\IndexController',
            'action'     => 'index'
        ]
    ],
    APP_URI => [
        '/content[/:tid]' => [
            'controller' => 'Content\Controller\ContentController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'index'
            ]
        ],
        '/content/add/:tid' => [
            'controller' => 'Content\Controller\ContentController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'add'
            ]
        ],
        '/content/edit/:tid/:id' => [
            'controller' => 'Content\Controller\ContentController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'edit'
            ]
        ],
        '/content/remove/:tid' => [
            'controller' => 'Content\Controller\ContentController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'remove'
            ]
        ],
        '/content/types[/]' => [
            'controller' => 'Content\Controller\TypeController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'content-types',
                'permission' => 'index'
            ]
        ],
        '/content/types/add' => [
            'controller' => 'Content\Controller\TypeController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'content-types',
                'permission' => 'add'
            ]
        ],
        '/content/types/edit/:id' => [
            'controller' => 'Content\Controller\TypeController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'content-types',
                'permission' => 'edit'
            ]
        ],
        '/content/types/remove' => [
            'controller' => 'Content\Controller\TypeController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'content-types',
                'permission' => 'remove'
            ]
        ]
    ]
];
