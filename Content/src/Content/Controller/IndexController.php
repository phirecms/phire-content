<?php
/**
 * @namespace
 */
namespace Content\Controller;

use Pop\Filter\String;
use Pop\Http\Response;
use Pop\Http\Request;
use Pop\Project\Project;
use Pop\Web\Mobile;
use Pop\Web\Session;
use Content\Form;
use Content\Model;
use Content\Table;

class IndexController extends \Phire\Controller\AbstractController
{

    /**
     * Session object
     * @var \Pop\Web\Session
     */
    protected $sess = null;

    /**
     * Device
     * @var string
     */
    protected $device = 'desktop';

    /**
     * Mobile flag
     * @var boolean
     */
    protected $mobile = false;

    /**
     * Tablet flag
     * @var boolean
     */
    protected $tablet = false;

    /**
     * Error action
     * @var string
     */
    protected $errorAction = 'index';

    /**
     * Constructor method to instantiate the default controller object
     *
     * @param  Request  $request
     * @param  Response $response
     * @param  Project  $project
     * @param  string   $viewPath
     * @return self
     */
    public function __construct(Request $request = null, Response $response = null, Project $project = null, $viewPath = null)
    {
        if (null === $viewPath) {
            $cfg = $project->module('Content')->asArray();
            $viewPath = __DIR__ . '/../../../view';

            if (isset($cfg['view'])) {
                $class = get_class($this);
                if (is_array($cfg['view']) && isset($cfg['view'][$class])) {
                    $viewPath = $cfg['view'][$class];
                } else if (is_array($cfg['view']) && isset($cfg['view']['*'])) {
                    $viewPath = $cfg['view']['*'];
                } else if (is_string($cfg['view'])) {
                    $viewPath = $cfg['view'];
                }
            }
        }

        if (($_SERVER['SERVER_PORT'] != '443') && (Model\Content::factory()->config()->force_ssl)) {
            Response::redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        } else {
            parent::__construct($request, $response, $project, $viewPath);
            $this->sess = Session::getInstance();
            $this->getDevice();
        }
    }

    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        if ($this->project->getService('acl')->isAuth()) {
            $this->prepareView(null, array(
                'acl'      => $this->project->getService('acl'),
                'phireNav' => $this->project->getService('phireNav')
            ));
            $this->view->set('incontent_editing', \Phire\Table\Config::findById('incontent_editing')->value);
        } else {
            $this->prepareView();
        }

        // Set up navigations
        $nav = new Model\Navigation(array('acl' => $this->project->getService('acl')));
        $this->view->merge($nav->getContentNav());
        $this->view->set('category_nav', $nav->getCategoryNav());

        $content = new Model\Content(array('acl' => $this->project->getService('acl')));
        $content->getByUri($this->request->getRequestUri());

        // Set breadcrumb and Phire model object
        $this->view->set('breadcrumb', $content->getBreadcrumb())
                   ->set('phire', new Model\Phire());

        // If site is live
        if ($this->isLive()) {
            // If page found, but requires SSL
            if (isset($content->id) && (($_SERVER['SERVER_PORT'] != '443') && ($content->force_ssl))) {
                Response::redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            // Else, if page found and allowed
            } else if (isset($content->id) && ($content->allowed)) {
                $template = $this->getTemplate($content->template, 'index');
                if (strpos($template, '[{categor') !== false) {
                    if ((strpos($template, 'category_[{slug}]}]') !== false) && isset($content->slug)) {
                        $template = str_replace('category_[{slug}]}]', 'category_' . $content->slug . '}]', $template);
                    }
                    $this->view->merge(Model\Template::parseCategories($template));
                }
                if (strpos($template, '[{recent') !== false) {
                    $this->view->merge(Model\Template::parseRecent($template));
                }
                $this->view->set('breadcrumb_title', strip_tags($content->getBreadcrumb()));
                $this->view->merge($content->getData());
                $this->view->setTemplate($template);
                $this->send();
            // Else, check for date-based URI
            } else {
                $uri = $this->request->getRequestUri();
                if (substr($uri, 0, 1) == '/') {
                    $uri = substr($uri, 1);
                }
                $date = $this->isDate($uri);
                if (null !== $date) {
                    $content->getByDate($date, $this->request->getQuery('page'));
                    if (isset($content->id) && ($content->allowed)) {
                        $template = $this->getTemplate($content->template, 'index');

                        if (strpos($template, '[{categor') !== false) {
                            if ((strpos($template, 'category_[{slug}]}]') !== false) && isset($content->slug)) {
                                $template = str_replace('category_[{slug}]}]', 'category_' . $content->slug . '}]', $template);
                            }
                            $this->view->merge(Model\Template::parseCategories($template));
                        }

                        $this->view->setTemplate($template);
                        $this->view->set('breadcrumb', $content->getBreadcrumb())
                                   ->set('breadcrumb_title', strip_tags($content->getBreadcrumb()));
                        $this->view->merge($content->getData());
                        $this->send();
                    } else if (isset($content->items[0])) {
                        $content->set('title', $date['match']);

                        $tmpl = Table\Templates::findBy(array('name' => 'Date'));
                        $template = (isset($tmpl->id)) ? $this->getTemplate($tmpl->id, 'date') : $this->getTemplate('date.phtml', 'date');

                        if (strpos($template, '[{categor') !== false) {
                            if ((strpos($template, 'category_[{slug}]}]') !== false) && isset($content->slug)) {
                                $template = str_replace('category_[{slug}]}]', 'category_' . $content->slug . '}]', $template);
                            }
                            $this->view->merge(Model\Template::parseCategories($template));
                        }

                        $this->view->setTemplate($template);

                        $dateData = $content->getData();

                        if (isset($tmpl->id)) {
                            foreach ($dateData['items'] as $key => $item) {
                                $dateData['items'][$key]['publish'] = date($this->view->datetime_format, strtotime($item['publish']));
                                foreach ($item as $k => $v) {
                                    $matches = array();
                                    preg_match_all('/\[\{' . $k . '_\d+\}\]/', $template, $matches);
                                    if (isset($matches[0]) && isset($matches[0][0])) {
                                        $count = substr($matches[0][0], (strpos($matches[0][0], '_') + 1));
                                        $count = substr($count, 0, strpos($count, '}]'));
                                        $dateData['items'][$key][$k . '_' . $count] = substr(strip_tags($item[$k]), 0, $count);
                                    }
                                }
                            }
                        }

                        $this->view->merge($dateData);
                        $this->send();
                    } else {
                        $this->error();
                    }
                // Else, error page
                } else {
                    $this->error();
                }
            }
        } else {
            $this->error();
        }
    }

    /**
     * Category method
     *
     * @return void
     */
    public function category()
    {
        if ($this->project->getService('acl')->isAuth()) {
            $this->prepareView(null, array(
                'acl'      => $this->project->getService('acl'),
                'phireNav' => $this->project->getService('phireNav')
            ));
            $this->view->set('incontent_editing', \Phire\Table\Config::findById('incontent_editing')->value);
        } else {
            $this->prepareView();
        }

        // Set up navigation
        $nav = new Model\Navigation(array('acl' => $this->project->getService('acl')));
        $this->view->merge($nav->getContentNav());
        $this->view->set('category_nav', $nav->getCategoryNav());

        $category = new Model\Category();
        $category->getByUri(substr($this->request->getRequestUri(), 9), $this->request->getQuery('page'));

        // Set up breadcrumb
        $this->view->set('breadcrumb', $category->getBreadcrumb())
                   ->set('breadcrumb_title', strip_tags($category->getBreadcrumb()));

        // If site is live
        if ($this->isLive()) {
            if (isset($category->id)) {
                $tmplFile = 'category-' . String::slug($category->category_title) . '.phtml';
                $tmplName = 'Category ' . $category->category_title;
                $tmpl = Table\Templates::findBy(array('name' => $tmplName));
                if (!isset($tmpl->id)) {
                    $tmpl = Table\Templates::findBy(array('name' => 'Category'));
                }
                $template = (isset($tmpl->id)) ? $this->getTemplate($tmpl->id, 'category') : $this->getTemplate($tmplFile, 'category');
                if (strpos($template, '[{categor') !== false) {
                    if ((strpos($template, 'category_[{slug}]}]') !== false) && isset($category->slug)) {
                        $template = str_replace('category_[{slug}]}]', 'category_' . $category->slug . '}]', $template);
                    }
                    $this->view->merge(Model\Template::parseCategories($template));
                }
                $categoryData = $category->getData();
                $tmpl = Table\Templates::findBy(array('name' => 'Category'));

                if (isset($tmpl->id)) {
                    foreach ($categoryData['items'] as $key => $item) {
                        $categoryData['items'][$key]['publish'] = date($this->view->datetime_format, strtotime($item['publish']));
                        foreach ($item as $k => $v) {
                            $matches = array();
                            preg_match_all('/\[\{' . $k . '_\d+\}\]/', $template, $matches);
                            if (isset($matches[0]) && isset($matches[0][0])) {
                                $count = substr($matches[0][0], (strpos($matches[0][0], '_') + 1));
                                $count = substr($count, 0, strpos($count, '}]'));
                                $categoryData['items'][$key][$k . '_' . $count] = substr(strip_tags($item[$k]), 0, $count);
                            }
                        }
                    }
                }

                $this->view->setTemplate($template);
                $this->view->merge($categoryData);
                $this->view->set('phire', new Model\Phire());
                $this->send();
            } else {
                $this->error();
            }
        } else {
            $this->error();
        }
    }

    /**
     * Search method
     *
     * @return void
     */
    public function search()
    {
        if ($this->project->getService('acl')->isAuth()) {
            $this->prepareView(null, array(
                'acl'      => $this->project->getService('acl'),
                'phireNav' => $this->project->getService('phireNav')
            ));
            $this->view->set('incontent_editing', \Phire\Table\Config::findById('incontent_editing')->value);
        } else {
            $this->prepareView();
        }

        // If site is live
        if ($this->isLive()) {
            // Set up navigation
            $nav = new Model\Navigation(array('acl' => $this->project->getService('acl')));
            $this->view->merge($nav->getContentNav());
            $this->view->set('category_nav', $nav->getCategoryNav())
                       ->set('title', 'Search');

            $content = new Model\Content();
            $content->search($this->request, $this->request->getQuery('page'));
            $this->view->set('phire', new Model\Phire());

            if (count($content->keys) == 0) {
                $this->view->set('error', $this->view->i18n->__('No search keywords were passed. Please try again.'));
            }

            $contentData = $content->getData();

            $tmpl = Table\Templates::findBy(array('name' => 'Search'));
            if (isset($tmpl->id)) {
                $template = $this->getTemplate($tmpl->id, 'search');

                foreach ($contentData['items'] as $key => $item) {
                    $contentData['items'][$key]['publish'] = date($this->view->datetime_format, strtotime($item['publish']));
                    foreach ($item as $k => $v) {
                        $matches = array();
                        preg_match_all('/\[\{' . $k . '_\d+\}\]/', $template, $matches);
                        if (isset($matches[0]) && isset($matches[0][0])) {
                            $count = substr($matches[0][0], (strpos($matches[0][0], '_') + 1));
                            $count = substr($count, 0, strpos($count, '}]'));
                            $contentData['items'][$key][$k . '_' . $count] = substr(strip_tags($item[$k]), 0, $count);
                        }
                    }
                }
            } else {
                $template = $this->getTemplate('search.phtml', 'search');
            }

            if (strpos($template, '[{categor') !== false) {
                $this->view->merge(Model\Template::parseCategories($template));
            }

            $this->view->setTemplate($template);
            $this->view->merge($contentData);
            $this->send();
        } else {
            $this->error();
        }
    }

    /**
     * Feed method
     *
     * @return void
     */
    public function feed()
    {
        if ($this->project->getService('acl')->isAuth()) {
            $this->prepareView(null, array(
                'acl'      => $this->project->getService('acl'),
                'phireNav' => $this->project->getService('phireNav')
            ));
        } else {
            $this->prepareView();
        }

        // If site is live
        if ($this->isLive()) {
            $this->view->set('title', 'Feed');

            $lang = $this->view->default_language;
            if (strpos($lang, '_') !== false) {
                $lang = substr($lang, 0, strpos($lang, '_'));
            }

            $headers = array(
                'title'     => $_SERVER['HTTP_HOST'] . ' Feed',
                'subtitle'  => $_SERVER['HTTP_HOST'] . ' Feed',
                'link'      => 'http://' . $_SERVER['HTTP_HOST'] . '/',
                'language'  => $lang,
                'updated'   => date('Y-m-d H:i:s'),
                'generator' => 'http://' . $_SERVER['HTTP_HOST'] . '/',
                'author'    => 'Phire CMS Feed Generator'
            );


            $content = new Model\Content();
            $feed = new \Pop\Feed\Writer(
                $headers, $content->getFeed((int)$content->config('feed_limit')),
                (int)$content->config('feed_type')
            );

            echo $feed->render(true);
        } else {
            $this->error();
        }
    }

    /**
     * CAPTCHA method
     *
     * @return void
     */
    public function captcha()
    {
        $config = $this->project->module('Phire')->captcha;
        $sess = Session::getInstance();
        $expire = (null !== $config->expire) ? (int)$config->expire : 300;
        $i18n = \Phire\Table\Config::getI18n();
        $captchaImage = '<br /><img id="captcha-image" src="' . BASE_PATH . '/captcha" /><br /><a class="reload-link" href="#" onclick="document.getElementById(\'captcha-image\').src = \'' . BASE_PATH . '/captcha?reload=1\';return false;">' . $i18n->__('Reload') . '</a>';

        // If token does not exist, create one
        if ((null !== $this->request->getQuery('reload')) || !isset($sess->pop_captcha)) {
            $token = array(
                'captcha' => $captchaImage,
                'value'   => String::random($config->length, String::ALPHANUM, String::UPPER),
                'expire'  => (int)$expire,
                'start'   => time()
            );
            $sess->pop_captcha = serialize($token);
        // Else, retrieve existing token
        } else {
            $token = unserialize($sess->pop_captcha);
            if ($token['value'] == '') {
                $token = array(
                    'captcha' => $captchaImage,
                    'value'   => String::random($config->length, String::ALPHANUM, String::UPPER),
                    'expire'  => (int)$expire,
                    'start'   => time()
                );
                $sess->pop_captcha = serialize($token);
            // Check to see if the token has expired
            } else  if ($token['expire'] > 0) {
                if (($token['expire'] + $token['start']) < time()) {
                    $token = array(
                        'captcha' => $captchaImage,
                        'value'   => String::random($config->length, String::ALPHANUM, String::UPPER),
                        'expire'  => (int)$expire,
                        'start'   => time()
                    );
                    $sess->pop_captcha = serialize($token);
                }
            }
        }

        $spacing   = $config->lineSpacing;
        $lineColor = $config->lineColor->asArray();
        $textColor = $config->textColor->asArray();

        $image = new \Pop\Image\Gd('captcha.gif', $config->width, $config->height);
        $image->setStrokeColor(new \Pop\Color\Space\Rgb($lineColor[0], $lineColor[1], $lineColor[2]));

        // Draw background grid
        for ($y = $spacing; $y <= $config->height; $y += $spacing) {
            $image->drawLine(0, $y, $config->width, $y);
        }

        for ($x = $spacing; $x <= $config->width; $x += $spacing) {
            $image->drawLine($x, 0, $x, $config->height);
        }

        $image->setStrokeColor(new \Pop\Color\Space\Rgb($textColor[0], $textColor[1], $textColor[2]))
              ->border(0.5);

        // If no font, use system font
        if (null === $config->font) {
            $textX = round(($config->width - ($config->length * 10)) / 2);
            $textY = round(($config->height - 16) / 2);
            $image->text($token['value'], 5, $textX, $textY);
        // Else, use TTF font
        } else {
            $textX = round(($config->width - ($config->length * ($config->size / 1.5))) / 2);
            $textY = round($config->height - (($config->height - $config->size) / 2) + ((int)$config->rotate / 2));
            $image->text($token['value'], $config->size, $textX, $textY, $config->font, (int)$config->rotate);
        }

        $image->output();
    }

    /**
     * Error method
     *
     * @return void
     */
    public function error()
    {
        if ($this->project->getService('acl')->isAuth()) {
            $this->prepareView(null, array(
                'acl'      => $this->project->getService('acl'),
                'phireNav' => $this->project->getService('phireNav')
            ));
        } else {
            $this->prepareView();
        }

        // Set up navigations
        $nav = new Model\Navigation(array('acl' => $this->project->getService('acl')));
        $this->view->merge($nav->getContentNav());
        $this->view->set('category_nav', $nav->getCategoryNav());

        $content = new Model\Content(array('acl' => $this->project->getService('acl')));

        $this->view->set('title', $this->view->i18n->__('404 Error') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Page Not Found'))
                   ->set('breadcrumb', $content->getBreadcrumb())
                   ->set('breadcrumb_title', strip_tags($content->getBreadcrumb()))
                   ->set('phire', new Model\Phire());

        $tmpl = Table\Templates::findBy(array('name' => 'Error'));
        $template = (isset($tmpl->id)) ? $this->getTemplate($tmpl->id, 'error') : $this->getTemplate('error.phtml', 'error');
        if (strpos($template, '[{categor') !== false) {
            $this->view->merge(Model\Template::parseCategories($template));
        }

        $this->view->setTemplate($template);
        $this->view->merge($content->getData());
        $this->send(404);
    }

    /**
     * Method to determine the mobile device
     *
     * @return string
     */
    protected function getDevice()
    {
        if (null !== $this->request->getQuery('mobile')) {
            $force = $this->request->getQuery('mobile');
            if ($force == 'clear') {
                unset($this->sess->mobile);
            } else {
                $this->sess->mobile = $force;
            }
        }

        if (!isset($this->sess->mobile)) {
            $this->mobile = Mobile::isMobileDevice();
            $this->tablet = Mobile::isTabletDevice();
            $device = Mobile::getMobileDevice();

            if (null !== $device) {
                $this->device = strtolower($device);
                if (($this->device == 'android') || ($this->device == 'windows')) {
                    $this->device .= ($this->tablet) ? '-tablet' : '-phone';
                }
            }
        } else {
            $this->device = $this->sess->mobile;
        }
    }

    /**
     * Method to determine the correct template
     *
     * @param  mixed  $template
     * @param  string $default
     * @return string
     */
    protected function getTemplate($template, $default = 'index')
    {
        $isFile = true;
        $site = \Phire\Table\Sites::getSite();
        $theme = \Phire\Table\Extensions::findBy(array('type' => 0, 'active' => 1), null, 1);

        if (isset($theme->id)) {
            $this->viewPath = $site->document_root . $site->base_path . CONTENT_PATH . '/extensions/themes/' . $theme->name;
        }

        $t = $this->viewPath . '/' . $default . '.phtml';

        if (null !== $template) {
            // If the template is in the database
            if (is_numeric($template)) {
                $tmpl = Table\Templates::getTemplate($template);
                if (count($tmpl) > 0) {
                    // If a specific mobile template is set
                    if (isset($tmpl[$this->device])) {
                        $isFile = false;
                        $t =  $tmpl[$this->device]['template'];
                        $this->response->setHeader('Content-Type', $tmpl[$this->device]['content_type']);
                    // Else, attempt to fall back on a generic mobile template
                    } else if ($this->device != 'desktop') {
                        $device = null;
                        if (isset($tmpl['tablet'])) {
                            $device = 'tablet';
                        } else if (isset($tmpl['phone'])) {
                            $device = 'phone';
                        } else if (isset($tmpl['mobile'])) {
                            $device = 'mobile';
                        }
                        if (null !== $device) {
                            $isFile = false;
                            $t =  $tmpl[$device]['template'];
                            $this->response->setHeader('Content-Type', $tmpl[$device]['content_type']);
                        // If there is a template object, fall back on the desktop template
                        } else if (isset($tmpl['desktop'])) {
                            $isFile = false;
                            $t =  $tmpl['desktop']['template'];
                            $this->response->setHeader('Content-Type', $tmpl['desktop']['content_type']);
                        }
                    }

                    $t = Model\Template::parse(html_entity_decode($t, ENT_QUOTES, 'UTF-8'), $template);
                }
            // Else, if the template is a file
            } else {
                $t = (file_exists($this->viewPath . '/' . $template)) ? $this->viewPath . '/' . $template : $this->viewPath . '/' . $default . '.phtml';
                if ($this->device != 'desktop') {
                    $mobileDir = $this->viewPath . DIRECTORY_SEPARATOR . $this->device;
                    if (file_exists($mobileDir) && is_dir($mobileDir)) {
                        $t = $mobileDir . DIRECTORY_SEPARATOR . $template;
                    } else {
                        $altDevices = array('tablet', 'phone', 'mobile');
                        foreach ($altDevices as $device) {
                            if (file_exists($this->viewPath . DIRECTORY_SEPARATOR . $device . DIRECTORY_SEPARATOR . $template)) {
                                $t = $this->viewPath . DIRECTORY_SEPARATOR . $device . DIRECTORY_SEPARATOR . $template;
                            }
                        }
                    }
                }
            }
        }

        // Check is the template file has a Content-Type override
        if (($isFile) && file_exists($t)) {
            $f = file_get_contents($t);
            if (strpos($f, 'Content-Type:') != false) {
                $contentType = substr($f, (strpos($f, 'Content-Type:') + 13));
                $contentType = trim(substr($contentType, 0, strpos($contentType, "\n")));
                if (in_array($contentType, Form\Template::getContentTypes())) {
                    $this->response->setHeader('Content-Type', $contentType);
                }
            }
        }

        return $t;
    }

    /**
     * Method to determine if the URI is a date
     *
     * @param  string $subject
     * @return mixed
     */
    protected function isDate($subject)
    {
        $regexes = array(
            10 => '/^(1[0-9]{3}|2[0-9]{3})\/(0[1-9]|1[0-2])\/(0[1-9]|1[0-9]|2[0-9]|3[0-1])$/', // YYYY/MM/DD
            7  => '/^(1[0-9]{3}|2[0-9]{3})\/(0[1-9]|1[0-2])$/',                                // YYYY/MM
            4  => '/^(1[0-9]{3}|2[0-9]{3})$/'                                                  // YYYY
        );

        $result = null;

        foreach ($regexes as $length => $regex) {
            $match = substr($subject, 0, $length);
            if (preg_match($regex, $match)) {
                $result = array(
                    'match' => $match,
                    'uri'   => substr($subject, $length)
                );
                break;
            }
        }

        return $result;
    }

}

