<?php

namespace Phire\Content\Form;

use Phire\Content\Table;
use Pop\Form\Form;
use Pop\Validator;

class Content extends Form
{

    /**
     * Constructor
     *
     * Instantiate the form object
     *
     * @param  array  $fields
     * @param  string $action
     * @param  string $method
     * @return Content
     */
    public function __construct(array $fields, $action = null, $method = 'post')
    {
        parent::__construct($fields, $action, $method);
        $this->setAttribute('id', 'content-form');
        $this->setIndent('    ');
    }

    /**
     * Set the field values
     *
     * @param  array $values
     * @return Content
     */
    public function setFieldValues(array $values = null)
    {
        parent::setFieldValues($values);

        if (($_POST) && (null !== $this->uri)) {
            // Check for dupe name
            $content = Table\Content::findBy(['uri' => $this->uri]);
            if (isset($content->id) && ($this->id != $content->id)) {
                $this->getElement('uri')
                     ->addValidator(new Validator\NotEqual($this->uri, 'That URI already exists.'));
            }
        }

        return $this;
    }

}