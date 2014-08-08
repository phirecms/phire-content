<?php
/**
 * @namespace
 */
namespace Content\Form;

use Pop\Validator;
use Content\Table;

class Migrate extends \Phire\Form\AbstractForm
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

        $sitesAry = array(
            '----' => '----',
            'Main' => $_SERVER['HTTP_HOST']
        );
        $sites = \Phire\Table\Sites::findAll('id ASC');
        foreach ($sites->rows as $site) {
            $sitesAry[$site->id] = $site->domain;
        }

        $this->initFieldsValues = array(
            'site_from' => array(
                'type'       => 'select',
                'label'      => '<span class="label-pad-4">' . $this->i18n->__('From') . '</span><span class="label-pad-4">' . $this->i18n->__('To') . '</span>',
                'value'      => $sitesAry,
                'validators' => new Validator\NotEqual('----'),
                'attributes' => array(
                    'style'  => 'width: 250px; margin-right: 30px;'
                )
            ),
            'site_to' => array(
                'type'  => 'select',
                'value' => $sitesAry,
                'validators' => new Validator\NotEqual('----'),
                'attributes' => array(
                    'style'  => 'width: 250px; margin-right: 15px;'
                )
            ),
            'migrate' => array(
                'type'  => 'select',
                'value' => array(
                    '----' => $this->i18n->__('All Content'),
                    'URI'  => $this->i18n->__('URIs Only (pages, etc.)'),
                    'File' => $this->i18n->__('Files Only (media, etc.)')
                ),
                'attributes' => array(
                    'style'  => 'margin-right: 15px;'
                )
            ),
            'submit' => array(
                'type'  => 'submit',
                'value' => $this->i18n->__('MIGRATE'),
                'attributes' => array(
                    'class' => 'save-btn',
                    'style' => 'width: 150px; height: 30px;'
                )
            )
        );

        $this->setAttributes('id', 'site-migration-form');
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

        // Add validators for checking dupe names and devices
        if ($_POST) {
            if ($this->site_from == $this->site_to) {
                $this->getElement('site_to')
                     ->addValidator(new Validator\NotEqual($this->site_from, $this->i18n->__('The sites cannot be the same.')));
            }
        }

        return $this;
    }

}

