<?php

namespace Phire\Content\Event;

use Phire\Content\Table;
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
        $config    = $application->module('phire-content');
        $models    = (isset($config['models'])) ? $config['models'] : null;
        $types     = \Phire\Content\Table\ContentTypes::findAll(null, ['order' => 'order ASC']);

        foreach ($types->rows() as $type) {
            if (null !== $models) {
                if (!isset($models['Phire\Content\Model\Content'])) {
                    $models['Phire\Content\Model\Content'] = [];
                }

                $models['Phire\Content\Model\Content'][] = [
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
            $application->module('phire-content')->mergeConfig(['models' => $models]);
        }
    }

}
