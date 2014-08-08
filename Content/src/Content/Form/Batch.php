<?php
/**
 * @namespace
 */
namespace Content\Form;

use Pop\Validator;
use Content\Model;
use Content\Table;

class Batch extends \Phire\Form\AbstractForm
{

    /**
     * Constructor method to instantiate the form object
     *
     * @param  string $action
     * @param  string $method
     * @param  int $tid
     * @return self
     */
    public function __construct($action = null, $method = 'post', $tid = 0)
    {
        parent::__construct($action, $method, null, '        ');

        $sess = \Pop\Web\Session::getInstance();
        $siteIds = array(0 => $_SERVER['HTTP_HOST']);

        $sites = \Phire\Table\Sites::findAll();
        foreach ($sites->rows as $site) {
            if (in_array($site->id, $sess->user->site_ids)) {
                $siteIds[$site->id] = $site->domain;
            }
        }

        // Categories
        $catsAry = array();
        $cats = new Model\Category();
        $cats->getAll();
        $cats = $cats->getCategoryArray();
        unset($cats[0]);
        foreach ($cats as $id => $cat) {
            $depth = substr_count($cat, '&nbsp;');
            $cat = str_replace(array('&nbsp;', '&gt; '), array('', ''), $cat);
            if ($depth == 0) {
                $cat = '<strong style="color: #000;">' . $cat . '</strong>';
            }
            $catsAry[$id] = '<span style="color: #666; border-bottom: dotted 1px #ccc; display: block; float: left; width: ' . (180 - (3 * $depth)) . 'px; font-size: 0.9em; padding-top: 0; padding-bottom: 7px; padding-left: ' . (3 * $depth) . 'px;">' . $cat . '</span>';
        }

        $fields1 = array(
            'submit' => array(
                'type'  => 'submit',
                'value' => $this->i18n->__('UPLOAD'),
                'attributes' => array(
                    'class' => 'save-btn',
                    'style' => 'width: 200px;'
                )
            ),
            'site_id' => array(
                'type'       => 'select',
                'label'      => $this->i18n->__('Site'),
                'value'      => $siteIds,
                'marked'     => 0,
                'attributes' => array('style' => 'width: 200px;')
            ),
            'type_id' => array(
                'type'  => 'hidden',
                'value' => $tid
            )
        );

        // Add categories
        if (count($catsAry) > 0) {
            $fields1['category_id'] = array(
                'type'     => 'checkbox',
                'label'    => $this->i18n->__('Categories'),
                'value'    => $catsAry
            );
        }

        $browser = new \Pop\Web\Browser();
        $height = (($browser->isMsie()) && ($browser->getVersion() < 9)) ? '30' : '26';

        $fields2 = array(
            'file_name_1' => array(
                'type'       => 'file',
                'label'      => '<a href="#" onclick="phire.addBatchFields(' . ini_get('max_file_uploads') . '); return false;">[+]</a> ' . $this->i18n->__('File') . ' / ' . $this->i18n->__('Title') . ': <span style="font-weight: normal; color: #666; padding: 0 0 0 10px; font-size: 0.9em;">[ <strong>' . ini_get('max_file_uploads') . '</strong> ' . $this->i18n->__('Files Max') . ' | <strong>' . \Phire\Table\Config::getMaxFileSize() . '</strong> ' . $this->i18n->__('Max Size Each') . ' | <strong>' . str_replace(array('M', 'K'), array(' MB', ' KB'), strtoupper(ini_get('post_max_size'))) . '</strong> ' . $this->i18n->__('Max Size Total') . ' ]</span>',
                'attributes' => array(
                    'size' => 40,
                    'style' => 'display: block; margin: 0 0 10px 0; padding: 1px 4px 1px 1px; height: ' . $height . 'px;'
                )
            ),
            'file_title_1' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size' => 60,
                    'style' => 'display: block; margin: 0 0 10px 0; padding: 5px 4px 6px 4px; height: 17px;'
                )
            )
        );

        $formats = \Pop\Archive\Archive::formats();

        if (isset($formats['phar'])) {
            unset($formats['phar']);
        }

        $fields3 = array();

        if (count($formats) > 0) {
            $fields3['archive_file'] = array(
                'type'       => 'file',
                'label'      => $this->i18n->__('Archive of Multiple Files') . '<br /><span style="display: block; margin: 5px 0 0 0; font-size: 0.9em;"><strong>' . $this->i18n->__('Supported Types') . ':</strong> ' . implode(', ', array_keys($formats)) . '</span>',
                'attributes' => array(
                    'size' => 40,
                    'style' => 'display: block; margin: 0 0 10px 0; padding: 1px 4px 1px 1px; margin: 0px 0px 10px 0; height: 26px;'
                )
            );
        }

        $this->initFieldsValues = array($fields1, $fields2, $fields3);

        $this->setAttributes('id', 'batch-form')
             ->setAttributes('onsubmit', 'phire.showLoading();');
    }

}

