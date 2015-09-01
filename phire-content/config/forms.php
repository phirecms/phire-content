<?php

return [
    'Phire\Content\Form\Content' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'content_parent_id' => [
                'type'  => 'select',
                'label' => 'Parent',
                'value' => [
                    '----' => '----',
                ],
                'attributes' => [
                    'onchange' => 'phire.changeUri();'
                ]
            ],
            'content_template' => [
                'type'  => 'select',
                'label' => 'Template',
                'value' => [
                    '0' => '(Default)'
                ]
            ],
            'content_status' => [
                'type'  => 'select',
                'label' => 'Status',
                'value' => [
                    '-1' => 'Unpublished',
                    '0' => 'Draft',
                    '1' => 'Published'
                ],
                'marked' => -1
            ],
            'publish_date' => [
                'type'   => 'text',
                'label'  => 'Publish / Start <a href="#" id="publish-calendar" class="calendar-open-link">[+]</a>',
                'attributes' => [
                    'placeholder' => 'Date',
                    'size'        => 12,
                    'class'       => 'datetime-field'
                ]
            ],
            'publish_time' => [
                'type'   => 'text',
                'attributes' => [
                    'placeholder' => 'Time',
                    'size'        => 12,
                    'class'       => 'datetime-field'
                ]
            ],
            'expire_date' => [
                'type'   => 'text',
                'label'  => 'Expire / End <a href="#" id="expire-calendar" class="calendar-open-link">[+]</a>',
                'attributes' => [
                    'placeholder' => 'Date',
                    'size'        => 12,
                    'class'       => 'datetime-field'
                ]
            ],
            'expire_time' => [
                'type'   => 'text',
                'attributes' => [
                    'placeholder' => 'Time',
                    'size'        => 12,
                    'class'       => 'datetime-field'
                ]
            ],
            'order' => [
                'type'  => 'text',
                'label' => 'Order',
                'value' => 0,
                'attributes' => [
                    'size'  => 2,
                    'class' => 'order-field'
                ]
            ],
            'roles' => [
                'type'  => 'checkbox',
                'label' => 'Roles',
                'value' => []
            ],
            'force_ssl' => [
                'type'  => 'radio',
                'label' => 'Force SSL',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => 0
            ],
            'type_id' => [
                'type'  => 'hidden',
                'value' => 0
            ],
            'id' => [
                'type'  => 'hidden',
                'value' => 0
            ]
        ],
        [
            'title' => [
                'type'       => 'text',
                'label'      => 'Title',
                'required'   => true,
                'attributes' => [
                    'size'   => 60,
                    'style'  => 'width: 99.5%'
                ]
            ],
            'slug' => [
                'type'       => 'text',
                'label'      => 'URI',
                'attributes' => [
                    'size'     => 60,
                    'style'    => 'width: 99.5%'
                ]
            ],
            'uri' => [
                'type'  => 'hidden',
                'label' => '<span id="uri-span"></span>',
                'value' => ''
            ]
        ]
    ],
    'Phire\Content\Form\ContentType' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'strict_publishing' => [
                'type'       => 'radio',
                'label'      => 'Strict Publishing',
                'value'      => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => 1
            ],
            'open_authoring' => [
                'type'       => 'radio',
                'label'      => 'Open Authoring',
                'value'      => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => 1
            ],
            'force_ssl' => [
                'type'  => 'radio',
                'label' => 'Force SSL',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => 0
            ],
            'order' => [
                'type'       => 'text',
                'label'      => 'Order',
                'attributes' => [
                    'size'  => 3,
                    'class' => 'order-field'
                ],
                'value'      => 0
            ],
            'id' => [
                'type'  => 'hidden',
                'value' => 0
            ]
        ],
        [
            'name' => [
                'type'       => 'text',
                'label'      => 'Name',
                'required'   => true,
                'attributes' => [
                    'size'  => 60,
                    'style' => 'width: 99.5%'
                ]
            ]
        ],
        [
            'content_type' => [
                'type'       => 'select',
                'label'      => 'Content Type',
                'value'      => [
                    'text/html'           => 'text/html',
                    'text/plain'          => 'text/plain',
                    'text/css'            => 'text/css',
                    'text/javascript'     => 'text/javascript',
                    'text/xml'            => 'text/xml',
                    'application/xml'     => 'application/xml',
                    'application/rss+xml' => 'application/rss+xml',
                    'application/json'    => 'application/json',
                    'other'               => 'other'
                ]
            ],
            'content_type_other' => [
                'type'       => 'text',
                'attributes' => [
                    'size'        => 25,
                    'placeholder' => 'Other'
                ]
            ]
        ]
    ]
];
