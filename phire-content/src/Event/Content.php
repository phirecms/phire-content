<?php

namespace Phire\Content\Event;

use Phire\Content\Table;
use Pop\Application;
use Phire\Controller\AbstractController;

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
        $types     = Table\ContentTypes::findAll(null, ['order' => 'order ASC']);

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

        $content = Table\Content::findBy(['roles!=' => 'a:0:{}']);
        if ($content->hasRows()) {
            foreach ($content->rows() as $c) {
                $application->services()->get('acl')->addResource(new \Pop\Acl\Resource\Resource('content-' . $c->id));
            }
        }
    }

    /**
     * Set the dashboard
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function setDashboard(AbstractController $controller, Application $application)
    {
        if (($controller instanceof \Phire\Controller\IndexController) && ($controller->hasView())) {
            if (substr($controller->view()->getTemplate()->getTemplate(), -17) == 'phire/index.phtml') {
                $controller->view()->getTemplate()->setTemplate(__DIR__ . '/../../view/phire/index.phtml');

                $sql = Table\Content::sql();
                $sql->select([
                    'id'                => DB_PREFIX . 'content.id',
                    'type_id'           => DB_PREFIX . 'content.type_id',
                    'title'             => DB_PREFIX . 'content.title',
                    'uri'               => DB_PREFIX . 'content.uri',
                    'status'            => DB_PREFIX . 'content.status',
                    'publish'           => DB_PREFIX . 'content.publish',
                    'expire'            => DB_PREFIX . 'content.expire',
                    'created_by'        => DB_PREFIX . 'content.created_by',
                    'content_type_id'   => DB_PREFIX . 'content_types.id',
                    'content_type_name' => DB_PREFIX . 'content_types.name',
                    'open_authoring'    => DB_PREFIX . 'content_types.open_authoring',
                ])->join(DB_PREFIX . 'content_types', [DB_PREFIX . 'content.type_id' => DB_PREFIX . 'content_types.id']);

                $sql->select()->where('status >= -1');
                $sql->select()->orderBy('id', 'DESC');
                $sql->select()->limit(10);

                $controller->view()->recent = Table\Content::query((string)$sql)->rows();
            }
        }
    }

}
