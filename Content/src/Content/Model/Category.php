<?php
/**
 * @namespace
 */
namespace Content\Model;

use Pop\Data\Type\Html;
use Content\Table;

class Category extends AbstractModel
{

    /**
     * @var   array
     */
    protected $categories = array(0 => '----');

    /**
     * Instantiate the model object.
     *
     * @param  array $data
     * @return self
     */
    public function __construct(array $data = null)
    {
        parent::__construct($data);

        $this->config->open_authoring    = \Phire\Table\Config::findById('open_authoring')->value;
        $this->config->feed_type         = \Phire\Table\Config::findById('feed_type')->value;
        $this->config->feed_limit        = \Phire\Table\Config::findById('feed_limit')->value;
        $this->config->incontent_editing = \Phire\Table\Config::findById('incontent_editing')->value;
    }

    /**
     * Get categories array
     *
     * @return array
     */
    public function getCategoryArray()
    {
        return $this->categories;
    }

    /**
     * Get all categories method
     *
     * @param  string  $sort
     * @param  string  $page
     * @param  string  $ord
     * @return void
     */
    public function getAll($sort = null, $page = null, $ord = null)
    {
        $order = $this->getSortOrder($sort, $page);
        $categories = Table\Categories::findAll($order['field'] . ' ' . (($ord !== null) ? $ord : $order['order']));
        $this->getCategories($this->getChildren($categories->rows, 0));

        if (isset($this->data['acl']) && ($this->data['acl']->isAuth('Content\Controller\Content\CategoriesController', 'remove'))) {
            $removeCheckbox = '<input type="checkbox" name="remove_categories[]" id="remove_categories[{i}]" value="[{id}]" />';
            $removeCheckAll = '<input type="checkbox" id="checkall" name="checkall" value="remove_categories" />';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Remove')
            );
        } else {
            $removeCheckbox = '&nbsp;';
            $removeCheckAll = '&nbsp;';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Remove'),
                'style' => 'display: none;'
            );
        }

        $options = array(
            'form' => array(
                'id'      => 'category-remove-form',
                'action'  => BASE_PATH . APP_URI . '/structure/categories/remove',
                'method'  => 'post',
                'process' => $removeCheckbox,
                'submit'  => $submit
            ),
            'table' => array(
                'headers' => array(
                    'id'       => '<a href="' . BASE_PATH . APP_URI . '/structure/categories?sort=id">#</a>',
                    'edit'     => '<span style="display: block; margin: 0 auto; width: 100%; text-align: center;">' . $this->i18n->__('Edit') . '</span>',
                    'title'    => '<a href="' . BASE_PATH . APP_URI . '/structure/categories?sort=title">' . $this->i18n->__('Title') . '</a>',
                    'process'  => $removeCheckAll
                ),
                'class'       => 'data-table',
                'cellpadding' => 0,
                'cellspacing' => 0,
                'border'      => 0
            ),
            'separator' => '',
            'indent'    => '        '
        );

        $catAry = array();
        $cats = $this->categories;
        unset($cats[0]);

        foreach ($cats as $id => $name) {
            if (isset($this->data['acl']) && ($this->data['acl']->isAuth('Content\Controller\Content\CategoriesController', 'edit'))) {
                $edit = '<a class="edit-link" title="' . $this->i18n->__('Edit') . '" href="' . BASE_PATH . APP_URI . '/structure/categories/edit/' . $id . '">Edit</a>';
            } else {
                $edit = null;
            }
            $cAry = array(
                'id'    => $id,
                'title' => $name
            );
            if (null !== $edit) {
                $cAry['edit'] = $edit;
            }
            $catAry[] = $cAry;
        }

        if (isset($catAry[0])) {
            $table = Html::encode($catAry, $options);
            if (isset($this->data['acl']) && ($this->data['acl']->isAuth('Contact\Controller\Content\CategoriesController', 'edit'))) {
                $tableLines = explode(PHP_EOL, $table);

                // Clean up the table
                foreach ($tableLines as $key => $value) {
                    if (strpos($value, '">&') !== false) {
                        $str = substr($value, (strpos($value, '">&') + 2));
                        $str = substr($str, 0, (strpos($str, ' ') + 1));
                        $value = str_replace($str, '', $value);
                        $tableLines[$key] = str_replace('<td><a', '<td>' . $str . '<a', $value);
                    }
                }
                $table = implode(PHP_EOL, $tableLines);
            }

            $this->data['table'] = $table;
        }
    }

    /**
     * Get category by URI method
     *
     * @param  string  $uri
     * @param  string  $page
     * @return void
     */
    public function getByUri($uri, $page = null)
    {
        $category = Table\Categories::findBy(array('uri' => $uri));
        if (isset($category->id)) {
            $categoryValues = $category->getValues();
            $categoryValues = array_merge($categoryValues, \Phire\Model\FieldValue::getAll($category->id, true));

            // Get content object within the category
            $categoryValues['items'] = array();
            $order   = $this->getSortOrder('order', $page);
            
            $sql = Table\ContentToCategories::getSql();

            $sql->select(array(
                DB_PREFIX . 'content_to_categories.content_id',
                DB_PREFIX . 'content_to_categories.category_id',
                DB_PREFIX . 'content_to_categories.order',
                DB_PREFIX . 'content.status',
                DB_PREFIX . 'content.publish',
                DB_PREFIX . 'content.expire',
            ))->where()->equalTo(DB_PREFIX . 'content_to_categories.category_id', ':category_id');

            $sql->select()->join(DB_PREFIX . 'content', array('content_id', 'id'), 'LEFT JOIN')
                          ->orderBy(DB_PREFIX . 'content_to_categories.order', 'ASC')
                          ->orderBy(DB_PREFIX . 'content.publish', 'DESC');

            $content = Table\Content::execute($sql->render(true), array('category_id' => $category->id));
            $site    = \Phire\Table\Sites::findBy(array('document_root' => $_SERVER['DOCUMENT_ROOT']));
            $siteId  = (isset($site->id)) ? $site->id : '0';
            $this->data['site_id'] = $siteId;
            $notAllowedCount = 0;

            if (isset($content->rows[0])) {
                foreach ($content->rows as $cont) {
                    $c = Table\Content::findById($cont->content_id);
                    if (Content::isAllowed($c)) {
                        $c = $c->getValues();
                        if (isset($c['title'])) {
                            $c['title'] = html_entity_decode($c['title'], ENT_QUOTES, 'UTF-8');
                        }
                        if (substr($c['uri'], 0, 1) != '/') {
                            $c['uri'] = CONTENT_PATH . '/media/' . $c['uri'];
                            $c['isFile'] = true;
                        } else {
                            $c['isFile'] = false;
                        }
                        $c['category_id']    = $category->id;
                        $c['category_title'] = html_entity_decode($category->title, ENT_QUOTES, 'UTF-8');;
                        $c['category_uri']   = $category->uri;

                        $fieldValues = \Phire\Model\FieldValue::getAll($c['id'], true);
                        foreach ($fieldValues as $key => $value) {
                            if (is_array($value)) {
                                foreach ($value as $k => $v) {
                                    $value[$k] = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
                                }
                                $fieldValues[$key] = $value;
                            } else {
                                $fieldValues[$key] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                            }
                        }

                        $c = array_merge($c, $fieldValues);
                        $categoryValues['items'][] = new \ArrayObject($c, \ArrayObject::ARRAY_AS_PROPS);
                    } else {
                        $notAllowedCount++;
                    }
                }
            }

            foreach ($categoryValues['items'] as $key => $item) {
                $categoryValues['items'][$key] = new \ArrayObject($this->filterContent((array)$item), \ArrayObject::ARRAY_AS_PROPS);
            }

            $count = count($categoryValues['items']);

            if ($count > (int)$order['limit']) {
                $categoryValues['items'] = array_slice($categoryValues['items'], $order['offset'], $order['limit']);
                $pg = new \Pop\Paginator\Paginator($categoryValues['items'], $this->config->pagination_limit, $this->config->pagination_range, $count);
                $categoryValues['page_links'] = implode('', $pg->getLinks(((null !== $page) ? $page : 1)));
            } else {
                $categoryValues['page_links'] = null;
            }

            $categoryValues['noitems'] = array();

            if ($count == 0) {
                $categoryValues['noitems'][] = 1;
            }

            $this->data = array_merge($this->data, $categoryValues);
            $this->filterContent();
        }
    }

    /**
     * Get category by ID method
     *
     * @param  int     $id
     * @return void
     */
    public function getById($id)
    {
        $category = Table\Categories::findById($id);

        if (isset($category->id)) {
            $categoryValues = $category->getValues();
            $categoryValues['category_title'] = $categoryValues['title'];
            unset($categoryValues['title']);

            $categoryValues = array_merge($categoryValues, \Phire\Model\FieldValue::getAll($id));

            $this->data = array_merge($this->data, $categoryValues);
        }
    }

    /**
     * Save category
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function save(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $uri = $fields['slug'];

        if ((int)$fields['parent_id'] != 0) {
            $pId = $fields['parent_id'];
            while ($pId != 0) {
                $category = Table\Categories::findById($pId);
                if (isset($category->id)) {
                    $pId = $category->parent_id;
                    $uri = $category->slug . '/' . $uri;
                }
            }
        }

        if (substr($uri, 0, 1) != '/') {
            $uri = '/' . $uri;
        } else if (substr($uri, 0, 2) == '//') {
            $uri = substr($uri, 1);
        }

        $category = new Table\Categories(array(
            'parent_id' => (($fields['parent_id'] != 0) ? $fields['parent_id'] : null),
            'title'     => $fields['category_title'],
            'uri'       => $uri,
            'slug'      => $fields['slug'],
            'order'     => (int)$fields['order'],
            'total'     => (int)$fields['total']
        ));

        $category->save();
        $this->data['id'] = $category->id;

        // Save category navs
        if (isset($fields['navigation_id'])) {
            foreach ($fields['navigation_id'] as $nav) {
                $categoryToNav = new Table\ContentToNavigation(array(
                    'navigation_id' => $nav,
                    'category_id'    => $category->id,
                    'order'         => (int)$_POST['navigation_order_' . $nav]
                ));
                $categoryToNav->save();
            }
        }

        \Phire\Model\FieldValue::save($fields, $category->id);
    }

    /**
     * Update category
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function update(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $uri = $fields['slug'];

        if ((int)$fields['parent_id'] != 0) {
            $pId = $fields['parent_id'];
            while ($pId != 0) {
                $category = Table\Categories::findById($pId);
                if (isset($category->id)) {
                    $pId = $category->parent_id;
                    $uri = $category->slug . '/' . $uri;
                }
            }
        }

        if (substr($uri, 0, 1) != '/') {
            $uri = '/' . $uri;
        } else if (substr($uri, 0, 2) == '//') {
            $uri = substr($uri, 1);
        }

        $category = Table\Categories::findById($fields['id']);
        $category->parent_id = (((int)$fields['parent_id'] != 0) ? $fields['parent_id'] : null);
        $category->title     = $fields['category_title'];
        $category->uri       = $uri;
        $category->slug      = $fields['slug'];
        $category->order     = (int)$fields['order'];
        $category->total     = (int)$fields['total'];
        $category->update();

        $this->data['id'] = $category->id;

        // Update category navs
        $categoryToNavigation = Table\ContentToNavigation::findBy(array('category_id' => $category->id));
        foreach ($categoryToNavigation->rows as $nav) {
            $categoryToNav = Table\ContentToNavigation::findById(array($nav->navigation_id, null, $category->id));
            if (isset($categoryToNav->category_id)) {
                $categoryToNav->delete();
            }
        }

        if (isset($_POST['navigation_id'])) {
            foreach ($_POST['navigation_id'] as $nav) {
                $categoryToNav = new Table\ContentToNavigation(array(
                    'category_id'    => $category->id,
                    'navigation_id' => $nav,
                    'order'         => (int)$_POST['navigation_order_' . $nav]
                ));
                $categoryToNav->save();
            }
        }

        \Phire\Model\FieldValue::update($fields, $category->id);
    }

    /**
     * Remove category
     *
     * @param  array   $post
     * @return void
     */
    public function remove(array $post)
    {
        if (isset($post['remove_categories'])) {
            foreach ($post['remove_categories'] as $id) {
                $category = Table\Categories::findById($id);
                if (isset($category->id)) {
                    // Delete category navs
                    $categoryToNavigation = Table\ContentToNavigation::findBy(array('category_id' => $category->id));
                    foreach ($categoryToNavigation->rows as $nav) {
                        $categoryToNav = Table\ContentToNavigation::findById(array($nav->navigation_id, null, $category->id));
                        if (isset($categoryToNav->category_id)) {
                            $categoryToNav->delete();
                        }
                    }

                    $category->delete();
                }

                \Phire\Model\FieldValue::remove($id);
            }
        }
    }

    /**
     * Method to get category breadcrumb
     *
     * @return string
     */
    public function getBreadcrumb()
    {
        $breadcrumb = $this->title;
        $pId = $this->parent_id;
        $basePath = \Phire\Table\Sites::getBasePath();
        $sep = $this->config->separator;

        while ($pId != 0) {
            $category = Table\Categories::findById($pId);
            if (isset($category->id)) {
                $breadcrumb = '<a href="' . $basePath . '/category' . $category->uri . '">' . $category->title . '</a> ' .
                    $sep . ' ' . $breadcrumb;
                $pId = $category->parent_id;
            }
        }

        return $breadcrumb;
    }

    /**
     * Recursive function to get a formatted array of nested categories id => category
     *
     * @param  array $categories
     * @param  int   $depth
     * @return array
     */
    protected function getCategories($categories, $depth = 0) {
        foreach ($categories as $category) {
            $this->categories[$category['id']] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth) . '&gt; ' . $category['title'];
            if (count($category['children']) > 0) {
                $this->getCategories($category['children'], ($depth + 1));
            }
        }
    }

    /**
     * Recursive method to get category children
     *
     * @param array   $category
     * @param int     $pid
     * @param boolean $count
     * @return  array
     */
    protected function getChildren($category, $pid, $count = false)
    {
        $children = array();
        foreach ($category as $c) {
            if ($c->parent_id == $pid) {
                $p = (array)$c;
                $p['uri'] = BASE_PATH . '/category'  . $c->uri;
                $p['href'] = $p['uri'];
                $p['name'] = $c->title;

                if (($count) && ($c->total)) {
                    $p['name'] .= ' (' . ((isset($c->num)) ? (int)$c->num : 0). ')';
                }

                $p['children'] = $this->getChildren($category, $c->id, $count);
                $children[] = $p;
            }
        }

        return $children;
    }

}

