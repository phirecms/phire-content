<?php

namespace Phire\Content\Controller;

use Phire\Content\Model;
use Phire\Content\Form;
use Phire\Content\Table;
use Phire\Controller\AbstractController;
use Pop\Paginator\Paginator;

class TypeController extends AbstractController
{

    /**
     * Index action method
     *
     * @return void
     */
    public function index()
    {
        $type = new Model\ContentType();

        if ($type->hasPages($this->config->pagination)) {
            $limit = $this->config->pagination;
            $pages = new Paginator($type->getCount(), $limit);
            $pages->useInput(true);
        } else {
            $limit = null;
            $pages = null;
        }

        $this->prepareView('content/types/index.phtml');
        $this->view->title = 'Content : Types';
        $this->view->pages = $pages;
        $this->view->types = $type->getAll(
            $limit, $this->request->getQuery('page'), $this->request->getQuery('sort')
        );

        $this->send();
    }

    /**
     * Add action method
     *
     * @return void
     */
    public function add()
    {
        $this->prepareView('content/types/add.phtml');
        $this->view->title = 'Content : Types : Add';

        $fields = $this->application->config()['forms']['Phire\Content\Form\ContentType'];

        $this->view->form = new Form\ContentType($fields);

        if ($this->request->isPost()) {
            $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $type = new Model\ContentType();
                $type->save($this->view->form->getFields());
                $this->view->id = $type->id;
                $this->sess->setRequestValue('saved', true);
                $this->redirect(BASE_PATH . APP_URI . '/content/types/edit/' . $type->id);
            }
        }

        $this->send();
    }

    /**
     * Edit action method
     *
     * @param  int $id
     * @return void
     */
    public function edit($id)
    {
        $type = new Model\ContentType();
        $type->getById($id);

        $this->prepareView('content/types/edit.phtml');
        $this->view->title              = 'Content : Types';
        $this->view->content_type_name = $type->name;

        $fields = $this->application->config()['forms']['Phire\Content\Form\ContentType'];

        $fields[1]['name']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';

        $this->view->form = new Form\ContentType($fields);
        $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
             ->setFieldValues($type->toArray());

        if ($this->request->isPost()) {
            $this->view->form->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $type = new Model\ContentType();
                $type->update($this->view->form->getFields());
                $this->view->id = $type->id;
                $this->sess->setRequestValue('saved', true);
                $this->redirect(BASE_PATH . APP_URI . '/content/types/edit/' . $type->id);
            }
        }

        $this->send();
    }

    /**
     * Remove action method
     *
     * @return void
     */
    public function remove()
    {
        if ($this->request->isPost()) {
            $type = new Model\ContentType();
            $type->remove($this->request->getPost());
        }
        $this->sess->setRequestValue('removed', true);
        $this->redirect(BASE_PATH . APP_URI . '/content/types');
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
