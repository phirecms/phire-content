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
namespace Phire\Content\Controller;

use Phire\Content\Model;
use Phire\Content\Form;
use Phire\Controller\AbstractController;
use Pop\Paginator\Form as Paginator;

/**
 * Content Type Controller class
 *
 * @category   Phire\Content
 * @package    Phire\Content
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
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

        if ($type->hasPages($this->application->config['pagination'])) {
            $limit = $this->application->config['pagination'];
            $pages = new Paginator($type->getCount(), $limit);
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

        $this->view->form = Form\ContentType::createFromFieldsetConfig($fields);

        if ($this->request->isPost()) {
            $this->view->form->addFilter('strip_tags')
                 ->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8', false])
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filterValues();

                $type = new Model\ContentType();
                $type->save($this->view->form->toArray());
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

        $this->view->form = Form\ContentType::createFromFieldsetConfig($fields);
        $this->view->form->addFilter('strip_tags')
             ->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8', false]);
        $this->view->form->setFieldValues($type->toArray());

        if ($this->request->isPost()) {
            $this->view->form->addFilter('strip_tags')
                 ->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8', false])
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filterValues();

                $type = new Model\ContentType();
                $type->update($this->view->form->toArray());
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
