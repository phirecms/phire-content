<?php
/**
 * @namespace
 */
namespace Content\Form;

use Pop\Form\Element;
use Pop\Validator;
use Content\Table;

class Template extends \Phire\Form\AbstractForm
{

    /**
     * Content types
     *
     * @var array
     */
    protected static $contentTypes = array(
        'text/html'           => 'text/html',
        'text/plain'          => 'text/plain',
        'text/css'            => 'text/css',
        'text/javascript'     => 'text/javascript',
        'text/xml'            => 'text/xml',
        'application/xml'     => 'application/xml',
        'application/rss+xml' => 'application/rss+xml',
        'application/json'    => 'application/json'
    );

    /**
     * Mobile templates
     *
     * @var array
     */
    protected static $mobileTemplates = array(
        'desktop'        => 'Desktop',
        'mobile'         => 'Any Mobile Device',
        'phone'          => 'Any Mobile Phone',
        'tablet'         => 'Any Mobile Tablet',
        'iphone'         => 'iPhone',
        'ipad'           => 'iPad',
        'android-phone'  => 'Android Phone',
        'android-tablet' => 'Android Tablet',
        'windows-phone'  => 'Windows Phone',
        'windows-tablet' => 'Windows Tablet',
        'blackberry'     => 'Blackberry',
        'palm'           => 'Palm'
    );

    /**
     * Constructor method to instantiate the form object
     *
     * @param  string  $action
     * @param  string  $method
     * @param  int     $tid
     * @return self
     */
    public function __construct($action = null, $method = 'post', $tid = 0)
    {
        parent::__construct($action, $method, null, '        ');
        $this->initFieldsValues = $this->getInitFields($tid);
        $this->setAttributes('id', 'template-form');
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
        if (($_POST) && isset($_POST['id'])) {
            $tmpl = Table\Templates::findBy(array('name' => $this->name));
            if (isset($tmpl->id) && ($this->id != $tmpl->id)) {
                $this->getElement('name')
                     ->addValidator(new Validator\NotEqual($this->name, $this->i18n->__('That template name already exists. The name must be unique.')));
            }

            if ($this->parent_id != '0') {
                $tmpl = Table\Templates::findBy(array('device' => $this->device, 'parent_id' => $this->parent_id));
                if (isset($tmpl->id) && ($this->id != $tmpl->id)) {
                    $this->getElement('device')
                         ->addValidator(new Validator\NotEqual($this->device, $this->i18n->__('That device is already added to that template.')));
                }
                $tmpl = Table\Templates::findBy(array('device' => $this->device, 'id' => $this->parent_id));
                if (isset($tmpl->id) && ($this->id != $tmpl->id)) {
                    $this->getElement('device')
                         ->addValidator(new Validator\NotEqual($this->device, $this->i18n->__('That device is already added to that template.')));
                }
            }
        }

        $this->checkFiles();

        return $this;
    }

    /**
     * Get the init field values
     *
     * @param  int     $tid
     * @return array
     */
    protected function getInitFields($tid = 0)
    {
        $parents = array(0 => '----');

        // Get parent templates
        $tmpls = Table\Templates::findAll('id ASC');
        foreach ($tmpls->rows as $tmpl) {
            if (($tmpl->id != $tid) && (null === $tmpl->parent_id)) {
                $parents[$tmpl->id] = $tmpl->name;
            }
        }

        // Create initial fields
        $fields1 = array(
            'name' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Name'),
                'required'   => true,
                'attributes' => array(
                    'size'  => 95,
                    'style' => 'display: block; width: 100%;'
                )
            )
        );

        if ($tid != 0) {
            $fields1['name']['attributes']['onkeyup'] = "phire.updateTitle('#template-title', this);";
        }

        $fieldGroups = array();
        $dynamicFields = false;

        $model = str_replace('Form', 'Model', get_class($this));
        $newFields = \Phire\Model\Field::getByModel($model, 0, $tid);
        if ($newFields['dynamic']) {
            $dynamicFields = true;
        }
        if ($newFields['hasFile']) {
            $this->hasFile = true;
        }
        foreach ($newFields as $key => $value) {
            if (is_numeric($key)) {
                $fieldGroups[] = $value;
            }
        }

        // Create remaining fields
        $fields3 = array(
            'template' => array(
                'type'       => 'textarea',
                'label'      => $this->i18n->__('Template'),
                'required'   => true,
                'attributes' => array(
                    'rows'    => 30,
                    'cols'    => 110,
                    'style' => 'display: block; width: 100%;'
                )
            )
        );

        $fields4 = array(
            'submit' => array(
                'type'  => 'submit',
                'value' => $this->i18n->__('SAVE'),
                'attributes' => array(
                    'class'   => 'save-btn'
                )
            ),
            'update' => array(
                'type'       => 'button',
                'value'      => $this->i18n->__('UPDATE'),
                'attributes' => array(
                    'onclick' => "return phire.updateForm('#template-form', " . ((($this->hasFile) || ($dynamicFields)) ? 'true' : 'false') . ");",
                    'class'   => 'update-btn'
                )
            ),
            'id' => array(
                'type'  => 'hidden',
                'value' => 0
            ),
            'update_value' => array(
                'type'  => 'hidden',
                'value' => 0
            ),
            'parent_id' => array(
                'type'       => 'select',
                'label'      => $this->i18n->__('Parent'),
                'value'      => $parents,
                'attributes' => array(
                    'style'    => 'width: 200px;'
                )
            ),
            'content_type' => array(
                'type'  => 'select',
                'label' => $this->i18n->__('Content Type'),
                'value' => self::$contentTypes,
                'attributes' => array(
                    'style'    => 'width: 200px;'
                )
            ),
            'device' => array(
                'type'  => 'select',
                'label' => $this->i18n->__('Device'),
                'value' => self::$mobileTemplates,
                'attributes' => array(
                    'style'    => 'width: 200px;'
                )
            )
        );

        $flds = array($fields4, $fields1);

        if (count($fieldGroups) > 0) {
            foreach ($fieldGroups as $fg) {
                $flds[] = $fg;
            }
        }

        $flds[] = $fields3;

        return $flds;
    }

    /**
     * Get the content types
     *
     * @return array
     */
    public static function getContentTypes()
    {
        return self::$contentTypes;
    }

    /**
     * Get the mobile templates
     *
     * @return array
     */
    public static function getMobileTemplates()
    {
        return self::$mobileTemplates;
    }

}

