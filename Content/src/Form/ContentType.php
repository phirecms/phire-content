<?php

namespace Content\Form;

use Content\Table;
use Pop\Form\Form;
use Pop\Validator;

class ContentType extends Form
{

    /**
     * Constructor
     *
     * Instantiate the form object
     *
     * @param  array  $fields
     * @param  string $action
     * @param  string $method
     * @return ContentType
     */
    public function __construct(array $fields, $action = null, $method = 'post')
    {
        parent::__construct($fields, $action, $method);
        $this->setAttribute('id', 'content-type-form');
        $this->setIndent('    ');
    }

    /**
     * Set the field values
     *
     * @param  array $values
     * @return ContentType
     */
    public function setFieldValues(array $values = null)
    {
        parent::setFieldValues($values);

        if (($_POST) && (null !== $this->name)) {
            // Check for dupe name
            $type = Table\ContentTypes::findBy(['name' => $this->name]);
            if (isset($type->id) && ($this->id != $type->id)) {
                $this->getElement('name')
                     ->addValidator(new Validator\NotEqual($this->name, 'That content type already exists.'));
            }
        }

        return $this;
    }

}