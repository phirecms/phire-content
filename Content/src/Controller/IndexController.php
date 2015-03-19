<?php

namespace Content\Controller;

use Content\Model;
use Phire\Controller\AbstractController;

class IndexController extends AbstractController
{

    /**
     * Index action method
     *
     * @return void
     */
    public function index()
    {
        $content = new Model\Content();
        $content->getByUri($this->request->getRequestUri());

        if (isset($content->id)) {
            $this->prepareView('content-public/index.phtml');
            $this->view->title = $content->title;
            $this->send();
        } else {
            $this->error();
        }
    }

    /**
     * Error action method
     *
     * @return void
     */
    public function error()
    {
        $this->prepareView('content-public/error.phtml');
        $this->view->title = '404 Error';
        $this->send(404);
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
