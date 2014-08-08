<?php
/**
 * @namespace
 */
namespace Content\Table;

use Pop\Db\Record;

class Templates extends Record
{

    /**
     * @var   string
     */
    protected $tableName = 'templates';

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
     * Static method to get the template and any child templates
     *
     * @param  int $id
     * @return array
     */
    public static function getTemplate($id)
    {
        $templates = array();
        $tmpl = static::findById($id);

        if (isset($tmpl->id)) {
            $templates[$tmpl->device] = array(
                'content_type' => $tmpl->content_type,
                'template'     => $tmpl->template
            );
            $children = static::findAll('id ASC', array('parent_id' => $tmpl->id));
            if (isset($children->rows[0])) {
                foreach ($children->rows as $child) {
                    $templates[$child->device] = array(
                        'content_type' => $child->content_type,
                        'template'     => $child->template
                    );
                }
            }
        }

        return $templates;
    }

}

