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
        '/content/copy/:tid/:id' => [
            'controller' => 'Content\Controller\ContentController',
            'action'     => 'copy',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'copy'
            ]
        ],
        '/content/trash/:tid' => [
            'controller' => 'Content\Controller\ContentController',
            'action'     => 'trash',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'trash'
            ]
        ],
        '/content/process/:tid' => [
            'controller' => 'Content\Controller\ContentController',
            'action'     => 'process',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'process'
            ]
        ],
        '/content/json/:id' => [
            'controller' => 'Content\Controller\ContentController',
            'action'     => 'json',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'json'
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
