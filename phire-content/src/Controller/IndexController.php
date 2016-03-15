<?php

namespace Phire\Content\Controller;

use Phire\Content\Model;
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
        $uri     = $this->request->getRequestUri();

        if (($uri != '/') && (substr($uri, -1) == '/')) {
            $uri = substr($uri, 0, -1);
        }

        $date = $this->isDate($uri);

        $content->separator = $this->application->module('phire-content')->config()['separator'];

        if (null !== $date) {
            $dateResult = $content->getByDate(
                $date, $this->application->module('phire-content')['date_format'] . ' ' . $this->application->module('phire-content')['time_format'],
                $this->application->module('phire-content')->config()['summary_length'],
                $this->config->pagination, $this->request->getQuery('page')
            );

            $this->prepareView('content-public/date.phtml');
            $this->view->title   = $this->formatDateTitle($date);
            $this->view->pages   = $content->getDatePages($date, $this->config->pagination);
            $this->view->archive = $content->getArchive($this->application->module('phire-content')['archive_count']);
            $this->view->items   = $dateResult;
            $this->template      = -2;
            $this->send();
        } else {
            $content->getByUri($uri);

            if ($content->isLive($this->sess)) {
                if ((($content->force_ssl) || ($content->content_type_force_ssl)) && ($_SERVER['SERVER_PORT'] != 443)) {
                    $this->redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                }
                $this->prepareView('content-public/index.phtml');
                $this->view->archive = $content->getArchive($this->application->module('phire-content')['archive_count']);

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

        $this->view->date_format   = $this->application->module('phire-content')['date_format'];
        $this->view->month_format  = $this->application->module('phire-content')['month_format'];
        $this->view->day_format    = $this->application->module('phire-content')['day_format'];
        $this->view->year_format   = $this->application->module('phire-content')['year_format'];
        $this->view->time_format   = $this->application->module('phire-content')['time_format'];
        $this->view->hour_format   = $this->application->module('phire-content')['hour_format'];
        $this->view->minute_format = $this->application->module('phire-content')['minute_format'];
        $this->view->minute_format = $this->application->module('phire-content')['minute_format'];
        $this->view->separator     = $this->application->module('phire-content')['separator'];
    }

}
