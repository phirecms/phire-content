<?php

namespace Content\Controller;

use Content\Model;
use Content\Form;
use Content\Table;
use Phire\Controller\AbstractController;
use Pop\Paginator\Paginator;

class ContentController extends AbstractController
{

    /**
     * Index action method
     *
     * @param  int $tid
     * @return void
     */
    public function index($tid = null)
    {
        if (null === $tid) {
            $this->prepareView('content/types.phtml');
            $type = new Model\ContentType();

            if ($type->hasPages($this->config->pagination)) {
                $limit = $this->config->pagination;
                $pages = new Paginator($type->getCount(), $limit);
                $pages->useInput(true);
            } else {
                $limit = null;
                $pages = null;
            }

            $this->view->title = 'Content';
            $this->view->pages = $pages;
            $this->view->types = $type->getAll(
                $limit, $this->request->getQuery('page'), $this->request->getQuery('sort')
            );
        } else {
            $this->prepareView('content/index.phtml');
            $content = new Model\Content(['tid' => $tid]);
            $type    = new Model\ContentType();
            $type->getById($tid);

            if (!isset($type->id)) {
                $this->redirect(BASE_PATH . APP_URI . '/content');
            }

            if ($this->services['acl']->isAllowed($this->sess->user->role, 'content-type-' . $type->id, 'index')) {
                if ($content->hasPages($this->config->pagination)) {
                    $limit = $this->config->pagination;
                    $pages = new Paginator($content->getCount(), $limit);
                    $pages->useInput(true);
                } else {
                    $limit = null;
                    $pages = null;
                }

                $this->view->title   = 'Content : ' . $type->name;
                $this->view->pages   = $pages;
                $this->view->tid     = $tid;
                $this->view->content = $content->getAll(
                    $limit, $this->request->getQuery('page'), $this->request->getQuery('sort')
                );
            } else {
                $this->redirect(BASE_PATH . APP_URI . '/content');
            }
        }

        $this->send();
    }

    /**
     * Add action method
     *
     * @param  int $tid
     * @return void
     */
    public function add($tid)
    {
        $type = new Model\ContentType();
        $type->getById($tid);

        if (!isset($type->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/content');
        }

        $this->prepareView('content/add.phtml');
        $this->view->title = 'Content : ' . $type->name . ' : Add';
        $this->view->tid   = $tid;

        $fields = $this->application->config()['forms']['Content\Form\Content'];
        $fields[0]['type_id']['value'] = $tid;
        $this->view->form = new Form\Content($fields);

        if ($this->request->isPost()) {
            $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $content = new Model\Content();
                $content->save($this->view->form->getFields());
                $this->view->id = $content->id;
                $this->redirect(BASE_PATH . APP_URI . '/content/edit/' . $tid . '/'. $content->id . '?saved=' . time());
            }
        }

        $this->send();
    }

    /**
     * Edit action method
     *
     * @param  int $tid
     * @param  int $id
     * @return void
     */
    public function edit($tid, $id)
    {
        $type = new Model\ContentType();
        $type->getById($tid);

        if (!isset($type->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/content');
        }

        $content = new Model\Content();
        $content->getById($id);

        if (!isset($content->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid);
        }

        $this->prepareView('content/edit.phtml');
        $this->view->title = 'Content : ' . $content->title;
        $this->view->tid   = $tid;

        $fields = $this->application->config()['forms']['Content\Form\Content'];
        $fields[0]['type_id']['value'] = $tid;

        $this->view->form = new Form\Content($fields);
        $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
             ->setFieldValues($content->toArray());

        if ($this->request->isPost()) {
            $this->view->form->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $content = new Model\Content();
                $content->update($this->view->form->getFields());
                $this->view->id = $content->id;
                $this->redirect(BASE_PATH . APP_URI . '/content/edit/' . $tid . '/'. $content->id . '?saved=' . time());
            }
        }

        $this->send();
    }

    /**
     * Remove action method
     *
     * @param  int $tid
     * @return void
     */
    public function remove($tid)
    {
        if ($this->request->isPost()) {
            $content = new Model\Content();
            $content->remove($this->request->getPost());
        }
        $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid .  '?removed=' . time());
    }

    /**
     * Prepare view
     *
     * @param  string $template
     * @return void
     */
    protected function prepareView($template)
    {
        $this->viewPath = __DIR__ . '/../../view';
        parent::prepareView($template);
    }

}
