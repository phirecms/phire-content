<?php

namespace Content\Event;

use Content\Table;
use Pop\Application;

class Content
{

    /**
     * Add content types to models of the module config for the application
     *
     * @param  Application $application
     * @return void
     */
    public static function bootstrap(Application $application)
    {
        $resources = $application->config()['resources'];
        $params    = $application->services()->getParams('nav.phire');
        $config    = $application->module('Content');
        $models    = (isset($config['models'])) ? $config['models'] : null;
        $types     = \Content\Table\ContentTypes::findAll(null, ['order' => 'order ASC']);

        foreach ($types->rows() as $type) {
            if (null !== $models) {
                if (!isset($models['Content\Model\Content'])) {
                    $models['Content\Model\Content'] = [];
                }

                $models['Content\Model\Content'][] = [
                    'type_field' => 'type_id',
                    'type_value' => $type->id,
                    'type_name'  => $type->name
                ];
            }

            $resources['content-type-' . $type->id . '|content-type-' . str_replace(' ', '-', strtolower($type->name))] = [
                'index', 'add', 'edit', 'remove'
            ];

            if (!isset($params['tree']['content']['children'])) {
                $params['tree']['content']['children'] = [];
            }

            $params['tree']['content']['children']['content-type-' . $type->id] = [
                    'name' => $type->name,
                    'href' => '/content/' . $type->id,
                    'acl'  => [
                        'resource'   => 'content-type-' . $type->id,
                        'permission' => 'index'
                    ]
            ];
        }

        $application->mergeConfig(['resources' => $resources]);
        $application->services()->setParams('nav.phire', $params);
        if (null !== $models) {
            $application->module('Content')->mergeConfig(['models' => $models]);
        }
    }

}
