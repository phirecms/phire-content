<?php
/**
 * Module Name: phire-content
 * Author: Nick Sagona
 * Description: This is the content module for Phire CMS 2
 * Version: 1.0
 */
return [
    'phire-content' => [
        'prefix'     => 'Phire\Content\\',
        'src'        => __DIR__ . '/../src',
        'routes'     => include 'routes.php',
        'resources'  => include 'resources.php',
        'forms'      => include 'forms.php',
        'nav.phire'  => [
            'content' => [
                'name' => 'Content',
                'href' => '/content',
                'acl' => [
                    'resource'   => 'content',
                    'permission' => 'index'
                ],
                'attributes' => [
                    'class' => 'content-nav-icon'
                ]
            ]
        ],
        'nav.module' => [
            'name' => 'Content Types',
            'href' => '/content/types',
            'acl'  => [
                'resource'   => 'content-types',
                'permission' => 'index'
            ]
        ],
        'models' => [
            'Phire\Content\Model\Content' => []
        ],
        'events' => [
            [
                'name'     => 'app.route.pre',
                'action'   => 'Phire\Content\Event\Content::bootstrap',
                'priority' => 1000
            ],
            [
                'name'     => 'app.send.pre',
                'action'   => 'Phire\Content\Event\Content::setDashboard',
                'priority' => 1000
            ]
        ],
        'separator'      => ' &gt; ',
        'summary_length' => 150
    ]
];
