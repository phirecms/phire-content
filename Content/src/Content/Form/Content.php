<?php
/**
 * @namespace
 */
namespace Content\Form;

use Pop\File\Dir;
use Pop\Form\Element;
use Pop\Validator;
use Content\Model;
use Content\Table;

class Content extends \Phire\Form\AbstractForm
{

    /**
     * Constructor method to instantiate the form object
     *
     * @param  string         $action
     * @param  string         $method
     * @param  int            $tid
     * @param  int            $mid
     * @param  array          $cfg
     * @param \Phire\Auth\Acl $acl
     * @return self
     */
    public function __construct($action = null, $method = 'post', $tid = 0, $mid = 0, $cfg = array(), $acl = null)
    {
        parent::__construct($action, $method, null, '        ');

        // Generate fields for content type select first
        if ($tid == 0) {
            $typesAry = array();
            $types = Table\ContentTypes::findAll('order ASC');
            foreach ($types->rows as $type) {
                if ($acl->isAuth('Content\Controller\Content\IndexController', 'add_' . $type->id)) {
                    $typesAry[$type->id] = $type->name;
                }
            }

            $this->initFieldsValues = array(
                'type_id' => array(
                    'type'     => 'select',
                    'required' => true,
                    'label'    => $this->i18n->__('Select Content Type'),
                    'value'    => $typesAry,
                    'attributes' => array(
                        'style' => 'margin: 0 10px 0 0; padding: 6px 5px 7px 5px; height: 32px;'
                    )
                ),
                'submit' => array(
                    'type'  => 'submit',
                    'value' => $this->i18n->__('SELECT'),
                    'attributes' => array(
                        'class' => 'save-btn',
                        'style' => 'margin: 0; padding: 5px 6px 6px 6px; width: 100px; height: 32px;'
                    )
                )
            );
            $id = 'content-select-form';
        // Else, generate fields for the content object
        } else {
            $this->initFieldsValues = $this->getInitFields($tid, $mid, $cfg);
            $id = 'content-form';
        }

        $this->setAttributes('id', $id);
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

        // Add validators for checking dupe uris
        if (($_POST) && isset($_POST['id'])) {
            $type = Table\ContentTypes::findById($_POST['type_id']);
            if ($type->uri) {
                $uri = Table\Content::findBy(array('slug' => $this->uri));
                if (($type->uri) && (isset($uri->id)) && ((int)$this->parent_id == (int)$uri->parent_id) &&
                    ($this->id != $uri->id) && ($this->site_id == $uri->site_id)) {
                    if ($this->uri == '') {
                        $this->getElement('uri')
                             ->addValidator(new Validator\NotEmpty($this->uri, $this->i18n->__('The root URI already exists.')));
                    } else if ($this->uri != '#') {
                        $this->getElement('uri')
                             ->addValidator(new Validator\NotEqual($this->uri, $this->i18n->__('That URI already exists under that parent content.')));
                    }
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
     * @param  int     $mid
     * @param  array   $cfg
     * @return array
     */
    protected function getInitFields($tid = 0, $mid = 0, $cfg = array())
    {
        // Get types and type object
        $typesAry = array();
        $types = Table\ContentTypes::findAll(null, array('uri' => 1));
        foreach ($types->rows as $type) {
            $typesAry[] = $type->id;
        }

        $type = Table\ContentTypes::findById($tid);

        // Get parents and children, if applicable
        $parents = array(0 => '----');

        // Prevent the object's children or itself from being in the parent drop down
        $content = Table\Content::findAll('id ASC');
        foreach ($content->rows as $c) {
            if (($c->parent_id == 0) && ($c->id != $mid) && (null !== $c->status)) {
                $parents[$c->id] = $c->title;
                $children = $this->children($c->id);
                if (count($children) > 0) {
                    foreach ($children as $cid => $child) {
                        if ($cid != $mid) {
                            $parents[$cid] = $child;
                        }
                    }
                }
            }
        }

        // If type requires a URI
        if ($type->uri == 1) {
            $fields1 = array();

            $uri = array(
                'type'       => 'text',
                'label'      => $this->i18n->__('URI') . (($mid != 0) ? ' <a class="small-link" href="#" onclick="phire.slug(\'content_title\', \'uri\'); return false;">[ ' . $this->i18n->__('Generate URI') . ' ]</a>' : null),
                'attributes' => array(
                    'size'    => 80,
                    'style' => 'display: block; width: 100%;',
                    'onkeyup' => "phire.slug(null, 'uri');"
                )
            );
            $titleAttributes = array(
                'size'    => 80,
                'style'   => 'display: block; width: 100%;'
            );
            if ($mid == 0) {
                $titleAttributes['onkeyup'] = "phire.slug('content_title', 'uri');";
            } else {
                $titleAttributes['onkeyup'] = "phire.updateTitle('#content-title', this);";
            }
        // Else, if type is a file
        } else {
            $this->hasFile = true;
            $label = $this->i18n->__('File') . ' <span style="font-weight: normal; color: #666; padding: 0 0 0 10px; font-size: 0.9em;">[ <strong>' . \Phire\Table\Config::getMaxFileSize() . '</strong> ' . $this->i18n->__('Max Size') . ' ]</span>';
            $required = true;
            if ($mid != 0) {
                $content = Table\Content::findById($mid);
                if (isset($content->id)) {
                    $site = \Phire\Table\Sites::getSite((int)$content->site_id);
                    $fileInfo = \Phire\Model\Media::getFileIcon($content->uri, $site->document_root . $site->base_path . CONTENT_PATH . '/media');
                    $label = '<em>' . $this->i18n->__('Replace?') . '</em><br /><a href="http://' .
                        $site->domain . BASE_PATH . CONTENT_PATH . '/media/' . $content->uri . '" target="_blank"><img id="current-file" style="padding-top: 3px;" src="http://' .
                        $site->domain . BASE_PATH . CONTENT_PATH . $fileInfo['fileIcon'] . '" width="50" /></a><br /><a href="http://' . $site->domain . BASE_PATH . CONTENT_PATH . '/media/' . $content->uri . '" target="_blank">' .
                        $content->uri . '</a><br /><span style="font-size: 0.9em;">(' . $fileInfo['fileSize'] . ')</span>';
                    $required = false;
                }
            }
            $uri = array(
                'type'       => 'file',
                'label'      => $label,
                'required'   => $required,
                'attributes' => array(
                    'size'  => 80,
                    'style' => 'display: block; width: 100%;'
                )
            );
            $titleAttributes = array(
                'size'    => 80,
                'style'   => 'display: block; width: 100%;'
            );
        }

        $fields1['content_title'] = array(
            'type'       => 'text',
            'label'      => $this->i18n->__('Title'),
            'required'   => (!$this->hasFile),
            'attributes' => $titleAttributes
        );

        $fields1['uri'] =  $uri;

        $sess = \Pop\Web\Session::getInstance();
        $siteIds = array(0 => $_SERVER['HTTP_HOST']);

        $sites = \Phire\Table\Sites::findAll();
        foreach ($sites->rows as $site) {
            if (in_array($site->id, $sess->user->site_ids)) {
                $siteIds[$site->id] = $site->domain;
            }
        }

        $fields4 = array(
            'site_id' => array(
                'type'       => 'select',
                'label'      => $this->i18n->__('Site'),
                'value'      => $siteIds,
                'marked'     => 0,
                'attributes' => array('style' => 'width: 200px;')
            )
        );

        // If type requires a URI
        if ($type->uri == 1) {
            $fields4['parent_id'] = array(
                'type'       => 'select',
                'label'      => $this->i18n->__('Parent'),
                'value'      => $parents,
                'attributes' => array(
                    'onchange' => "phire.slug(null, 'uri');",
                    'style'    => 'width: 200px;'
                )
            );
            $fields4['template'] = array(
                'type'       => 'select',
                'label'      => $this->i18n->__('Template'),
                'value'      => $this->getTemplates($cfg),
                'attributes' => array(
                    'style'    => 'width: 200px;'
                )
            );
        }

        // Categories
        $catOrder = array();
        $catsMarked = array();
        if ($mid != 0) {
            $cats = Table\ContentToCategories::findAll(null, array('content_id' => $mid));
            if (isset($cats->rows[0])) {
                foreach ($cats->rows as $cat) {
                    $catsMarked[] = $cat->category_id;
                    $catOrder[$cat->category_id] = $cat->order;
                }
            }
        }

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
            $catsAry[$id] = '<span style="color: #666; border-bottom: dotted 1px #ccc; display: block; float: left; width: ' . (150 - (3 * $depth)) . 'px; font-size: 0.9em; padding-top: 0; padding-bottom: 7px; padding-left: ' . (3 * $depth) . 'px;">' . $cat . '</span> <input style="margin: -4px 0 0 10px; padding: 2px; font-size: 0.9em; width: 25px; text-align: center;" type="text" name="category_order_' . $id . '" value="' . (isset($catOrder[$id]) ? $catOrder[$id] : 0) . '" size="3" />';
        }

        // Add nav include and roles
        if (!$this->hasFile) {
            $fields4['status'] = array(
                'type'   => 'select',
                'label'  => $this->i18n->__('Status'),
                'value'  => array(
                    0 => $this->i18n->__('Unpublished'),
                    1 => $this->i18n->__('Draft'),
                    2 => $this->i18n->__('Published')
                ),
                'marked'     => 0,
                'attributes' => array('style' => 'width: 200px;')
            );

            $navOrder = array();
            $navsMarked = array();
            if ($mid != 0) {
                $navs = Table\ContentToNavigation::findAll(null, array('content_id' => $mid));
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
            if (count($navsAry) > 0) {
                $fields5['navigation_id'] = array(
                    'type'   => 'checkbox',
                    'label'  => $this->i18n->__('Navigation') . ' / ' . $this->i18n->__('Order'),
                    'value'  => $navsAry,
                    'marked' => $navsMarked
                );
            }
            // Add categories
            if (count($catsAry) > 0) {
                $fields5['category_id'] = array(
                    'type'     => 'checkbox',
                    'label'    => $this->i18n->__('Categories'),
                    'value'    => $catsAry
                );
            }
            $fields5['feed'] = array(
                'type'   => 'radio',
                'label'  => $this->i18n->__('Include in Feed'),
                'value'  => array(1 => $this->i18n->__('Yes'), 0 => $this->i18n->__('No')),
                'marked' => 1
            );
            $fields5['force_ssl'] = array(
                'type'   => 'radio',
                'label'  => $this->i18n->__('Force SSL'),
                'value'  => array(1 => $this->i18n->__('Yes'), 0 => $this->i18n->__('No')),
                'marked' => 0
            );
            $rolesAry = array();
            $roles = \Phire\Table\UserRoles::findAll('id ASC');
            foreach ($roles->rows as $role) {
                $rolesAry[$role->id] = $role->name;
            }
            $fields5['roles'] = array(
                'type'   => 'checkbox',
                'label'  => $this->i18n->__('Roles'),
                'value'  => $rolesAry
            );
        } else {
            // Add categories
            if (count($catsAry) > 0) {
                $fields5['category_id'] = array(
                    'type'     => 'checkbox',
                    'label'    => $this->i18n->__('Categories'),
                    'value'    => $catsAry
                );
            }
            $fields5['feed'] = array(
                'type'   => 'radio',
                'label'  => $this->i18n->__('Include in Feed'),
                'value'  => array(1 => $this->i18n->__('Yes'), 0 => $this->i18n->__('No')),
                'marked' => 1
            );
        }

        $fieldGroups = array();
        $dynamicFields = false;

        $model = str_replace('Form', 'Model', get_class($this));
        $newFields = \Phire\Model\Field::getByModel($model, $tid, $mid);
        if ($newFields['dynamic']) {
            $dynamicFields = true;
        }
        if ($newFields['hasFile']) {
            $this->hasFile = true;
        }
        foreach ($newFields as $key => $value) {
            if (is_numeric($key) && is_array($value) && (count($value) > 0)) {
                $fieldGroups[] = $value;
            }
        }

        // Create pub/exp date fields for a URI-based content object
        if ($type->uri) {
            $fields4['publish_month'] = array(
                'type'       => 'select',
                'label'      => $this->i18n->__('Publish') . ' / ' . $this->i18n->__('Start Date'),
                'value'      => Element\Select::MONTHS_SHORT,
                'marked'     => date('m')
            );
            $fields4['publish_day'] = array(
                'type'       => 'select',
                'value'      => Element\Select::DAYS_OF_MONTH,
                'marked'     => date('d')
            );
            $fields4['publish_year'] = array(
                'type'       => 'select',
                'value'      => 'YEAR_' . (date('Y') - 10) . '_' . (date('Y') + 10),
                'marked'     => date('Y')
            );
            $fields4['publish_hour'] = array(
                'type'       => 'select',
                'value'      => Element\Select::HOURS_24,
                'marked'     => date('H')
            );
            $fields4['publish_minute'] = array(
                'type'       => 'select',
                'value'      => Element\Select::MINUTES,
                'marked'     => date('i')
            );
            $fields4['expire_month'] = array(
                'type'       => 'select',
                'label'      => $this->i18n->__('Expiration') . ' / ' . $this->i18n->__('End Date'),
                'value'      => Element\Select::MONTHS_SHORT
            );
            $fields4['expire_day'] = array(
                'type'       => 'select',
                'value'      => Element\Select::DAYS_OF_MONTH
            );
            $fields4['expire_year'] = array(
                'type'       => 'select',
                'value'      => 'YEAR_' . (date('Y') - 10) . '_' . (date('Y') + 10)
            );
            $fields4['expire_hour'] = array(
                'type'       => 'select',
                'value'      => Element\Select::HOURS_24
            );
            $fields4['expire_minute'] = array(
                'type'       => 'select',
                'value'      => Element\Select::MINUTES
            );
        }

        $fields6 = array(
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
                    'onclick' => "return phire.updateForm('#content-form', " . ((($this->hasFile) || ($dynamicFields)) ? 'true' : 'false') . ");",
                    'class'   => 'update-btn'
                )
            )
        );

        $fields5['type_id'] = array(
            'type'  => 'hidden',
            'value' => $tid
        );
        $fields5['id'] = array(
            'type'  => 'hidden',
            'value' => 0
        );
        $fields5['update_value'] = array(
            'type'  => 'hidden',
            'value' => 0
        );
        $fields5['live'] = array(
            'type'  => 'hidden',
            'value' => (isset($_GET['live']) && ($_GET['live'] == 1)) ? 1 : 0
        );

        $allFields = array();
        $allFields[] = array_merge($fields6, $fields4, $fields5);
        $allFields[] = $fields1;

        if (count($fieldGroups) > 0) {
            foreach ($fieldGroups as $fg) {
                $allFields[] = $fg;
            }
        }

        return $allFields;
    }

    /**
     * Method to get templates
     *
     * @param  array $cfg
     * @return array
     */
    protected function getTemplates($cfg = array())
    {
        $theme = \Phire\Table\Extensions::findBy(array('type' => 0, 'active' => 1), null, 1);

        // Get view templates path from config, or fall back to the default
        if (isset($cfg['view'])) {
            if (is_array($cfg['view'])) {
                if (isset($cfg['view']['Content\Controller\IndexController'])) {
                    $view = $cfg['view']['Content\Controller\IndexController'];
                } else if (isset($cfg['view']['*'])) {
                    $view = $cfg['view']['*'];
                } else {
                    $view = realpath(__DIR__ . '/../../../view');
                }
            } else {
                $view = $cfg['view'];
            }
        } else if (isset($theme->id) && file_exists(__DIR__ . '/../../../../../themes/' . $theme->name)) {
            $view = realpath(__DIR__ . '/../../../../../themes/' . $theme->name);
        } else {
            $view = realpath(__DIR__ . '/../../../view');
        }

        $templates = array('0' => '(' . $this->i18n->__('Default') . ')');

        if (isset($theme->id)) {
            $themeName = $theme->name;
            $assets = unserialize($theme->assets);
            foreach ($assets['info'] as $k => $v) {
                if (stripos($k, 'name') !== false) {
                    $themeName = $v;
                }
            }

            foreach ($assets['templates'] as $key => $value) {
                if (!$this->isSystemTemplate($value)) {
                    if (strpos($key, 'template_') !== false) {
                        $id = substr($key, (strpos($key, '_') + 1));
                        $templates[$id] = $value . ' (' . $themeName . ')';
                    } else {
                        $templates[$value] = $value . ' (' . $themeName . ')';
                    }
                }
            }
        }

        $tmpls = Table\Templates::findAll('id ASC');
        $viewDir = new Dir($view, false, false, false);

        foreach ($tmpls->rows as $tmpl) {
            if (null === $tmpl->parent_id) {
                if (!$this->isSystemTemplate($tmpl->name)) {
                    $templates[$tmpl->id] = $tmpl->name;
                }
            }
        }

        foreach ($viewDir->getFiles() as $file) {
            $ext = strtolower(substr($file, strrpos($file, '.')));
            if (($ext == '.phtml') || ($ext == '.php') || ($ext == '.php3')) {
                if (!$this->isSystemTemplate($file)) {
                    $templates[$file] = $file;
                }
            }
        }

        return $templates;
    }

    /**
     * Recursive method to get children of the content object
     *
     * @param  int   $pid
     * @param  array $children
     * @param  int   $depth
     * @return array
     */
    protected function children($pid, $children = array(), $depth = 0)
    {
        $c = Table\Content::findBy(array('parent_id' => $pid));

        if (isset($c->rows[0])) {
            foreach ($c->rows as $child) {
                $children[$child->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', ($depth + 1)) . '&gt; ' . $child->title;
                $c = Table\Content::findBy(array('parent_id' => $child->id));
                if (isset($c->rows[0])) {
                    $d = $depth + 1;
                    $children = $this->children($child->id, $children, $d);
                }
            }
        }

        return $children;
    }

    /**
     * Method to detect if the template is a system template
     *
     * @param  string $tmpl
     * @return boolean
     */
    protected function isSystemTemplate($tmpl)
    {
        $isSystemTmpl = true;

        if ((strtolower($tmpl) != 'search') && (stripos($tmpl, 'search.ph') === false) &&
            (strtolower($tmpl) != 'sidebar') && (stripos($tmpl, 'sidebar.ph') === false) && (stripos($tmpl, 'sidebar-') === false) &&
            (strtolower($tmpl) != 'category') && (stripos($tmpl, 'category.ph') === false) && (stripos($tmpl, 'category-') === false)  &&
            (strtolower($tmpl) != 'date') && (stripos($tmpl, 'date.ph') === false) &&
            (strtolower($tmpl) != 'functions') && (stripos($tmpl, 'functions.ph') === false) &&
            (strtolower($tmpl) != 'error') && (stripos($tmpl, 'error.ph') === false) &&
            (strtolower($tmpl) != 'header') && (stripos($tmpl, 'header.ph') === false) &&
            (strtolower($tmpl) != 'footer') && (stripos($tmpl, 'footer.ph') === false)) {
            $isSystemTmpl = false;
        }

        return $isSystemTmpl;
    }

}

