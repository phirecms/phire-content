<?php
/**
 * @namespace
 */
namespace Content\Form;

use Pop\Form\Element;
use Pop\Validator;
use Content\Table;

class Category extends \Phire\Form\AbstractForm
{

    /**
     * Constructor method to instantiate the form object
     *
     * @param  string  $action
     * @param  string  $method
     * @param  int     $cid
     * @return self
     */
    public function __construct($action = null, $method = 'post', $cid = 0)
    {
        parent::__construct($action, $method, null, '        ');
        $this->initFieldsValues = $this->getInitFields($cid);
        $this->setAttributes('id', 'category-form');
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
            $slug = Table\Categories::findBy(array('slug' => $this->slug));
            if (isset($slug->id) && ((int)$this->parent_id == (int)$slug->parent_id) && ($this->id != $slug->id)) {
                $this->getElement('slug')
                     ->addValidator(new Validator\NotEqual($this->slug, $this->i18n->__('That URI already exists under that parent category.')));
            }
        }

        $this->checkFiles();

        return $this;
    }

    /**
     * Get the init field values
     *
     * @param  int     $cid
     * @return array
     */
    protected function getInitFields($cid = 0)
    {
        // Get children, if applicable
        $children = ($cid != 0) ? $this->children($cid) : array();
        $parents = array(0 => '----');

        // Prevent the object's children or itself from being in the parent drop down
        $cats = Table\Categories::findAll('id ASC');
        foreach ($cats->rows as $cat) {
            if (($cat->id != $cid) && (!in_array($cat->id, $children))) {
                $parents[$cat->id] = $cat->title;
            }
        }

        // Get parents and children, if applicable
        $parents = array(0 => '----');

        // Prevent the object's children or itself from being in the parent drop down
        $content = Table\Categories::findAll('order ASC');
        foreach ($content->rows as $c) {
            if (($c->parent_id == 0) && ($c->id != $cid)) {
                $parents[$c->id] = $c->title;
                $children = $this->children($c->id);
                if (count($children) > 0) {
                    foreach ($children as $id => $child) {
                        if ($id != $cid) {
                            $parents[$id] = $child;
                        }
                    }
                }
            }
        }

        // Create initial fields
        $fields1 = array(
            'category_title' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Title'),
                'required'   => true,
                'attributes' => array(
                    'size'    => 80,
                    'style'   => 'display: block; width: 100%;',
                    'onkeyup' => "phire.catSlug('category_title', 'slug');"
                )
            ),
            'slug' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Slug'),
                'required'   => true,
                'attributes' => array(
                    'size'    => 80,
                    'style'   => 'display: block; width: 100%;' ,
                    'onkeyup' => "phire.catSlug(null, 'slug');"
                )
            )
        );

        if ($cid != 0) {
            $fields1['category_title']['attributes']['onkeyup'] .= " phire.updateTitle('#category-header-title', this);";
        }

        $fieldGroups = array();
        $dynamicFields = false;

        $model = str_replace('Form', 'Model', get_class($this));
        $newFields = \Phire\Model\Field::getByModel($model, 0, $cid);
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
                    'onclick' => "return phire.updateForm('#category-form', " . ((($this->hasFile) || ($dynamicFields)) ? 'true' : 'false') . ");",
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
            'parent_id' => array(
                'type'       => 'select',
                'label'      => $this->i18n->__('Parent'),
                'value'      => $parents,
                'attributes' => array(
                    'style'    => 'width: 200px;',
                    'onchange' => "phire.catSlug(null, 'slug');"
                )
            ),
            'order' => array(
                'type'       => 'text',
                'label'      => $this->i18n->__('Order'),
                'attributes' => array('size' => 3),
                'value'      => 0
            ),
            'total' => array(
                'type'       => 'radio',
                'label'      => $this->i18n->__('Show Total'),
                'value'      => array(
                    '1' => $this->i18n->__('Yes'),
                    '0' => $this->i18n->__('No')
                ),
                'marked' => '0'
            )
        );

        $navOrder = array();
        $navsMarked = array();
        if ($cid != 0) {
            $navs = Table\ContentToNavigation::findAll(null, array('category_id' => $cid));
            if (isset($navs->rows[0])) {
                foreach ($navs->rows as $nav) {
                    $navsMarked[] = $nav->navigation_id;
                    $navOrder[$nav->navigation_id] = $nav->order;
                }
            }
        }

        $navsAry = array();
        $navs = Table\Navigation::findAll('id ASC');
        foreach ($navs->rows as $nav) {
            $navsAry[$nav->id] = '<strong style="color: #666; border-bottom: dotted 1px #ccc; display: block; float: left; width: 150px; font-size: 0.9em; padding: 0 0 7px; 0">' . $nav->navigation . '</strong> <input style="margin: -4px 0 0 10px; padding: 2px; font-size: 0.9em;; width: 25px; text-align: center;" type="text" name="navigation_order_' . $nav->id . '" value="' . (isset($navOrder[$nav->id]) ? $navOrder[$nav->id] : 0) . '" size="3" />';
        }

        $fields3['navigation_id'] = array(
            'type'   => 'checkbox',
            'label'  => $this->i18n->__('Navigation') . ' / ' . $this->i18n->__('Order'),
            'value'  => $navsAry,
            'marked' => $navsMarked
        );

        $flds = array($fields3, $fields1);

        if (count($fieldGroups) > 0) {
            foreach ($fieldGroups as $fg) {
                $flds[] = $fg;
            }
        }

        return $flds;
    }

    /**
     * Recursive method to get children of the category object
     *
     * @param  int   $pid
     * @param  array $children
     * @param  int   $depth
     * @return array
     */
    protected function children($pid, $children = array(), $depth = 0)
    {
        $c = Table\Categories::findBy(array('parent_id' => $pid));

        if (isset($c->rows[0])) {
            foreach ($c->rows as $child) {
                $children[$child->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', ($depth + 1)) . '&gt; ' . $child->title;
                $c = Table\Categories::findBy(array('parent_id' => $child->id));
                if (isset($c->rows[0])) {
                    $d = $depth + 1;
                    $children = $this->children($child->id, $children, $d);
                }
            }
        }

        return $children;
    }

}

