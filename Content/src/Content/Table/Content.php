<?php
/**
 * @namespace
 */
namespace Content\Table;

use Pop\Db\Record;

class Content extends Record
{

    /**
     * @var   string
     */
    protected $tableName = 'content';

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
     * Static method to get content by date range
     *
     * @param  array $date
     * @return self
     */
    public static function findByDate($date)
    {
        $dateAry = explode('/', $date['match']);
        $site = \Phire\Table\Sites::getSite();

        if (count($dateAry) == 3) {
            $start = $dateAry[0] . '-' . $dateAry[1] . '-' . $dateAry[2] . ' 00:00:00';
            $end = $dateAry[0] . '-' . $dateAry[1] . '-' . $dateAry[2] . ' 23:59:59';
        } else if (count($dateAry) == 2) {
            $start = $dateAry[0] . '-' . $dateAry[1] . '-01 00:00:00';
            $end = $dateAry[0] . '-' . $dateAry[1] . '-' . date('t', strtotime($dateAry[0] . '-' . $dateAry[1] . '-01')) . ' 23:59:59';
        } else {
            $start = $dateAry[0] . '-01-01 00:00:00';
            $end = $dateAry[0] . '-12-31 23:59:59';
        }

        // Create SQL object and build SQL statement
        $sql = static::getSql();
        $sql->select()
            ->where()
            ->equalTo('site_id', ':site_id')
            ->greaterThanOrEqualTo('publish', ':publish1')
            ->lessThanOrEqualTo('publish', ':publish2');

        // If there is a content URI
        if (!empty($date['uri'])) {
            $sql->select()->where()->equalTo('uri', ':uri');
            $sql->select()->where()->equalTo('status', ':status');
            $content = static::execute($sql->render(true), array('site_id' => $site->id, 'publish' => array($start, $end), 'uri' => $date['uri'], 'status' => 2));
        } else {
            $sql->select()->where()->equalTo('status', ':status');
            $content = static::execute($sql->render(true), array('site_id' => $site->id, 'publish' => array($start, $end), 'status' => 2));
        }

        return $content;
    }

}

