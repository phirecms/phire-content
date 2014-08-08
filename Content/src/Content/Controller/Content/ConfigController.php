<?php
/**
 * @namespace
 */
namespace Content\Controller\Content;

use Pop\Form\Element;
use Pop\Http\Response;
use Pop\Http\Request;
use Pop\Project\Project;
use Content\Form;
use Content\Model;
use Content\Table;

class ConfigController extends \Phire\Controller\AbstractController
{

    /**
     * Constructor method to instantiate the content controller object
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
            $viewPath = __DIR__ . '/../../../../view/content';

            if (isset($cfg['view'])) {
                $class = get_class($this);
                if (is_array($cfg['view']) && isset($cfg['view'][$class])) {
                    $viewPath = $cfg['view'][$class];
                } else if (is_array($cfg['view']) && isset($cfg['view']['*'])) {
                    $viewPath = $cfg['view']['*'] . '/content';
                } else if (is_string($cfg['view'])) {
                    $viewPath = $cfg['view'] . '/content';
                }
            }
        }

        parent::__construct($request, $response, $project, $viewPath);
    }

    /**
     * Content index method
     *
     * @return void
     */
    public function index()
    {
        $this->prepareView('config.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $content = new Model\Content();

        if ($this->request->isPost()) {
            $content->updateConfig($this->request->getPost());
            Response::redirect($this->request->getBasePath() . '?saved=' . time());
        } else {
            // Set feed limit form element
            $feedLimit = new Element('text', 'feed_limit', $content->config('feed_limit'));
            $feedLimit->setAttributes('size', 10)
                      ->setAttributes('style', 'padding: 3px;');

            $this->view->set('title', $this->view->i18n->__('Content'))
                       ->set('feed_type', new Element\Select('feed_type', array('9' => 'RSS', '10' => 'Atom'), $content->config('feed_type'), '                    '))
                       ->set('feed_limit', $feedLimit)
                       ->set('open_authoring', new Element\Radio('open_authoring', array('1' => $this->view->i18n->__('Yes'), '0' => $this->view->i18n->__('No')), $content->config('open_authoring'), '                    '))
                       ->set('incontent_editing', new Element\Radio('incontent_editing', array('1' => $this->view->i18n->__('Yes'), '0' => $this->view->i18n->__('No')), $content->config('incontent_editing'), '                    '));

            $this->send();
        }
    }

    /**
     * Content migrate method
     *
     * @return void
     */
    public function migrate()
    {
        $this->prepareView(null, array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $this->view->setTemplate(__DIR__ . '/../../../../view/phire/config/sites.phtml');

        $this->view->set('title', $this->view->i18n->__('Configuration') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Sites') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Migrate'));

        $form = new Form\Migrate(
            $this->request->getBasePath() . $this->request->getRequestUri(), 'post'
        );

        if ($this->request->isPost()) {
            $form->setFieldValues(
                $this->request->getPost(),
                array('htmlentities'),
                array(array(ENT_QUOTES, 'UTF-8'))
            );

            if ($form->isValid()) {
                $content = new Model\Content();
                $content->migrate($form);
                Response::redirect(BASE_PATH . APP_URI . '/config/sites?saved=' . time());
            } else {
                $this->view->set('form', $form);
                $this->send();
            }
        } else {
            $this->view->set('form', $form);
            $this->send();
        }
    }

    /**
     * Error method
     *
     * @return void
     */
    public function error()
    {
        $this->prepareView('error.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $this->view->set('title', $this->view->i18n->__('404 Error') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Page Not Found'));
        $this->send(404);
    }

}

