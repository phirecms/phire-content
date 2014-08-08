<?php
/**
 * @namespace
 */
namespace Content\Model;

use Pop\Filter\String;
use Pop\Nav\Nav;
use Content\Table;

class Navigation extends AbstractModel
{

    protected $trackNav = array();

    protected $trackNavNotAllowed = array();

    /**
     * Get all navigation method
     *
     * @param  string  $sort
     * @param  string  $page
     * @return array
     */
    public function getAll($sort = null, $page = null)
    {
        $order = $this->getSortOrder($sort, $page);
        $navigation = Table\Navigation::findAll($order['field'] . ' ' . $order['order']);

        $navAry = array();

        foreach ($navigation->rows as $nav) {

            $sql = Table\ContentToNavigation::getSql();
            $sql->select(array(
                'navigation_id',
                'content_id',
                'category_id',
                DB_PREFIX . 'content_to_navigation.order',
                'cont_id'        => DB_PREFIX . 'content.id',
                'cont_site_id'   => DB_PREFIX . 'content.site_id',
                'cont_parent_id' => DB_PREFIX . 'content.parent_id',
                'cont_title'     => DB_PREFIX . 'content.title',
                'cont_uri'       => DB_PREFIX . 'content.uri',
                'cat_id'         => DB_PREFIX . 'categories.id',
                'cat_parent_id'  => DB_PREFIX . 'categories.parent_id',
                'cat_title'      => DB_PREFIX . 'categories.title',
                'cat_uri'        => DB_PREFIX . 'categories.uri'
            ))->where()->equalTo('navigation_id', $nav->id);
            $sql->select()->join(DB_PREFIX . 'content', array('content_id', 'id'), 'LEFT JOIN');
            $sql->select()->join(DB_PREFIX . 'categories', array('category_id', 'id'), 'LEFT JOIN');
            $sql->select()->orderBy(DB_PREFIX . 'content_to_navigation.order', 'ASC');

            $navTree = Table\ContentToNavigation::execute($sql->render(true));

            $navChildren = array();
            $contParents = array('0' => '----');
            $catParents = array('0' => '----');
            if (isset($navTree->rows[0])) {
                foreach ($navTree->rows as $c) {
                    if (null !== $c->cont_id) {
                        $contParents[$c->cont_id] = $c->cont_title;
                    } else if (null !== $c->cat_id) {
                        $catParents[$c->cat_id] = $c->cat_title;
                    }
                }
                $this->trackNav = array();
                $navChildren = $this->getTreeChildren($navTree->rows, null, true);
                $newChildren = array();
                foreach ($navTree->rows as $c) {
                    if ((null !== $c->cont_id) && !in_array($c->cont_id, $this->trackNav)) {
                        $newChildren = array_merge($newChildren, $this->getTreeChildren($navTree->rows, $c->cont_parent_id, true));
                    } else if ((null !== $c->cat_id) && !in_array($c->cat_id, $this->trackNav)) {
                        $newChildren = array_merge($newChildren, $this->getTreeChildren($navTree->rows, $c->cat_parent_id, true));
                    }
                }

                $navChildren = array_merge($newChildren, $navChildren);
            }

            $navAry[] = array(
                'nav'         => $nav,
                'contParents' => $contParents,
                'catParents'  => $catParents,
                'children'    => $this->getNavChildren($navChildren, array())
            );
        }

        return $navAry;
    }

    /**
     * Method to get all content navigation
     *
     * @return array
     */
    public function getContentNav()
    {
        // Get main navs
        $navigations = Table\Navigation::findAll();
        $navs = array();

        foreach ($navigations->rows as $nav) {

            $sql = Table\ContentToNavigation::getSql();
            $sql->select(array(
                'navigation_id',
                'content_id',
                'category_id',
                DB_PREFIX . 'content_to_navigation.order',
                'cont_id'        => DB_PREFIX . 'content.id',
                'cont_parent_id' => DB_PREFIX . 'content.parent_id',
                'cont_title'     => DB_PREFIX . 'content.title',
                'cont_uri'       => DB_PREFIX . 'content.uri',
                'cat_id'         => DB_PREFIX . 'categories.id',
                'cat_parent_id'  => DB_PREFIX . 'categories.parent_id',
                'cat_title'      => DB_PREFIX . 'categories.title',
                'cat_uri'        => DB_PREFIX . 'categories.uri',
                DB_PREFIX . 'content.status',
                DB_PREFIX . 'content.roles',
                DB_PREFIX . 'content.site_id',
                DB_PREFIX . 'content.type_id',
                DB_PREFIX . 'content.template',
                DB_PREFIX . 'content.feed',
                DB_PREFIX . 'content.force_ssl',
                DB_PREFIX . 'content.created',
                DB_PREFIX . 'content.updated',
                DB_PREFIX . 'content.publish',
                DB_PREFIX . 'content.expire',
                DB_PREFIX . 'content.created_by',
                DB_PREFIX . 'content.updated_by',
                'type_uri'       => DB_PREFIX . 'content_types.uri',
            ))->where()->equalTo('navigation_id', $nav->id);
            $sql->select()->join(DB_PREFIX . 'content', array('content_id', 'id'), 'LEFT JOIN');
            $sql->select()->join(DB_PREFIX . 'categories', array('category_id', 'id'), 'LEFT JOIN');
            $sql->select()->join(DB_PREFIX . 'content_types', array(DB_PREFIX . 'content.type_id', 'id'), 'LEFT JOIN');
            $sql->select()->orderBy(DB_PREFIX . 'content_to_navigation.order', 'ASC');
            $allContent = Table\Content::execute($sql->render(true));

            if (isset($allContent->rows[0])) {
                $top = array(
                    'node' => (null !== $nav->top_node) ? $nav->top_node : 'ul',
                    'id'   => (null !== $nav->top_id) ? $nav->top_id : String::slug($nav->navigation)
                );
                $parent = array();
                $child  = array();

                if (null !== $nav->top_class) {
                    $top['class'] = $nav->top_class;
                }
                if (null !== $nav->top_attributes) {
                    $attribs = array();
                    $attsAry = explode(' ', $nav->top_attributes);
                    foreach ($attsAry as $att) {
                        $a = explode('=', $att);
                        if (isset($a[0]) && isset($a[1])) {
                            $attribs[trim($a[0])] = str_replace('"', '', trim($a[1]));
                        }
                    }
                    $top['attributes'] = $attribs;
                }

                if (null !== $nav->parent_node) {
                    $parent['node'] = $nav->parent_node;
                }
                if (null !== $nav->parent_id) {
                    $parent['id'] = $nav->parent_id;
                }
                if (null !== $nav->parent_class) {
                    $parent['class'] = $nav->parent_class;
                }
                if (null !== $nav->parent_attributes) {
                    $attribs = array();
                    $attsAry = explode(' ', $nav->parent_attributes);
                    foreach ($attsAry as $att) {
                        $a = explode('=', $att);
                        if (isset($a[0]) && isset($a[1])) {
                            $attribs[trim($a[0])] = str_replace('"', '', trim($a[1]));
                        }
                    }
                    $parent['attributes'] = $attribs;
                }

                if (null !== $nav->child_node) {
                    $child['node'] = $nav->child_node;
                }
                if (null !== $nav->child_id) {
                    $child['id'] = $nav->child_id;
                }
                if (null !== $nav->child_class) {
                    $child['class'] = $nav->child_class;
                }
                if (null !== $nav->child_attributes) {
                    $attribs = array();
                    $attsAry = explode(' ', $nav->child_attributes);
                    foreach ($attsAry as $att) {
                        $a = explode('=', $att);
                        if (isset($a[0]) && isset($a[1])) {
                            $attribs[trim($a[0])] = str_replace('"', '', trim($a[1]));
                        }
                    }
                    $child['attributes'] = $attribs;
                }

                $on  = (null !== $nav->on_class) ? $nav->on_class : null;
                $off = (null !== $nav->off_class) ? $nav->off_class : null;

                $navConfig = array(
                    'top'    => $top,
                    'parent' => $parent,
                    'child'  => $child,
                    'on'     => $on,
                    'off'    => $off
                );

                if (isset($allContent->rows[0])) {
                    $basePath = \Phire\Table\Sites::getBasePath();

                    $this->trackNav = array();
                    $navChildren = $this->getTreeChildren($allContent->rows, 0, $basePath);
                    $newChildren = array();
                    foreach ($allContent->rows as $c) {
                        if ((null !== $c->cont_id) && !in_array($c->cont_id, $this->trackNav)) {
                            $newChildren = array_merge($newChildren, $this->getTreeChildren($allContent->rows, $c->cont_parent_id, false, $basePath));
                        } else if ((null !== $c->cat_id) && !in_array($c->cat_id, $this->trackNav)) {
                            $newChildren = array_merge($newChildren, $this->getTreeChildren($allContent->rows, $c->cat_parent_id, false, $basePath));
                        }
                    }

                    $navChildren = array_merge($newChildren, $navChildren);

                    if (count($navChildren) > 0) {
                        $navName = str_replace('-', '_', String::slug($nav->navigation));
                        $indent = (null !== $nav->spaces) ? str_repeat(' ', $nav->spaces) : '    ';
                        $newNav = new Nav($navChildren, $navConfig);
                        $newNav->nav()->setIndent($indent);
                        $newNav->returnFalse(true);
                        $newNav->rebuild();
                        $navs[$navName] = $newNav;
                    }
                }
            }
        }

        return $navs;
    }

    /**
     * Method to get category navigation
     *
     * @param  int $id
     * @return mixed
     */
    public function getCategoryNav($id = null)
    {
        $nav = null;
        $categories = Table\Categories::getCategoriesWithCount();
        if (isset($categories->rows[0])) {
            // Get category nav
            $catConfig = array(
                'top' => array(
                    'id'    => 'category-nav'
                ),
                'parent' => array(
                    'class' => 'category-nav-level'
                ),
                'on' => 'category-nav-on'
            );


            if (isset($categories->rows[0])) {
                $pid = 0;
                $cid = null;

                if (null !== $id) {
                    $cat = Table\Categories::findById($id);
                    if (isset($cat->id)) {
                        $pid = (int)$cat->parent_id;
                        $cid = $cat->id;
                    }
                }
                $navChildren = $this->getCategoryChildren($categories->rows, $pid, $cid, \Phire\Table\Sites::getBasePath());
                if (count($navChildren) > 0) {
                    $nav = new Nav($navChildren, $catConfig);
                    $nav->returnFalse(true);
                    $nav->rebuild();
                    $nav->nav()->setIndent('    ');
                }
            }
        }

        return $nav;
    }

    /**
     * Get navigation by ID method
     *
     * @param  int     $id
     * @return void
     */
    public function getById($id)
    {
        $navigation = Table\Navigation::findById($id);

        if (isset($navigation->id)) {
            $navigationValues = $navigation->getValues();
            $navigationValues = array_merge($navigationValues, \Phire\Model\FieldValue::getAll($id));
            $this->data = array_merge($this->data, $navigationValues);
        }
    }

    /**
     * Save navigation
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function save(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $navigation = new Table\Navigation(array(
            'navigation'        => $fields['navigation'],
            'spaces'            => (($fields['spaces'] != '') ? (int)$fields['spaces'] : null),
            'top_node'          => (($fields['top_node'] != '') ? $fields['top_node'] : null),
            'top_id'            => (($fields['top_id'] != '') ? $fields['top_id'] : null),
            'top_class'         => (($fields['top_class'] != '') ? $fields['top_class'] : null),
            'top_attributes'    => (($fields['top_attributes'] != '') ? $fields['top_attributes'] : null),
            'parent_node'       => (($fields['parent_node'] != '') ? $fields['parent_node'] : null),
            'parent_id'         => (($fields['parent_id'] != '') ? $fields['parent_id'] : null),
            'parent_class'      => (($fields['parent_class'] != '') ? $fields['parent_class'] : null),
            'parent_attributes' => (($fields['parent_attributes'] != '') ? $fields['parent_attributes'] : null),
            'child_node'        => (($fields['child_node'] != '') ? $fields['child_node'] : null),
            'child_id'          => (($fields['child_id'] != '') ? $fields['child_id'] : null),
            'child_class'       => (($fields['child_class'] != '') ? $fields['child_class'] : null),
            'child_attributes'  => (($fields['child_attributes'] != '') ? $fields['child_attributes'] : null),
            'on_class'          => (($fields['on_class'] != '') ? $fields['on_class'] : null),
            'off_class'         => (($fields['off_class'] != '') ? $fields['off_class'] : null)
        ));

        $navigation->save();
        $this->data['id'] = $navigation->id;

        \Phire\Model\FieldValue::save($fields, $navigation->id);
    }

    /**
     * Update navigation
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function update(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();
        $navigation = Table\Navigation::findById($fields['id']);
        $navigation->navigation  = $fields['navigation'];
        $navigation->spaces            = (($fields['spaces'] != '') ? (int)$fields['spaces'] : null);
        $navigation->top_node          = (($fields['top_node'] != '') ? $fields['top_node'] : null);
        $navigation->top_id            = (($fields['top_id'] != '') ? $fields['top_id'] : null);
        $navigation->top_class         = (($fields['top_class'] != '') ? $fields['top_class'] : null);
        $navigation->top_attributes    = (($fields['top_attributes'] != '') ? $fields['top_attributes'] : null);
        $navigation->parent_node       = (($fields['parent_node'] != '') ? $fields['parent_node'] : null);
        $navigation->parent_id         = (($fields['parent_id'] != '') ? $fields['parent_id'] : null);
        $navigation->parent_class      = (($fields['parent_class'] != '') ? $fields['parent_class'] : null);
        $navigation->parent_attributes = (($fields['parent_attributes'] != '') ? $fields['parent_attributes'] : null);
        $navigation->child_node        = (($fields['child_node'] != '') ? $fields['child_node'] : null);
        $navigation->child_id          = (($fields['child_id'] != '') ? $fields['child_id'] : null);
        $navigation->child_class       = (($fields['child_class'] != '') ? $fields['child_class'] : null);
        $navigation->child_attributes  = (($fields['child_attributes'] != '') ? $fields['child_attributes'] : null);
        $navigation->on_class          = (($fields['on_class'] != '') ? $fields['on_class'] : null);
        $navigation->off_class         = (($fields['off_class'] != '') ? $fields['off_class'] : null);
        $navigation->update();

        $this->data['id'] = $navigation->id;

        \Phire\Model\FieldValue::update($fields, $navigation->id);
    }

    /**
     * Process navigation
     *
     * @param  array   $post
     * @param  int     $id
     * @return void
     */
    public function process(array $post, $id)
    {
        foreach ($post as $key => $value) {
            if (strpos($key, 'navigation_order_') !== false) {
                $key = str_replace('navigation_order_', '', $key);
                $ids = explode('_', $key);
                $navId = $ids[1];
                $cId = $ids[2];
                $idsAry = ($ids[0] == 'cont') ? array($navId, $cId, null) : array($navId, null, $cId);
                $c2Nav = Table\ContentToNavigation::findById($idsAry);
                if (isset($c2Nav->navigation_id)) {
                    if ($value != '') {
                        $c2Nav->order = (int)$value;
                        $c2Nav->update();
                    } else {
                        $c2Nav->delete();
                    }
                }
            } else if (strpos($key, 'parent_id_') !== false) {
                $ids = explode('_', $key);
                $c = ($ids[0] == 'cont') ? Table\Content::findById($ids[3]) : Table\Categories::findById($ids[3]);
                if (isset($c->id)) {
                    $pId = ((int)$value == 0) ? null : (int)$value;
                    $c->parent_id = $pId;
                    $c->update();
                }
            }
        }

        if (isset($post['rm_nav'])) {
            $navigation = Table\Navigation::findById($id);
            if (isset($navigation->id)) {
                $navigation->delete();
            }
            \Phire\Model\FieldValue::remove($id);
        }
    }

    /**
     * Recursive method to get nav tree children
     *
     * @param  array   $content
     * @param  int     $pid
     * @param  boolean $override
     * @param  string  $basePath
     * @return array
     */
    protected function getTreeChildren($content, $pid, $override = false, $basePath = null)
    {
        if (null === $basePath) {
            $basePath = BASE_PATH;
        }

        $children = array();
        foreach ($content as $c) {
            if (null !== $c->cont_id) {
                if ($c->cont_parent_id === $pid) {
                    if (!in_array($c->cont_id, $this->trackNav)) {
                        $this->trackNav[] = $c->cont_id;
                        $p = (array)$c;
                        $p['uri']  = (substr($c->cont_uri, 0, 4) != 'http') ? $basePath . $c->cont_uri : $c->cont_uri;
                        $p['href'] = $p['uri'];
                        $p['name'] = $c->cont_title;

                        if (substr($p['href'], 0, 4) == 'http') {
                            $p['attributes'] = array(
                                'target' => '_blank'
                            );
                        }

                        $cont = $c;
                        $cont->id        = $c->cont_id;
                        $cont->parent_id = $c->cont_parent_id;
                        $cont->title     = $c->cont_title;
                        $cont->uri       = $c->cont_uri;

                        if (($override) || (\Content\Model\Content::isAllowed($cont) && (!in_array($c->cont_parent_id, $this->trackNavNotAllowed)))) {
                            $p['children'] = $this->getTreeChildren($content, $c->cont_id, $override, $basePath);
                            $children[] = $p;
                        } else {
                            $this->trackNavNotAllowed[] = $cont->cont_id;
                        }
                    }
                }
            } else if (null !== $c->cat_id) {
                if ($c->cat_parent_id === $pid) {
                    if (!in_array($c->cat_id, $this->trackNav)) {
                        $this->trackNav[] = $c->cat_id;
                        $p = (array)$c;
                        $p['uri'] = $basePath . '/category' . $c->cat_uri;
                        $p['href'] = $p['uri'];
                        $p['name'] = $c->cat_title;

                        $p['children'] = $this->getTreeChildren($content, $c->cat_id, $override, $basePath);
                        $children[] = $p;
                    }
                }
            }
        }

        return $children;
    }

    /**
     * Recursive method to get category children
     *
     * @param array   $category
     * @param int     $pid
     * @param int     $cid
     * @param string  $basePath
     * @return  array
     */
    protected function getCategoryChildren($category, $pid, $cid = null, $basePath = null)
    {
        if (null === $basePath) {
            $basePath = BASE_PATH;
        }

        $children = array();
        foreach ($category as $c) {
            if ($c->parent_id == $pid) {
                if ((null !== $cid) && ($cid == $c->id)) {
                    $p = (array)$c;
                    $p['uri'] = $basePath . '/category'  . $c->uri;
                    $p['href'] = $p['uri'];
                    $p['name'] = $c->title;

                    if ($c->total) {
                        $p['name'] .= ' (' . ((isset($c->num)) ? (int)$c->num : 0). ')';
                    }

                    $p['children'] = $this->getCategoryChildren($category, $c->id, null, $basePath);
                    $children[] = $p;
                } else if (null === $cid) {
                    $p = (array)$c;
                    $p['uri'] = $basePath . '/category'  . $c->uri;
                    $p['href'] = $p['uri'];
                    $p['name'] = $c->title;

                    if ($c->total) {
                        $p['name'] .= ' (' . ((isset($c->num)) ? (int)$c->num : 0). ')';
                    }

                    $p['children'] = $this->getCategoryChildren($category, $c->id, null, $basePath);
                    $children[] = $p;
                }
            }
        }

        return $children;
    }

    /**
     * Recursive method to get category children
     *
     * @param array $children
     * @param array $set
     * @param int   $depth
     * @return array
     */
    protected function getNavChildren($children, $set, $depth = 0) {
        foreach ($children as $nav) {
            if (null !== $nav['cont_id']) {
                $set[] = array(
                    'title'         => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth) . '&gt; ' . $nav['cont_title'],
                    'content_id'    => $nav['cont_id'],
                    'parent_id'     => $nav['cont_parent_id'],
                    'navigation_id' => $nav['navigation_id'],
                    'order'         => $nav['order'],
                    'children'      => $this->children($nav['cont_id']),
                    'isCategory'    => false
                );
                if (count($nav['children']) > 0) {
                    $set = $this->getNavChildren($nav['children'], $set, ($depth + 1));
                }
            } else if (null !== $nav['cat_id']) {
                $set[] = array(
                    'title'         => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth) . '&gt; ' . $nav['cat_title'],
                    'content_id'    => $nav['cat_id'],
                    'parent_id'     => $nav['cat_parent_id'],
                    'navigation_id' => $nav['navigation_id'],
                    'order'         => $nav['order'],
                    'children'      => $this->children($nav['cat_id']),
                    'isCategory'    => true
                );
                if (count($nav['children']) > 0) {
                    $set = $this->getNavChildren($nav['children'], $set, ($depth + 1));
                }
            }
        }

        return $set;
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
                $children[] = $child->id;
                $c = Table\Content::findBy(array('parent_id' => $child->id));
                if (isset($c->rows[0])) {
                    $d = $depth + 1;
                    $children = $this->children($child->id, $children, $d);
                }
            }
        }

        return $children;
    }

}

