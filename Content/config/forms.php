<?php

return [
    'Content\Form\Content' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'status' => [
                'type'  => 'select',
                'label' => 'Status',
                'value' => [
                    '-1' => 'Unpublished',
                    '0' => 'Draft',
                    '1' => 'Published'
                ],
                'marked' => 0
            ],
            'publish_month' => [
                'type'   => 'select',
                'label'  => 'Publish / Start',
                'value'  => \Pop\Form\Element\Select::MONTHS_SHORT,
                'marked' => date('m')
            ],
            'publish_day' => [
                'type'   => 'select',
                'value'  => \Pop\Form\Element\Select::DAYS_OF_MONTH,
                'marked' => date('d')
            ],
            'publish_year' => [
                'type'   => 'select',
                'value'  => 'YEAR_' . (date('Y') - 10) . '_' . (date('Y') + 10),
                'marked' => date('Y')
            ],
            'publish_hour' => [
                'type'   => 'select',
                'value'  => \Pop\Form\Element\Select::HOURS_24,
                'marked' => date('H')
            ],
            'publish_minute' => [
                'type'   => 'select',
                'value'  => \Pop\Form\Element\Select::MINUTES,
                'marked' => date('i')
            ],
            'expire_month' => [
                'type'   => 'select',
                'label'  => 'Expire / End',
                'value'  => \Pop\Form\Element\Select::MONTHS_SHORT,
                'marked' => date('m')
            ],
            'expire_day' => [
                'type'   => 'select',
                'value'  => \Pop\Form\Element\Select::DAYS_OF_MONTH,
                'marked' => date('d')
            ],
            'expire_year' => [
                'type'   => 'select',
                'value'  => 'YEAR_' . (date('Y') - 10) . '_' . (date('Y') + 10),
                'marked' => date('Y')
            ],
            'expire_hour' => [
                'type'   => 'select',
                'value'  => \Pop\Form\Element\Select::HOURS_24,
                'marked' => date('H')
            ],
            'expire_minute' => [
                'type'   => 'select',
                'value'  => \Pop\Form\Element\Select::MINUTES,
                'marked' => date('i')
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
                'attributes' => [
                    'size'   => 60,
                    'style'  => 'width: 99.5%',
                    'onkeyup' => "phire.createSlug(this.value, '#uri');"
                ]
            ],
            'uri' => [
                'type'       => 'text',
                'label'      => 'URI',
                'required'   => true,
                'attributes' => [
                    'size'  => 60,
                    'style' => 'width: 99.5%'
                ]
            ]
        ]
    ],
    'Content\Form\ContentType' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'order' => [
                'type'       => 'text',
                'label'      => 'Order',
                'attributes' => ['size' => 3],
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
            ],
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
