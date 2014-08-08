<?php
/**
 * @namespace
 */
namespace Content\Table;

use Pop\Db\Record;

class Categories extends Record
{

    /**
     * @var   string
     */
    protected $tableName = 'categories';

    /**
     * @var   string
     */
    protected $primaryId = 'id';

    /**
     * @var   boolean
     */
    protected $auto = true;

    /**
     * @var   string
     */
    protected $prefix = DB_PREFIX;

    /**
     * Static method to get categories with model count
     *
     * @return self
     */
    public static function getCategoriesWithCount()
    {
        $site = \Phire\Table\Sites::findBy(array('document_root' => $_SERVER['DOCUMENT_ROOT']));
        $siteId = (isset($site->id)) ? $site->id : '0';

        // Create SQL object and get first SQL result set of
        // content to category where content object is published or a file
        $firstSql = \Content\Table\ContentToCategories::getSql();
        $firstSql->select(array(
            'content_id',
            'category_id',
            'site_id',
            'status',
            'publish',
            'expire'
        ))->join(DB_PREFIX . 'content', array('content_id', 'id'), 'LEFT JOIN')
          ->where()->nest()->isNull('status')->equalTo('status', 2, 'OR');

        $firstSql->select()->where()->equalTo('site_id', $siteId);
        $firstSql->select()->where()->nest()->isNull('publish')->lessThanOrEqualTo('publish', date('Y-m-d H:i:s'), 'OR');
        $firstSql->select()->where()->nest()->isNull('expire')->greaterThan('expire', date('Y-m-d H:i:s'), 'OR');
        $firstSql->setAlias('content_live');

        // Create SQL object and get second result set of the
        // actual count of content objects to categories, using
        // the first SQL object as a sub-select
        $secondSql = \Content\Table\ContentToCategories::getSql();
        $secondSql->select(array(
            0     => 'category_id',
            'num' => 'COUNT(*)'
        ))->groupBy('category_id');

        $secondSql->setAlias('cat_count');
        $secondSql->setTable($firstSql);

        // Create SQL object to get the category/content data
        // using the the nested sub-selects for the JOIN
        $catSql = static::getSql();

        $catSql->select(array(
            DB_PREFIX . 'categories.id',
            DB_PREFIX . 'categories.parent_id',
            DB_PREFIX . 'categories.title',
            DB_PREFIX . 'categories.uri',
            DB_PREFIX . 'categories.slug',
            DB_PREFIX . 'categories.order',
            DB_PREFIX . 'categories.total',
            'cat_count.num'
        ))->join($secondSql, array('id', 'category_id'), 'LEFT JOIN')
          ->orderBy('order', 'ASC');

        return static::execute($catSql->render(true));
    }
}

