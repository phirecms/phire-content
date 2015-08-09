<?php

namespace Content\Controller;

use Content\Model;
use Phire\Controller\AbstractController;

class IndexController extends AbstractController
{

    /**
     * Current template reference
     * @var mixed
     */
    protected $template = null;

    /**
     * Index action method
     *
     * @return void
     */
    public function index()
    {
        $content = new Model\Content();
        $date = $this->isDate($this->request->getRequestUri());

        if (null !== $date) {
            $dateResult = $content->getByDate(
                $date, $this->config->datetime_format, $this->application->module('Content')->config()['summary_length'],
                $this->config->pagination, $this->request->getQuery('page'), $this->application->modules()->isRegistered('Fields')
            );

            $this->prepareView('content-public/date.phtml');
            $this->view->title = $this->formatDateTitle($date);
            $this->view->pages = $dateResult['pages'];
            $this->view->rows  = $dateResult['rows'];
            $this->template    = -2;
            $this->send();
        } else {
            $content->getByUri($this->request->getRequestUri(), $this->application->modules()->isRegistered('Fields'));

            if ($content->isLive($this->sess)) {
                $this->prepareView('content-public/index.phtml');
                $this->view->merge($content->toArray());
                $this->template = $content->template;
                $this->send(200, ['Content-Type' => $content->content_type]);
            } else {
                $this->error();
            }
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
        $this->template    = -1;
        $this->send(404);
    }

    /**
     * Get current template
     *
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set current template
     *
     * @param  string $template
     * @return IndexController
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Method to determine if the URI is a date
     *
     * @param  string $uri
     * @return mixed
     */
    protected function isDate($uri)
    {
        if (substr($uri, 0, 1) == '/') {
            $uri = substr($uri, 1);
        }
        $regexes = [
            10 => '/^(1[0-9]{3}|2[0-9]{3})\/(0[1-9]|1[0-2])\/(0[1-9]|1[0-9]|2[0-9]|3[0-1])$/', // YYYY/MM/DD
            7  => '/^(1[0-9]{3}|2[0-9]{3})\/(0[1-9]|1[0-2])$/',                                // YYYY/MM
            4  => '/^(1[0-9]{3}|2[0-9]{3})$/'                                                  // YYYY
        ];

        $result = null;

        foreach ($regexes as $length => $regex) {
            $match = substr($uri, 0, $length);
            if (preg_match($regex, $match)) {
                $result = $match;
                break;
            }
        }

        return $result;
    }

    /**
     * Format date title
     *
     * @param  string $date
     * @return string
     */
    protected function formatDateTitle($date)
    {
        if (substr($date, 0, 1) == '/') {
            $date = substr($date, 1);
        }

        switch (substr_count($date, '/')) {
            case 1:
                $date = date('F Y', strtotime(str_replace('/', '-', $date)));
                break;
            case 2:
                $date = date('F j, Y', strtotime(str_replace('/', '-', $date)));
                break;
        }

        return $date;
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
