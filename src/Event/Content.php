<?php
/**
 * Phire Content Module
 *
 * @link       https://github.com/phirecms/phire-content
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Phire\Content\Event;

use Phire\Content\Model;
use Phire\Content\Table;
use Pop\Application;
use Phire\Controller\AbstractController;

/**
 * Content Event class
 *
 * @category   Phire\Content
 * @package    Phire\Content
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
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
     * Init content model object
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function init(AbstractController $controller, Application $application)
    {
        if ((!$_POST) && ($controller->hasView()) && ($controller->view()->isFile()) &&
            (($controller instanceof \Phire\Content\Controller\IndexController) ||
                ($controller instanceof \Phire\Categories\Controller\IndexController))) {
                $content = new Model\Content();
                $content->date_format    = $application->module('phire-content')['date_format'];
                $content->month_format   = $application->module('phire-content')['month_format'];
                $content->day_format     = $application->module('phire-content')['day_format'];
                $content->year_format    = $application->module('phire-content')['year_format'];
                $content->time_format    = $application->module('phire-content')['time_format'];
                $content->hour_format    = $application->module('phire-content')['hour_format'];
                $content->minute_format  = $application->module('phire-content')['minute_format'];
                $content->period_format  = $application->module('phire-content')['period_format'];
                $content->separator      = $application->module('phire-content')['separator'];
                $content->filters        = $application->module('phire-content')['filters'];
                $content->archive_count  = $application->module('phire-content')['archive_count'];
                $controller->view()->phire->content = $content;
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
            $expDate = substr($expire, 0, strpos($expire, ' '));
            $expTime = substr($expire, (strpos($expire, ' ') + 1));

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
     * Parse any content group placeholders
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function parseContent(AbstractController $controller, Application $application)
    {
        if (($controller->hasView()) &&
            (($controller instanceof \Phire\Categories\Controller\IndexController) ||
                ($controller instanceof \Phire\Content\Controller\IndexController))
        ) {
            $data = $controller->view()->getData();
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $subIds = self::parseContentIds($value);
                    if (count($subIds) > 0) {
                        $content = new Model\Content();
                        foreach ($subIds as $sid) {
                            $c    = substr($value, strpos($value, '[{content_' . $sid . '}]'));
                            $c    = substr($c, 0, (strpos($c, '[{/content_' . $sid . '}]') + strlen('[{/content_' . $sid . '}]')));
                            $view = new \Pop\View\View($c, ['content_' . $sid => $content->getAllByTypeId($sid)]);
                            $controller->view()->{$key} = str_replace($c, $view->render(), $value);
                        }
                    }
                }
            }

            $body = $controller->response()->getBody();
            $ids  = self::parseContentIds($body);
            if (count($ids) > 0) {
                $content = new Model\Content();
                foreach ($ids as $id) {
                    $key = 'content_' . $id;
                    $controller->view()->{$key} = $content->getAllByTypeId($id);
                }
            }
        }
    }

    /**
     * Parse placeholders
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function parsePlaceholders(AbstractController $controller, Application $application)
    {
        if (($controller instanceof \Phire\Content\Controller\IndexController) && ($controller->hasView())) {
            $body    = $controller->response()->getBody();
            $matches = [];

            preg_match_all('/\[\{(.*)\}\]/', $body, $matches);

            if (isset($matches[0]) && isset($matches[0][0]) && isset($matches[1]) && isset($matches[1][0])) {
                $data = $controller->view()->getData();
                foreach ($matches[1] as $match) {
                    if (isset($data[$match]) && !is_array($data[$match])) {
                        $body = str_replace('[{' . $match . '}]', $data[$match], $body);
                    }
                }
            }

            $controller->response()->setBody($body);
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
                $assets = '    <link type="text/css" rel="stylesheet" href="' . BASE_PATH . CONTENT_PATH . '/assets/phire-content/css/phire.content.edit.css" />' . PHP_EOL . PHP_EOL;

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

    /**
     * Parse content IDs from template
     *
     * @param  string $template
     * @return array
     */
    protected static function parseContentIds($template)
    {
        $ids     = [];
        $content = [];

        preg_match_all('/\[\{content_.*\}\]/', $template, $content);

        if (isset($content[0]) && isset($content[0][0])) {
            foreach ($content[0] as $cont) {
                $ids[] = str_replace('}]', '', substr($cont, (strpos($cont, '_') + 1)));
            }
        }

        return $ids;
    }

}
