<?php
/**
 * @namespace
 */
namespace Content\Form;

use Pop\Form\Element;
use Pop\Validator;
use Content\Table;

class Navigation extends \Phire\Form\AbstractForm
{

    /**
     * Constructor method to instantiate the form object
     *
     * @param  string  $action
     * @param  string  $method
     * @param  int     $nid
     * @return self
     */
    public function __construct($action = null, $method = 'post', $nid = 0)
    {
        parent::__construct($action, $method, null, '        ');
        $this->initFieldsValues = $this->getInitFields($nid);
        $this->setAttributes('id', 'navigation-form');
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

        // Add validators for checking dupe slugs
        if (($_POST) && isset($_POST['id'])) {
            $nav = Table\Navigation::findBy(array('navigation' => $this->navigation));
            if (isset($nav->id) && ((int)$this->id != (int)$nav->id)) {
                $this->getElement('navigation')
                     ->addValidator(new Validator\NotEqual($this->navigation, $this->i18n->__('That navigation name already exists.')));
            }
        }

        $this->checkFiles();

        return $this;
    }

    /**
     * Get the init field values
     *
     * @param  int     $nid
     * @return array
     */
    protected function getInitFields($nid = 0)
    {

        // Create initial fields
        $fields1 = array(
            'navigation' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Navigation'),
                'required'   => true,
                'attributes' => array(
                    'size'    => 80
                )
            ),
            'top_node' => array(
                'type'       => 'text',
                'label'      => '<span class="label-pad-1">' . $this->i18n->__('Top Node') . '</span><span class="label-pad-1">' . $this->i18n->__('ID') . '</span><span class="label-pad-1">' . $this->i18n->__('Class') . '</span><span>' . $this->i18n->__('Attributes') . ':</span>',
                'attributes' => array(
                    'size'    => 10
                )
            ),
            'top_id' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size'    => 10
                )
            ),
            'top_class' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size'    => 10
                )
            ),
            'top_attributes' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size'    => 40
                )
            ),
            'parent_node' => array(
                'type'       => 'text',
                'label'      => '<span class="label-pad-1">' . $this->i18n->__('Parent Node') . '</span><span class="label-pad-1">' . $this->i18n->__('ID') . '</span><span class="label-pad-1">' . $this->i18n->__('Class') . '</span><span>' . $this->i18n->__('Attributes') . '</span>',
                'attributes' => array(
                    'size'    => 10
                )
            ),
            'parent_id' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size'    => 10
                )
            ),
            'parent_class' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size'    => 10
                )
            ),
            'parent_attributes' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size'    => 40
                )
            ),
            'child_node' => array(
                'type'       => 'text',
                'label'      => '<span class="label-pad-1">' . $this->i18n->__('Child Node') . '</span><span class="label-pad-1">' . $this->i18n->__('ID') . '</span><span class="label-pad-1">' . $this->i18n->__('Class') . '</span><span>' . $this->i18n->__('Attributes') . '</span>',
                'attributes' => array(
                    'size'    => 10
                )
            ),
            'child_id' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size'    => 10
                )
            ),
            'child_class' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size'    => 10
                )
            ),
            'child_attributes' => array(
                'type'       => 'text',
                'attributes' => array(
                    'size'    => 40
                )
            )
        );

        if ($nid != 0) {
            $fields1['navigation']['attributes']['onkeyup'] = "phire.updateTitle('#navigation-title', this);";
        }

        $fieldGroups = array();
        $dynamicFields = false;

        $model = str_replace('Form', 'Model', get_class($this));
        $newFields = \Phire\Model\Field::getByModel($model, 0, $nid);
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
            'submit' => array(
                'type'  => 'submit',
                'value' => $this->i18n->__('SAVE'),
                'attributes' => array(
                    'class' => 'save-btn'
                )
            ),
            'update' => array(
                'type'       => 'button',
                'value'      => $this->i18n->__('UPDATE'),
                'attributes' => array(
                    'onclick' => "return phire.updateForm('#navigation-form', " . ((($this->hasFile) || ($dynamicFields)) ? 'true' : 'false') . ");",
                    'class' => 'update-btn'
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
            'on_class' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('"On" Class'),
                'attributes' => array(
                    'size'    => 15
                )
            ),
            'off_class' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('"Off" Class'),
                'attributes' => array(
                    'size'    => 15
                )
            ),
            'spaces' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Indentation Spaces'),
                'attributes' => array(
                    'size'    => 3
                ),
                'value'      => 4
            )
        );

        $allFields = array($fields3, $fields1);
        if (count($fieldGroups) > 0) {
            foreach ($fieldGroups as $fg) {
                $allFields[] = $fg;
            }
        }

        return $allFields;
    }

}

