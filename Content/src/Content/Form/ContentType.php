<?php
/**
 * @namespace
 */
namespace Content\Form;

use Pop\Form\Element;
use Pop\Validator;
use Content\Table;

class ContentType extends \Phire\Form\AbstractForm
{

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
        $this->setAttributes('id', 'content-type-form');
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

        // Check for dupe content type names
        if ($_POST) {
            $type = Table\ContentTypes::findBy(array('name' => $this->name));
            if (isset($type->id) && ($this->id != $type->id)) {
                $this->getElement('name')
                     ->addValidator(new Validator\NotEqual($this->name, $this->i18n->__('That content type name already exists. The name must be unique.')));
            }
        }

        $this->checkFiles();
    }

    /**
     * Get the init field values
     *
     * @param  int     $tid
     * @return array
     */
    protected function getInitFields($tid = 0)
    {
        // Create initial fields
        $fields1 = array(
            'name' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Name'),
                'required'   => true,
                'attributes' => array(
                    'size'  => 80,
                    'style' => 'display: block; width: 100%;'
                )
            )
        );

        if ($tid != 0) {
            $fields1['name']['attributes']['onkeyup'] = "phire.updateTitle('#content-type-title', this);";
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

        $fields2 = array();

        // If it's a redirect from an add content request
        if (isset($_GET['redirect'])) {
            $fields2['redirect'] = array(
                'type'  => 'hidden',
                'value' => 1
            );
        }

        // Create remaining fields
        $fields3 = array(
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
                    'onclick' => "return phire.updateForm('#content-type-form', " . ((($this->hasFile) || ($dynamicFields)) ? 'true' : 'false') . ");",
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
            'uri' => array(
                'type'   => 'select',
                'label'  => $this->i18n->__('URI Type'),
                'value'  => array(
                    '1' => 'URI',
                    '0' => 'File'
                ),
                'marked' => 1,
                'attributes' => array('style' => 'width: 100px;')
            ),
            'order' =>  array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Order'),
                'value'      => 0,
                'attributes' => array(
                    'size'  => 3,
                    'style' => 'padding: 5px 4px 5px 4px;'
                )
            )
        );

        $allFields = array($fields3, $fields1);
        if (count($fieldGroups) > 0) {
            foreach ($fieldGroups as $fg) {
                $allFields[] = $fg;
            }
        }
        $allFields[] = $fields2;

        return $allFields;
    }

}

