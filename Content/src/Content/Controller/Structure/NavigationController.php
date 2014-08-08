<?php
/**
 * @namespace
 */
namespace Content\Controller\Structure;

use Pop\Http\Response;
use Pop\Http\Request;
use Pop\Project\Project;
use Content\Form;
use Content\Model;
use Content\Table;

class NavigationController extends \Phire\Controller\AbstractController
{

    /**
     * Constructor method to instantiate the navigation controller object
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
            $viewPath = __DIR__ . '/../../../../view/structure';

            if (isset($cfg['view'])) {
                $class = get_class($this);
                if (is_array($cfg['view']) && isset($cfg['view'][$class])) {
                    $viewPath = $cfg['view'][$class];
                } else if (is_array($cfg['view']) && isset($cfg['view']['*'])) {
                    $viewPath = $cfg['view']['*'] . '/structure';
                } else if (is_string($cfg['view'])) {
                    $viewPath = $cfg['view'] . '/structure';
                }
            }
        }

        parent::__construct($request, $response, $project, $viewPath);
    }

    /**
     * Navigation index method
     *
     * @return void
     */
    public function index()
    {
        $this->prepareView('navigation.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));
        $navigation = new Model\Navigation(array('acl' => $this->project->getService('acl')));
        $this->view->set('navigation', $navigation->getAll($this->request->getQuery('sort'), $this->request->getQuery('page')))
                   ->set('title', $this->view->i18n->__('Structure') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Navigation'));
        $this->send();
    }

    /**
     * Navigation add method
     *
     * @return void
     */
    public function add()
    {
        $this->prepareView('navigation.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $this->view->set('title', $this->view->i18n->__('Structure') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Navigation') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Add'));

        $form = new Form\Navigation(
            $this->request->getBasePath() . $this->request->getRequestUri(), 'post', 0
        );

        if ($this->request->isPost()) {
            $form->setFieldValues(
                $this->request->getPost(),
                array('htmlentities' => array(ENT_QUOTES, 'UTF-8'))
            );

            if ($form->isValid()) {
                $navigation = new Model\Navigation();
                $navigation->save($form);
                $this->view->set('id', $navigation->id);

                if (null !== $this->request->getPost('update_value') && ($this->request->getPost('update_value') == '1')) {
                    Response::redirect($this->request->getBasePath() . '/edit/' . $navigation->id . '?saved=' . time());
                } else if (null !== $this->request->getQuery('update')) {
                    $this->sendJson(array(
                        'redirect' => $this->request->getBasePath() . '/edit/' . $navigation->id . '?saved=' . time(),
                        'updated'  => '',
                        'form'     => 'navigation-form'
                    ));
                } else {
                    Response::redirect($this->request->getBasePath() . '?saved=' . time());
                }
            } else {
                if (null !== $this->request->getQuery('update')) {
                    $this->sendJson($form->getErrors());
                } else {
                    $this->view->set('form', $form);
                    $this->send();
                }
            }
        } else {
            $this->view->set('form', $form);
            $this->send();
        }
    }

    /**
     * Navigation edit method
     *
     * @return void
     */
    public function edit()
    {
        if (null === $this->request->getPath(1)) {
            Response::redirect($this->request->getBasePath());
        } else {
            $this->prepareView('navigation.phtml', array(
                'assets'   => $this->project->getAssets(),
                'acl'      => $this->project->getService('acl'),
                'phireNav' => $this->project->getService('phireNav')
            ));

            $navigation = new Model\Navigation();
            $navigation->getById($this->request->getPath(1));

            // If field is found and valid
            if (isset($navigation->id)) {
                $this->view->set('title', $this->view->i18n->__('Structure') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Navigation') . ' ' . $this->view->separator . ' ' . $navigation->navigation)
                           ->set('data_title', $this->view->i18n->__('Structure') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Navigation') . ' ' . $this->view->separator . ' ');
                $form = new Form\Navigation(
                    $this->request->getBasePath() . $this->request->getRequestUri(), 'post', $navigation->id
                );

                // If form is submitted
                if ($this->request->isPost()) {
                    $form->setFieldValues(
                        $this->request->getPost(),
                        array('htmlentities' => array(ENT_QUOTES, 'UTF-8'))
                    );

                    // If form is valid, save field
                    if ($form->isValid()) {
                        $navigation->update($form);
                        $this->view->set('id', $navigation->id);

                        if (null !== $this->request->getPost('update_value') && ($this->request->getPost('update_value') == '1')) {
                            Response::redirect($this->request->getBasePath() . '/edit/' . $navigation->id . '?saved=' . time());
                        } else if (null !== $this->request->getQuery('update')) {
                            $this->sendJson(array(
                                'updated' => '',
                                'form'    => 'navigation-form'
                            ));
                        } else {
                            Response::redirect($this->request->getBasePath() . '?saved=' . time());
                        }
                    // Else, re-render the form with errors
                    } else {
                        if (null !== $this->request->getQuery('update')) {
                            $this->sendJson($form->getErrors());
                        } else {
                            $this->view->set('form', $form);
                            $this->send();
                        }
                    }
                // Else, render form
                } else {
                    $form->setFieldValues(
                        $navigation->getData(null, false)
                    );
                    $this->view->set('form', $form);
                    $this->send();
                }
            // Else, redirect
            } else {
                Response::redirect($this->request->getBasePath());
            }
        }
    }

    /**
     * Navigation process method
     *
     * @return void
     */
    public function process()
    {
        if ($this->request->isPost()) {
            $navigation = new Model\Navigation();
            $navigation->process($this->request->getPost(), $this->request->getPath(1));
        }

        Response::redirect($this->request->getBasePath() . '?saved=' . time());
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

