<?php
/**
 * @namespace
 */
namespace Content\Model;

use Content\Table;

class Phire extends \Phire\Model\Phire
{

    /**
     * Get content object
     *
     * @param  mixed   $id
     * @return \ArrayObject
     */
    public function getContent($id)
    {
        $contentValues = array();
        $content = (is_numeric($id)) ? Table\Content::findById($id) : Table\Content::findBy(array('uri' => $id));

        if (isset($content->id)) {
            $contentValues = $content->getValues();
            $contentValues = $this->filterContent(array_merge($contentValues, \Phire\Model\FieldValue::getAll($content->id, true)));
        }

        return new \ArrayObject($contentValues, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get category object
     *
     * @param  mixed   $id
     * @return mixed
     */
    public function getCategory($id)
    {
        $categoryValues = array();
        $category = (is_numeric($id)) ? Table\Categories::findById($id) : Table\Categories::findBy(array('uri' => $id));

        if (isset($category->id)) {
            $categoryValues = $category->getValues();
            $categoryValues = $this->filterContent(array_merge($categoryValues, \Phire\Model\FieldValue::getAll($category->id, true)));
        }

        return new \ArrayObject($categoryValues, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get template object
     *
     * @param  mixed   $id
     * @return mixed
     */
    public function getTemplate($id)
    {
        $templateValues = array();
        $template = (is_numeric($id)) ? Table\Templates::findById($id) : Table\Templates::findBy(array('name' => $id));

        if (isset($template->id)) {
            $templateValues = $template->getValues();
            $templateValues = array_merge($templateValues, \Phire\Model\FieldValue::getAll($template->id, true));
            $templateValues['template'] = Template::parse($templateValues['template'], $template->id);
        }

        return new \ArrayObject($templateValues, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get a content navigation object
     *
     * @param  mixed $name
     * @return mixed
     */
    public function getNavigation($name)
    {
        $nav = new Navigation();
        $navAry = $nav->getContentNav();

        return (isset($navAry[$name])) ? $navAry[$name] : null;
    }

    /**
     * Get a category navigation object
     *
     * @param  mixed $name
     * @return mixed
     */
    public function getCategoryNavigation($name = null)
    {
        $nav = new Navigation();
        return $nav->getCategoryNav($name);
    }

    /**
     * Get a category navigation tree
     *
     * @param  mixed $name
     * @return mixed
     */
    public function getCategoryTree($name = null)
    {
        $catTree = array();

        $cat = new Category();
        $cat->getAll('order', null, 'ASC');
        $catAry = $cat->getCategoryArray();
        unset($catAry[0]);

        foreach ($catAry as $id => $c) {
            $c = str_replace('&gt; ', '', $c);
            $depth = substr_count($c, '&nbsp;') / 5;
            $c = str_replace('&nbsp;', '', $c);
            $cU = Table\Categories::findById($id);
            $catTree[$id] = new \ArrayObject(array('category' => $c, 'depth' => $depth, 'uri' => $cU->uri), \ArrayObject::ARRAY_AS_PROPS);
        }

        if (null !== $name) {
            $category = (is_numeric($name)) ? Table\Categories::findById($name) : Table\Categories::findBy(array('title' => $name));
            if (isset($category->id)) {
                $subCatTree = array();
                $inBranch = false;
                $branchDepth = 0;
                foreach ($catTree as $id => $c) {
                    if ($id == $category->id) {
                        $inBranch = true;
                        $branchDepth = $c['depth'];
                        $subCatTree[$id] = $c;
                    } else if (($inBranch) && ($c['depth'] > $branchDepth)) {
                        $subCatTree[$id] = $c;
                    } else if ($c['depth'] <= $branchDepth) {
                        $inBranch = false;
                    }
                }
                $catTree = $subCatTree;
            }
        }

        return $catTree;
    }

    /**
     * Get content by date method
     *
     * @param  string  $from
     * @param  string  $to
     * @param  int     $limit
     * @return array
     */
    public function getContentByDate($from = null, $to = null, $limit = 5)
    {
        $site = \Phire\Table\Sites::getSite();

        // If from is just a year
        if ((null !== $to) && is_numeric($from) && (strlen($from) == 4)) {
            $from .= '-12-31 23:59:59';
        }

        $from = (null === $from) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($from));

        if (null !== $to) {
            if (is_numeric($to) && (strlen($to) == 4)) {
                $to .= '-01-01 00:00:00';
            }
            $to = date('Y-m-d H:i:s', strtotime($to));
        }

        $sql = Table\Content::getSql();
        $sql->select()
            ->where()->equalTo('site_id', ':site_id')
                     ->equalTo('status', ':status')
                     ->lessThanOrEqualTo('publish', ':publish1');

        $params = array('site_id' => $site->id, 'status' => 2, 'publish' => array($from));

        if (null !== $to) {
            $sql->select()
                ->where()->greaterThanOrEqualTo('publish', ':publish2');
            $params['publish'][] = $to;
        }

        if ((int)$limit > 0) {
            $sql->select()->limit((int)$limit);
        }

        $sql->select()->orderBy('publish', 'DESC');

        $content = Table\Content::execute($sql->render(true), $params);
        $results = $content->rows;

        foreach ($results as $key => $result) {
            if (\Content\Model\Content::isAllowed($result)) {
                $fv = \Phire\Model\FieldValue::getAll($result->id, true);
                if (count($fv) > 0) {
                    foreach ($fv as $k => $v) {
                        $results[$key]->{$k} = $v;
                    }
                }
            } else {
                unset($results[$key]);
            }
        }

        return $results;
    }

    /**
     * Get content by category method
     *
     * @param  mixed   $category
     * @param  string  $orderBy
     * @param  int     $limit
     * @return array
     */
    public function getContentByCategory($category, $orderBy = 'order ASC', $limit = null)
    {
        $contentAry = array();

        if (!is_array($category)) {
            $category = array($category);
        }

        foreach ($category as $cat) {
            if (!is_numeric($cat)) {
                $c = Table\Categories::findBy(array('title' => $cat));
            } else {
                $c = Table\Categories::findById($cat);
            }

            if (isset($c->id)) {
                $sql = Table\Content::getSql();
                $sql->select(array(
                    0          => DB_PREFIX . 'content.id',
                    1          => DB_PREFIX . 'content.site_id',
                    2          => DB_PREFIX . 'content.type_id',
                    3          => DB_PREFIX . 'content.parent_id',
                    4          => DB_PREFIX . 'content.template',
                    5          => DB_PREFIX . 'content.title',
                    'uri'      => DB_PREFIX . 'content.uri',
                    6          => DB_PREFIX . 'content.slug',
                    7          => DB_PREFIX . 'content.feed',
                    8          => DB_PREFIX . 'content.force_ssl',
                    9          => DB_PREFIX . 'content.status',
                    10         => DB_PREFIX . 'content.roles',
                    11         => DB_PREFIX . 'content.created',
                    12         => DB_PREFIX . 'content.updated',
                    13         => DB_PREFIX . 'content.publish',
                    14         => DB_PREFIX . 'content.expire',
                    15         => DB_PREFIX . 'content.created_by',
                    16         => DB_PREFIX . 'content.updated_by',
                    'type_uri' => DB_PREFIX . 'content_types.uri',
                    'order'    => DB_PREFIX . 'content_to_categories.order',
                ));

                $sql->select()->join(DB_PREFIX . 'content_types', array('type_id', 'id'), 'LEFT JOIN');
                $sql->select()->join(DB_PREFIX . 'content_to_categories', array('id', 'content_id'), 'LEFT JOIN');
                $sql->select()->where()->equalTo(DB_PREFIX . 'content_to_categories.category_id', ':category_id');

                $order = explode(' ', $orderBy);
                $sql->select()->orderBy($order[0], $order[1]);
                if (null !== $limit) {
                    $sql->select()->limit((int)$limit);
                }

                $content = Table\Content::execute($sql->render(true), array('category_id' => $c->id));

                if (isset($content->rows[0])) {
                    foreach ($content->rows as $cont) {
                        if (\Content\Model\Content::isAllowed($cont)) {
                            $contentValues = (array)$cont;
                            $contentValues = $this->filterContent(array_merge($contentValues, \Phire\Model\FieldValue::getAll($cont->id, true)));
                            $contentAry[$contentValues['id']] = new \ArrayObject($contentValues, \ArrayObject::ARRAY_AS_PROPS);
                        }
                    }
                }
            }
        }

        return $contentAry;
    }

    /**
     * Get content by categories method
     *
     * @param  array $cids
     * @return array
     */
    public function getContentByCategories(array $cids)
    {
        $categories = array();
        $content    = array();

        foreach ($cids as $key => $value) {
            $cids[$key] = (int)$value;
            $cat = Table\Categories::findById($cids[$key]);
            $catValues = $this->filterContent(array_merge($cat->getValues(), \Phire\Model\FieldValue::getAll($cat->id, true)));
            $categories[$cat->id] = new \ArrayObject($catValues, \ArrayObject::ARRAY_AS_PROPS);
        }

        $sql = Table\ContentToCategories::getSql();
        $s = 'SELECT ' . $sql->quoteId('content_id') . ', ' . $sql->quoteId('order') .
             ' FROM ' . $sql->quoteId(DB_PREFIX . 'content_to_categories') . ' WHERE ' . $sql->quoteId('category_id') .
             ' IN (' . implode(', ', $cids) . ') GROUP BY ' . $sql->quoteId('content_id') . ' HAVING COUNT(*) = ' . count($cids) .
             ' ORDER BY ' . $sql->quoteId('order') . ' ASC';

        $contentIds = Table\ContentToCategories::execute($s);
        foreach ($contentIds->rows as $contId) {
            $cont = Table\Content::findById($contId->content_id);
            if (isset($cont->id)) {
                if ((null === $cont->status) || (($cont->status == 2) && ($cont->publish <= date('Y-m-d H:i:s')))) {
                    $contToCat = Table\ContentToCategories::findAll(null, array('content_id' => $cont->id));
                    $catIds =  array();
                    foreach ($contToCat->rows as $c2c) {
                        $catIds[] = $c2c->category_id;
                    }
                    $contValues = $this->filterContent(array_merge($cont->getValues(), \Phire\Model\FieldValue::getAll($cont->id, true)));
                    $contValues['category_ids'] = $catIds;
                    $contValues['order'] = $contId->order;
                    $content[$cont->id] = new \ArrayObject($contValues, \ArrayObject::ARRAY_AS_PROPS);
                }
            }
        }

        return array(
            'categories' => $categories,
            'content'    => $content
        );
    }

    /**
     * Get child categories
     *
     * @param  mixed   $cat
     * @param  int     $limit
     * @return array
     */
    public function getChildCategories($cat, $limit = null)
    {
        if (!is_numeric($cat)) {
            $c = Table\Categories::findBy(array('title' => $cat));
        } else {
            $c = Table\Categories::findById($cat);
        }

        $categoryAry = array();
        if (isset($c->id)) {
            $limit = (null !== $limit) ? (int)$limit : null;
            $children = Table\Categories::findBy(array('parent_id' => $c->id), 'order ASC', $limit);
            if (isset($children->rows[0])) {
                foreach ($children->rows as $child) {
                    $child = (array)$child;
                    $child = array_merge($child, \Phire\Model\FieldValue::getAll($child['id'], true));
                    $categoryAry[] = new \ArrayObject($child, \ArrayObject::ARRAY_AS_PROPS);
                }
            }
        }

        return $categoryAry;
    }

}

