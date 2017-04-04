<?php
/**
 * phire-content form configuration
 */
return [
    'Phire\Content\Form\Content' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'btn btn-md btn-info btn-block text-uppercase'
                ]
            ],
            'content_parent_id' => [
                'type'   => 'select',
                'label'  => 'Parent',
                'values' => [
                    '----' => '----',
                ],
                'attributes' => [
                    'onchange' => 'phire.changeUri();'
                ]
            ],
            'content_template' => [
                'type'   => 'select',
                'label'  => 'Template',
                'values' => [
                    '0'  => '(Default)'
                ]
            ],
            'content_status' => [
                'type'   => 'select',
                'label'  => 'Status',
                'values' => [
                    '-1' => 'Unpublished',
                    '0' => 'Draft',
                    '1' => 'Published'
                ],
                'marked' => -1
            ],
            'publish_date' => [
                'type'   => 'text',
                'label'  => '<a href="#" id="publish-calendar" class="calendar-open-link"><span>[+]</span></a> Publish / Start',
                'attributes' => [
                    'placeholder' => 'Date',
                    'size'        => 12,
                    'class'       => 'form-control form-control-inline input-sm'
                ]
            ],
            'publish_time' => [
                'type'   => 'text',
                'attributes' => [
                    'placeholder' => 'Time',
                    'size'        => 12,
                    'class'       => 'form-control form-control-inline input-sm'
                ]
            ],
            'expire_date' => [
                'type'   => 'text',
                'label'  => '<a href="#" id="expire-calendar" class="calendar-open-link"><span>[+]</span></a> Expire / End',
                'attributes' => [
                    'placeholder' => 'Date',
                    'size'        => 12,
                    'class'       => 'form-control form-control-inline input-sm'
                ]
            ],
            'expire_time' => [
                'type'   => 'text',
                'attributes' => [
                    'placeholder' => 'Time',
                    'size'        => 12,
                    'class'       => 'form-control form-control-inline input-sm'
                ]
            ],
            'order' => [
                'type'  => 'text',
                'label' => 'Order',
                'value' => 0,
                'attributes' => [
                    'size'  => 2,
                    'class' => 'form-control form-control-inline input-sm order-field'
                ]
            ],
            'roles' => [
                'type'   => 'checkbox',
                'label'  => 'Roles',
                'values' => []
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
                    'class'  => 'form-control'
                ]
            ],
            'slug' => [
                'type'       => 'text',
                'label'      => 'URI',
                'attributes' => [
                    'size'  => 60,
                    'class' => 'form-control'
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
                    'class'  => 'btn btn-md btn-info btn-block text-uppercase'
                ]
            ],
            'strict_publishing' => [
                'type'       => 'radio',
                'label'      => 'Strict Publishing',
                'values'     => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'checked' => 1
            ],
            'open_authoring' => [
                'type'       => 'radio',
                'label'      => 'Open Authoring',
                'values'     => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'checked' => 1
            ],
            'in_date' => [
                'type'   => 'radio',
                'label'  => 'Include in Date',
                'values' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'checked' => 0
            ],
            'order' => [
                'type'       => 'text',
                'label'      => 'Order',
                'attributes' => [
                    'size'  => 3,
                    'class' => 'form-control form-control-inline order-field'
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
                    'class' => 'form-control'
                ]
            ]
        ],
        [
            'content_type' => [
                'type'       => 'select',
                'label'      => 'Content Type',
                'values'     => [
                    'text/html'           => 'text/html',
                    'text/plain'          => 'text/plain',
                    'text/css'            => 'text/css',
                    'text/javascript'     => 'text/javascript',
                    'text/xml'            => 'text/xml',
                    'application/xml'     => 'application/xml',
                    'application/rss+xml' => 'application/rss+xml',
                    'application/json'    => 'application/json',
                    'other'               => 'other'
                ],
                'attributes' => [
                    'class' => 'form-control form-control-inline input-sm form-control-sm',
                    'style' => 'height: 28px;'
                ]
            ],
            'content_type_other' => [
                'type'       => 'text',
                'attributes' => [
                    'size'        => 25,
                    'placeholder' => 'Other',
                    'class' => 'form-control form-control-inline input-sm form-control-sm'
                ]
            ]
        ]
    ]
];
