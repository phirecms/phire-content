<?php
/**
 * @namespace
 */
namespace Content\Form;

use Pop\Validator;
use Content\Table;

class Media extends \Phire\Form\AbstractForm
{

    /**
     * Constructor method to instantiate the form object
     *
     * @param  string $action
     * @param  string $method
     * @return self
     */
    public function __construct($action = null, $method = 'post')
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

        $typesAry = array();
        $types = Table\ContentTypes::findAll('order ASC', array('uri' => 0));
        foreach ($types->rows as $type) {
            $typesAry[$type->id] = $type->name;
        }

        $this->initFieldsValues = array(
            'site_id' => array(
                'type'       => 'select',
                'value'      => $siteIds,
                'marked'     => 0,
                'attributes' => array('style' => 'width: 150px;')
            ),
            'type_id' => array(
                'type'     => 'select',
                'required' => true,
                'value'    => $typesAry
            ),
            'uri' => array(
                'type'       => 'file',
                'required'   => true,
                'attributes' => array(
                    'size' => 20,
                    'style' => 'width: 200px;'
                )
            ),
            'submit' => array(
                'type'  => 'submit',
                'value' => $this->i18n->__('UPLOAD'),
                'attributes' => array(
                    'class' => 'upload-btn'
                )
            )
        );

        $this->setAttributes('id', 'media-form')
             ->setAttributes('onsubmit', 'phire.showLoading();');
    }

    /**
     * Set the field values
     *
     * @param  array $values
     * @param  array $filters
     * @return \Pop\Form\Form
     */
    public function setFieldValues(array $values = null, $filters = null)
    {
        parent::setFieldValues($values, $filters);
        $this->checkFiles();
        return $this;
    }

}

