<?php
/**
 * Phire Content Module
 *
 * @link       https://github.com/phirecms/phire-content
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Phire\Content\Form;

use Phire\Content\Table;
use Pop\Form\Form;
use Pop\Validator;

/**
 * Content Form class
 *
 * @category   Phire\Content
 * @package    Phire\Content
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
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
     */
    public function __construct(array $fields = null, $action = null, $method = 'post')
    {
        parent::__construct($fields, $action, $method);
        $this->setAttribute('id', 'content-form');
        $this->setAttribute('class', 'data-form');
        $this->setIndent('    ');
    }

    /**
     * Set the field values
     *
     * @param  array $values
     * @return Content
     */
    public function setFieldValues(array $values)
    {
        parent::setFieldValues($values);

        if (($_POST) && (null !== $this->uri)) {
            // Check for dupe name
            $content = Table\Content::findOne(['uri' => $this->uri]);
            if (isset($content->id) && ($this->id != $content->id)) {
                $this->getField('uri')
                     ->addValidator(new Validator\NotEqual($this->uri, 'That URI already exists.'));
            }
        }

        return $this;
    }

}