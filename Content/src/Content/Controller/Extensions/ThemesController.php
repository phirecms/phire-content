<?php
/**
 * @namespace
 */
namespace Content\Controller\Extensions;

use Pop\Http\Response;
use Pop\Http\Request;
use Pop\Project\Project;
use Content\Model;

class ThemesController extends \Phire\Controller\AbstractController
{

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
            $cfg = $project->module('Phire')->asArray();
            $viewPath = __DIR__ . '/../../../../view/extensions';

            if (isset($cfg['view'])) {
                $class = get_class($this);
                if (is_array($cfg['view']) && isset($cfg['view'][$class])) {
                    $viewPath = $cfg['view'][$class];
                } else if (is_array($cfg['view']) && isset($cfg['view']['*'])) {
                    $viewPath = $cfg['view']['*'] . '/extensions';
                } else if (is_string($cfg['view'])) {
                    $viewPath = $cfg['view'] . '/extensions';
                }
            }
        }

        parent::__construct($request, $response, $project, $viewPath);
    }

    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $this->prepareView('themes.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $ext = new Model\Extension(array('acl' => $this->project->getService('acl')));
        $ext->getThemes();

        $this->view->set('title', $this->view->i18n->__('Extensions') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Themes'));
        $this->view->merge($ext->getData());
        $this->send();
    }

    /**
     * Install method
     *
     * @return void
     */
    public function install()
    {
        $this->prepareView('themes.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $ext = new Model\Extension(array('acl' => $this->project->getService('acl')));
        $ext->getThemes();

        if ((null !== $this->request->getPath(0)) && ($this->request->getPath(0) == 'install') && (count($ext->new) > 0)) {
            $ext->installThemes();
            if (null !== $ext->error) {
                $this->view->set('title', $this->view->i18n->__('Extensions') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Themes') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Installation Error'));
                $this->view->merge($ext->getData());
                $this->send();
            } else {
                Response::redirect($this->request->getBasePath() . '?saved=' . time());
            }
        } else {
            Response::redirect($this->request->getBasePath());
        }
    }

    /**
     * Process method
     *
     * @return void
     */
    public function process()
    {
        $this->prepareView('themes.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $ext = new Model\Extension(array('acl' => $this->project->getService('acl')));
        $ext->getThemes();

        if (($this->request->isPost()) && (null !== $this->request->getPath(0)) && ($this->request->getPath(0) == 'process')) {
            $ext->processThemes($this->request->getPost());
            Response::redirect($this->request->getBasePath() . '?saved=' . time());
        } else {
            Response::redirect($this->request->getBasePath());
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

