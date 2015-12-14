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
        $types     = Table\ContentTypes::findAll(['order' => 'order ASC']);

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

    /**
     * Init data values
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function initDateValues(AbstractController $controller, Application $application)
    {
        if (($controller instanceof \Phire\Content\Controller\IndexController) && ($controller->hasView())) {
            $publish = $controller->view()->publish;
            $expire  = $controller->view()->expire;
            $pubDate = substr($publish, 0, strpos($publish, ' '));
            $pubTime = substr($publish, (strpos($publish, ' ') + 1));
            $expDate = substr($publish, 0, strpos($expire, ' '));
            $expTime = substr($publish, (strpos($expire, ' ') + 1));

            $pubMonth  = date($application->module('phire-content')['month_format'], strtotime($pubDate));
            $pubDay    = date($application->module('phire-content')['day_format'], strtotime($pubDate));
            $pubYear   = date($application->module('phire-content')['year_format'], strtotime($pubDate));
            $pubHour   = date($application->module('phire-content')['hour_format'], strtotime($pubTime));
            $pubMin    = date($application->module('phire-content')['minute_format'], strtotime($pubTime));
            $pubPeriod = date($application->module('phire-content')['period_format'], strtotime($pubTime));

            $expMonth  = date($application->module('phire-content')['month_format'], strtotime($expDate));
            $expDay    = date($application->module('phire-content')['day_format'], strtotime($expDate));
            $expYear   = date($application->module('phire-content')['year_format'], strtotime($expDate));
            $expHour   = date($application->module('phire-content')['hour_format'], strtotime($expTime));
            $expMin    = date($application->module('phire-content')['minute_format'], strtotime($expTime));
            $expPeriod = date($application->module('phire-content')['period_format'], strtotime($expTime));

            $pubDate   = date($application->module('phire-content')['date_format'], strtotime($pubDate));
            $pubTime   = date($application->module('phire-content')['time_format'], strtotime($pubTime));
            $expDate   = date($application->module('phire-content')['date_format'], strtotime($expDate));
            $expTime   = date($application->module('phire-content')['time_format'], strtotime($expTime));

            $controller->view()->set('publish_date', $pubDate);
            $controller->view()->set('publish_time', $pubTime);
            $controller->view()->set('publish_month', $pubMonth);
            $controller->view()->set('publish_day', $pubDay);
            $controller->view()->set('publish_year', $pubYear);
            $controller->view()->set('publish_hour', $pubHour);
            $controller->view()->set('publish_minute', $pubMin);
            $controller->view()->set('publish_period', $pubPeriod);

            $controller->view()->set('expire_date', $expDate);
            $controller->view()->set('expire_time', $expTime);
            $controller->view()->set('expire_month', $expMonth);
            $controller->view()->set('expire_day', $expDay);
            $controller->view()->set('expire_year', $expYear);
            $controller->view()->set('expire_hour', $expHour);
            $controller->view()->set('expire_minute', $expMin);
            $controller->view()->set('expire_period', $expPeriod);
        }
    }

    /**
     * Initialize page editor
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function initPageEditor(AbstractController $controller, Application $application)
    {
        if (($controller instanceof \Phire\Content\Controller\IndexController) &&
            ($controller->hasView()) && ($controller->response()->getCode() == 200)) {
            $sess = $application->services()->get('session');
            $acl  = $application->services()->get('acl');

            if (isset($sess->user) && ($acl->isAllowed($sess->user->role, 'content', 'in-edit')) &&
                ($acl->isAllowed($sess->user->role, 'content', 'edit'))) {
                $body    = $controller->response()->getBody();
                $head    = substr($body, strpos($body, '<head>'));
                $head    = substr($head, 0, (strpos($head, '</head>') + 7));
                $newHead = $head;
                $assets  = '';

                if ((strpos($newHead, 'jax.4.0.0.min.js') === false) && (strpos($newHead, 'jax.4.0.0.js') === false)) {
                    if (strpos($newHead, '<script') !== false) {
                        $newHead1  = substr($newHead, 0, strpos($newHead, '<script'));
                        $newHead2  = substr($newHead, strpos($newHead, '<script'));
                        $newHead1 .= '<script type="text/javascript" src="' . BASE_PATH . CONTENT_PATH . '/assets/phire/js/jax.4.0.0.min.js"></script>' . PHP_EOL;
                        $newHead1 .= '    <script type="text/javascript">' . PHP_EOL;
                        $newHead1 .= '        jax.noConflict();' . PHP_EOL;
                        $newHead1 .= '    </script>' . PHP_EOL . '    ';
                        $newHead   = $newHead1 . $newHead2;
                    } else {
                        $assets .= '    <script type="text/javascript" src="' . BASE_PATH . CONTENT_PATH . '/assets/phire/js/jax.4.0.0.min.js"></script>' . PHP_EOL;
                        $assets .= '    <script type="text/javascript">' . PHP_EOL;
                        $assets .= '        jax.noConflict();' . PHP_EOL;
                        $assets .= '    </script>' . PHP_EOL;
                    }
                }

                $assets .= '    <script type="text/javascript" src="' . BASE_PATH . CONTENT_PATH . '/assets/phire/js/phire.js"></script>' . PHP_EOL;
                $assets .= '    <script type="text/javascript" src="' . BASE_PATH . CONTENT_PATH . '/assets/phire-content/js/phire.content.edit.js"></script>' . PHP_EOL . PHP_EOL;
                $assets .= '    <link type="text/css" rel="stylesheet" href="' . BASE_PATH . CONTENT_PATH . '/assets/phire-content/css/phire.content.edit.css" />' . PHP_EOL . PHP_EOL;
                $assets .= '</head>';

                $systemUri = BASE_PATH . APP_URI;
                if ($systemUri == '') {
                    $systemUri = '/';
                }

                $contentUri = BASE_PATH . APP_URI . '/content/edit/' . $controller->view()->type_id . '/' . $controller->view()->id . '?in_edit=1';

                $nav  = PHP_EOL . '<nav id="phire-in-edit-nav">' . PHP_EOL;
                $nav .= '    <a href="' . $contentUri . '" title="Edit Page" onclick="phire.launchPageEditor(this.href); return false;">Edit</a>' . PHP_EOL;
                $nav .= '    <a href="' . $systemUri . '" title="Dashboard">Dashboard</a>' . PHP_EOL;
                $nav .= '</nav>' . PHP_EOL . PHP_EOL;
                $nav .= '</body>';

                $newHead = str_replace('</head>', $assets, $newHead);
                $body    = str_replace($head, $newHead, $body);
                $body    = str_replace('</body>', $nav, $body);
                $controller->response()->setBody($body);
            }
        }
    }

}
