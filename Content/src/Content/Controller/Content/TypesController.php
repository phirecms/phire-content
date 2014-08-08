<?php
/**
 * @namespace
 */
namespace Content\Controller\Content;

use Pop\Http\Response;
use Pop\Http\Request;
use Pop\Project\Project;
use Content\Form;
use Content\Model;
use Content\Table;

class TypesController extends \Phire\Controller\AbstractController
{

    /**
     * Constructor method to instantiate the content types controller object
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
     * Content types index method
     *
     * @return void
     */
    public function index()
    {
        $this->prepareView('types.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $this->view->set('title', $this->view->i18n->__('Content Types'));

        $type = new Model\ContentType(array('acl' => $this->project->getService('acl')));
        $type->getAll($this->request->getQuery('sort'), $this->request->getQuery('page'));
        $this->view->set('table', $type->table);
        $this->send();
    }

    /**
     * Content types add method
     *
     * @return void
     */
    public function add()
    {
        $this->prepareView('types.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $this->view->set('title', $this->view->i18n->__('Content Types') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Add'));

        $form = new Form\ContentType(
            $this->request->getBasePath() . $this->request->getRequestUri() . (isset($_GET['redirect']) ? '?redirect=1' : null),
            'post', 0
        );

        if ($this->request->isPost()) {
            $form->setFieldValues(
                $this->request->getPost(),
                array('htmlentities' => array(ENT_QUOTES, 'UTF-8'))
            );

            if ($form->isValid()) {
                $type = new Model\ContentType();
                $type->save($form);
                $this->view->set('id', $type->id);

                $url = ($form->redirect) ? BASE_PATH . APP_URI . '/content/add/' . $type->id : $this->request->getBasePath();
                if (null !== $this->request->getPost('update_value') && ($this->request->getPost('update_value') == '1')) {
                    Response::redirect($this->request->getBasePath() . '/edit/' . $type->id . '?saved=' . time());
                } else if (null !== $this->request->getQuery('update')) {
                    $this->sendJson(array(
                        'redirect' => $this->request->getBasePath() . '/edit/' . $type->id . '?saved=' . time(),
                        'updated'  => '',
                        'form'     => 'content-type-form'
                    ));
                } else {
                    Response::redirect($url . '?saved=' . time());
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
     * Content types edit method
     *
     * @return void
     */
    public function edit()
    {
        if (null === $this->request->getPath(1)) {
            Response::redirect($this->request->getBasePath());
        } else {
            $this->prepareView('types.phtml', array(
                'assets'   => $this->project->getAssets(),
                'acl'      => $this->project->getService('acl'),
                'phireNav' => $this->project->getService('phireNav')
            ));

            $type = new Model\ContentType();
            $type->getById($this->request->getPath(1));

            // If field is found and valid
            if (isset($type->id)) {
                $this->view->set('title', $this->view->i18n->__('Content Types') . ' ' . $this->view->separator . ' ' . $type->name)
                           ->set('data_title', $this->view->i18n->__('Content Types') . ' ' . $this->view->separator . ' ');
                $form = new Form\ContentType(
                    $this->request->getBasePath() . $this->request->getRequestUri(),
                    'post', $type->id
                );

                // If form is submitted
                if ($this->request->isPost()) {
                    $form->setFieldValues(
                        $this->request->getPost(),
                        array('htmlentities' => array(ENT_QUOTES, 'UTF-8'))
                    );

                    // If form is valid, save field
                    if ($form->isValid()) {
                        $type->update($form);
                        $this->view->set('id', $type->id);

                        if (null !== $this->request->getPost('update_value') && ($this->request->getPost('update_value') == '1')) {
                            Response::redirect($this->request->getBasePath() . '/edit/' . $type->id . '?saved=' . time());
                        } else if (null !== $this->request->getQuery('update')) {
                            $this->sendJson(array(
                                'updated' => '',
                                'form'     => 'content-type-form'
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
                        $type->getData(null, false)
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
     * Content types remove method
     *
     * @return void
     */
    public function remove()
    {
        if ($this->request->isPost()) {
            $type = new Model\ContentType();
            $type->remove($this->request->getPost());
        }

        Response::redirect($this->request->getBasePath() . '?removed=' . time());
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

