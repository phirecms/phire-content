<?php
/**
 * phire-content routes
 */
return [
    '' => [
        '/' => [
            'controller' => 'Phire\Content\Controller\IndexController',
            'action'     => 'index'
        ],
        '*' => [
            'controller' => 'Phire\Content\Controller\IndexController',
            'action'     => 'index'
        ]
    ],
    APP_URI => [
        '/content[/:tid]' => [
            'controller' => 'Phire\Content\Controller\ContentController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'index'
            ]
        ],
        '/content/add/:tid' => [
            'controller' => 'Phire\Content\Controller\ContentController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'add'
            ]
        ],
        '/content/edit/:tid/:id' => [
            'controller' => 'Phire\Content\Controller\ContentController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'edit'
            ]
        ],
        '/content/copy/:tid/:id' => [
            'controller' => 'Phire\Content\Controller\ContentController',
            'action'     => 'copy',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'copy'
            ]
        ],
        '/content/trash/:tid' => [
            'controller' => 'Phire\Content\Controller\ContentController',
            'action'     => 'trash',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'trash'
            ]
        ],
        '/content/process/:tid' => [
            'controller' => 'Phire\Content\Controller\ContentController',
            'action'     => 'process',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'process'
            ]
        ],
        '/content/json/:id' => [
            'controller' => 'Phire\Content\Controller\ContentController',
            'action'     => 'json',
            'acl'        => [
                'resource'   => 'content',
                'permission' => 'json'
            ]
        ],
        '/content/types/' => [
            'controller' => 'Phire\Content\Controller\TypeController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'content-types',
                'permission' => 'index'
            ]
        ],
        '/content/types/add' => [
            'controller' => 'Phire\Content\Controller\TypeController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'content-types',
                'permission' => 'add'
            ]
        ],
        '/content/types/edit/:id' => [
            'controller' => 'Phire\Content\Controller\TypeController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'content-types',
                'permission' => 'edit'
            ]
        ],
        '/content/types/remove' => [
            'controller' => 'Phire\Content\Controller\TypeController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'content-types',
                'permission' => 'remove'
            ]
        ]
    ]
];
