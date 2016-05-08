<?php

namespace Phire\Content\Controller;

use Phire\Content\Model;
use Phire\Content\Form;
use Phire\Content\Table;
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
            $content = new Model\Content();
            $type    = new Model\ContentType();
            $type->getById($tid);

            if (!isset($type->id)) {
                $this->redirect(BASE_PATH . APP_URI . '/content');
            }

            if ($this->services['acl']->isAllowed($this->sess->user->role, 'content-type-' . $type->id, 'index')) {
                if ($content->hasPages($this->config->pagination, $tid)) {
                    $limit = $this->config->pagination;
                    $pages = new Paginator($content->getCount($tid), $limit);
                    $pages->useInput(true);
                } else {
                    $limit = null;
                    $pages = null;
                }

                $content->getAll(
                    $tid, $this->request->getQuery('sort'), $this->request->getQuery('title')
                );

                $contentFlatMap = $content->getFlatMap();

                if (count($contentFlatMap) > $this->config->pagination) {
                    $page  = $this->request->getQuery('page');
                    $pages = new Paginator(count($contentFlatMap), $limit);
                    $pages->useInput(true);
                    $offset = ((null !== $page) && ((int)$page > 1)) ?
                        ($page * $limit) - $limit : 0;
                    $contentFlatMap = array_slice($contentFlatMap, $offset, $limit, true);
                } else {
                    $pages = null;
                }

                $this->view->title          = 'Content : ' . $type->name;
                $this->view->pages          = $pages;
                $this->view->tid            = $tid;
                $this->view->open_authoring = $type->open_authoring;
                $this->view->trash          = $content->getCount($tid, -2);
                $this->view->content        = $contentFlatMap;
                $this->view->searchValue    = htmlentities(strip_tags($this->request->getQuery('title')), ENT_QUOTES, 'UTF-8');
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
        $content->getAll($tid);

        $parents = [];
        foreach ($content->getFlatMap() as $c) {
            $parents[$c->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $c->depth) .
                (($c->depth > 0) ? '&rarr; ' : '') . $c->title;
        }

        $this->prepareView('content/add.phtml');
        $this->view->title = 'Content : ' . $type->name . ' : Add';
        $this->view->tid   = $tid;

        $fields = $this->application->config()['forms']['Phire\Content\Form\Content'];
        $fields[0]['type_id']['value']   = $tid;
        $fields[0]['content_parent_id']['value'] = $fields[0]['content_parent_id']['value'] + $parents;
        $fields[0]['publish_date']['value'] = date($this->application->module('phire-content')['date_format']);
        $fields[0]['publish_time']['value'] = date($this->application->module('phire-content')['time_format']);

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
                $this->sess->setRequestValue('saved', true);
                $this->redirect(BASE_PATH . APP_URI . '/content/edit/' . $tid . '/'. $content->id);
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
        $content->date_format = $this->application->module('phire-content')['date_format'];
        $content->time_format = $this->application->module('phire-content')['time_format'];
        $content->getById($id);

        if (!isset($content->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid);
        }

        if ((!$type->open_authoring) && ($content->created_by != $this->sess->user->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid);
        }

        $contents = new Model\Content();
        $contents->getAll($tid);

        $parents = [];
        foreach ($contents->getFlatMap() as $c) {
            if ($c->id != $id) {
                $parents[$c->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $c->depth) .
                    (($c->depth > 0) ? '&rarr; ' : '') . $c->title;
            }
        }

        if ($this->request->getQuery('in_edit')) {
            $this->prepareView('content/in-edit.phtml');
        } else {
            $this->prepareView('content/edit.phtml');
        }

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

        $fields = $this->application->config()['forms']['Phire\Content\Form\Content'];
        $fields[0]['type_id']['value']   = $tid;
        $fields[0]['content_parent_id']['value'] = $fields[0]['content_parent_id']['value'] + $parents;
        $fields[1]['slug']['label']     .=
            ' [ <a href="#" class="small-link" onclick="phire.createSlug(jax(\'#title\').val(), \'#slug\');' .
            ' return phire.changeUri();">Generate URI</a> ]';

        $fields[1]['title']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';
        $fields[1]['slug']['attributes']['onkeyup']  = "phire.changeUri();";

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
                $this->sess->setRequestValue('saved', true);
                $this->redirect(
                    BASE_PATH . APP_URI . '/content/edit/' . $tid . '/'. $content->id . ((null !== $this->request->getQuery('in_edit')) ? '?in_edit=1' : null)
                );
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

        $content->copy($this->sess->user->id);
        $this->sess->setRequestValue('saved', true);
        $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid);
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

        if ((null !== $this->request->getPost('content_process_action')) && ($this->request->getPost('content_process_action') == -3)) {
            $this->sess->setRequestValue('removed', true);
        } else {
            $this->sess->setRequestValue('saved', true);
        }

        $this->redirect(BASE_PATH . APP_URI . '/content/' . $tid);
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
        $content = new Model\Content();
        $type    = new Model\ContentType();
        $type->getById($tid);

        if (!isset($type->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/content');
        }

        if ($content->hasPages($this->config->pagination, $tid, -2)) {
            $limit = $this->config->pagination;
            $pages = new Paginator($content->getCount($tid, -2), $limit);
            $pages->useInput(true);
        } else {
            $limit = null;
            $pages = null;
        }

        $this->view->title   = 'Content : ' . $type->name . ' : Trash';
        $this->view->pages   = $pages;
        $this->view->tid     = $tid;
        $this->view->content = $content->getAll(
            $tid, $this->request->getQuery('sort'), $this->request->getQuery('title'), true
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
