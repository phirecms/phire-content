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

                $this->view->title          = 'Content : ' . $type->name;
                $this->view->pages          = $pages;
                $this->view->tid            = $tid;
                $this->view->open_authoring = $type->open_authoring;
                $this->view->trash          = $content->getCount(-2);
                $this->view->content        = $content->getAll(
                    $limit, $this->request->getQuery('page'), $this->request->getQuery('sort'), $this->request->getQuery('title')
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

        if (!$this->services['acl']->isAllowed($this->sess->user->role, 'content-type-' . $tid, 'add')) {
            $this->redirect(BASE_PATH . APP_URI . '/content');
        }

        $content = new Model\Content();

        $this->prepareView('content/add.phtml');
        $this->view->title = 'Content : ' . $type->name . ' : Add';
        $this->view->tid   = $tid;

        $fields = $this->application->config()['forms']['Content\Form\Content'];
        $fields[0]['type_id']['value']   = $tid;
        $fields[0]['content_parent_id']['value'] = $fields[0]['content_parent_id']['value'] + $content->getParents($tid);

        $fields[1]['slug']['attributes']['onkeyup']  = "phire.changeUri();";
        $fields[1]['title']['attributes']['onkeyup'] = "phire.createSlug(this.value, '#slug'); phire.changeUri();";

        $roles = (new \Phire\Model\Role())->getAll();
        foreach ($roles as $role) {
            $fields[0]['roles']['value'][$role->id] = $role->name;
        }

        $this->view->form = new Form\Content($fields);

        if ($this->request->isPost()) {
            $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $content = new Model\Content();
                $content->save($this->view->form->getFields(), $this->sess->user->id);
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

        if (!$this->services['acl']->isAllowed($this->sess->user->role, 'content-type-' . $tid, 'edit')) {
            $this->redirect(BASE_PATH . APP_URI . '/content');
        }

        $content = new Model\Content();
        $content->getById($id);

        if (!isset($content->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid);
        }

        if ((!$type->open_authoring) && ($content->created_by != $this->sess->user->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid);
        }

        $this->prepareView('content/edit.phtml');
        $this->view->title               = 'Content';
        $this->view->content_title       = $content->title;
        $this->view->tid                 = $tid;
        $this->view->uri                 = $content->uri;
        $this->view->created             = $content->created;
        $this->view->created_by          = $content->created_by;
        $this->view->created_by_username = $content->created_by_username;
        $this->view->updated             = $content->updated;
        $this->view->updated_by          = $content->updated_by;
        $this->view->updated_by_username = $content->updated_by_username;

        $fields = $this->application->config()['forms']['Content\Form\Content'];
        $fields[0]['type_id']['value']   = $tid;
        $fields[0]['content_parent_id']['value'] = $fields[0]['content_parent_id']['value'] + $content->getParents($tid, $id);
        $fields[1]['slug']['label']     .=
            ' [ <a href="#" class="small-link" onclick="phire.createSlug(jax(\'#title\').val(), \'#slug\');' .
            ' return phire.changeUri();">Generate URI</a> ]';

        $fields[1]['title']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';

        $roles = (new \Phire\Model\Role())->getAll();
        foreach ($roles as $role) {
            $fields[0]['roles']['value'][$role->id] = $role->name;
        }

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
                $content->update($this->view->form->getFields(), $this->sess->user->id);
                $this->view->id = $content->id;
                $this->redirect(BASE_PATH . APP_URI . '/content/edit/' . $tid . '/'. $content->id . '?saved=' . time());
            }
        }

        $this->send();
    }

    /**
     * Copy action method
     *
     * @param  int $tid
     * @param  int $id
     * @return void
     */
    public function copy($tid, $id)
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

        $content->copy($this->sess->user->id, $this->application->modules()->isRegistered('Fields'));
        $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid . '?saved=' . time());
    }

    /**
     * Remove action method
     *
     * @param  int $tid
     * @return void
     */
    public function process($tid)
    {
        if ($this->request->isPost()) {
            $content = new Model\Content();
            $content->process($this->request->getPost());
        }
        $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid .  '?saved=' . time());
    }

    /**
     * Trash action method
     *
     * @param  int $tid
     * @return void
     */
    public function trash($tid)
    {

        $this->prepareView('content/trash.phtml');
        $content = new Model\Content(['tid' => $tid]);
        $type    = new Model\ContentType();
        $type->getById($tid);

        if (!isset($type->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/content');
        }

        if ($content->hasPages($this->config->pagination)) {
            $limit = $this->config->pagination;
            $pages = new Paginator($content->getCount(-2), $limit);
            $pages->useInput(true);
        } else {
            $limit = null;
            $pages = null;
        }

        $this->view->title   = 'Content : ' . $type->name . ' : Trash';
        $this->view->pages   = $pages;
        $this->view->tid     = $tid;
        $this->view->content = $content->getAll(
            $limit, $this->request->getQuery('page'), $this->request->getQuery('sort'),
            $this->request->getQuery('title'), true
        );

        $this->send();
    }

    /**
     * JSON action method
     *
     * @param  int $id
     * @return void
     */
    public function json($id)
    {
        $json = ['parent_uri' => ''];

        $content = Table\Content::findById($id);
        if (isset($content->id)) {
            $json['parent_uri'] = $content->uri;
        }

        $this->response->setBody(json_encode($json, JSON_PRETTY_PRINT));
        $this->send(200, ['Content-Type' => 'application/json']);
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
