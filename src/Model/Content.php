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
namespace Phire\Content\Model;

use Phire\Content\Table;
use Phire\Model\AbstractModel;
use Pop\Paginator\Paginator;
use Pop\Dom\Child;

/**
 * Content Model class
 *
 * @category   Phire\Content
 * @package    Phire\Content
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
class Content extends AbstractModel
{

    protected $flatMap = [];

    /**
     * Get all content
     *
     * @param  int     $typeId
     * @param  string  $sort
     * @param  string  $title
     * @param  boolean $trash
     * @return array
     */
    public function getAll($typeId = null, $sort = null, $title = null, $trash = false)
    {
        $selectFields = [
            'id'                  => DB_PREFIX . 'content.id',
            'type_id'             => DB_PREFIX . 'content.type_id',
            'parent_id'           => DB_PREFIX . 'content.parent_id',
            'title'               => DB_PREFIX . 'content.title',
            'uri'                 => DB_PREFIX . 'content.uri',
            'slug'                => DB_PREFIX . 'content.slug',
            'status'              => DB_PREFIX . 'content.status',
            'roles'               => DB_PREFIX . 'content.roles',
            'order'               => DB_PREFIX . 'content.order',
            'publish'             => DB_PREFIX . 'content.publish',
            'expire'              => DB_PREFIX . 'content.expire',
            'created'             => DB_PREFIX . 'content.created',
            'updated'             => DB_PREFIX . 'content.updated',
            'created_by'          => DB_PREFIX . 'content.created_by',
            'updated_by'          => DB_PREFIX . 'content.updated_by',
            'created_by_username' => DB_PREFIX . 'users.username'
        ];

        if (isset($_GET['category_id']) && ($_GET['category_id'] > 0)) {
            $selectFields['category_id']     = DB_PREFIX . 'category_items.category_id';
            $selectFields['content_id']      = DB_PREFIX . 'category_items.content_id';
            $selectFields['category_title']  = DB_PREFIX . 'categories.title';
        }

        $sql = Table\Content::sql();
        $sql->select($selectFields)
            ->join(DB_PREFIX . 'users', [DB_PREFIX . 'users.id' => DB_PREFIX . 'content.created_by']);

        if (isset($_GET['category_id']) && ($_GET['category_id'] > 0)) {
            $sql->select()->join(DB_PREFIX . 'category_items', [DB_PREFIX . 'category_items.content_id' => DB_PREFIX . 'content.id']);
            $sql->select()->join(DB_PREFIX . 'categories', [DB_PREFIX . 'category_items.category_id' => DB_PREFIX . 'categories.id']);
        }

        if (null !== $typeId) {
            $sql->select()->where('type_id = :type_id');
            $params = [
                'type_id' => $typeId,
                'status'  => -2
            ];
        } else {
            $params = [
                'status'  => -2
            ];
        }

        $order  = (null !== $sort) ? $this->getSortOrder($sort) : 'id DESC';
        $by     = explode(' ', $order);
        $sql->select()->orderBy($by[0], $by[1]);

        if ($trash) {
            $sql->select()->where('status = :status');
        } else {
            $sql->select()->where('status > :status');
        }

        if (null !== $title) {
            $params['title'] = '%' . $title . '%';
            $sql->select()->where(DB_PREFIX . 'content.title LIKE :title');
        } else if (!$trash) {
            $sql->select()->where('parent_id IS NULL');
        }

        if (isset($_GET['category_id']) && ($_GET['category_id'] > 0)) {
            $sql->select()->where('category_id = :category_id');
            $params['category_id'] = (int)$_GET['category_id'];
        }

        $content    = Table\Content::execute((string)$sql, $params);
        $contentAry = [];

        foreach ($content->rows() as $c) {
            $contentData = [
                'id'                  => $c->id,
                'type_id'             => $c->type_id,
                'parent_id'           => $c->parent_id,
                'title'               => $c->title,
                'uri'                 => $c->uri,
                'slug'                => $c->slug,
                'status'              => $c->status,
                'roles'               => $c->roles,
                'order'               => $c->order,
                'publish'             => $c->publish,
                'expire'              => $c->expire,
                'created'             => $c->created,
                'updated'             => $c->updated,
                'created_by'          => $c->created_by,
                'updated_by'          => $c->updated_by,
                'created_by_username' => $c->created_by_username,
                'depth'               => 0
            ];

            if (isset($c->category_title)) {
                $contentData['category_title'] = $c->category_title;
            }

            $this->flatMap[] = new \ArrayObject($contentData, \ArrayObject::ARRAY_AS_PROPS);
            $c->depth     = 0;
            if ((null === $title) && (!$trash)) {
                $c->children = $this->getChildren($c, $selectFields, $params, $trash, $order);
            }
            $contentAry[] = $c;
        }

        return $contentAry;
    }

    /**
     * Get all content by type ID
     *
     * @param  int $typeId
     * @return array
     */
    public function getAllByTypeId($typeId)
    {
        $type = new ContentType();
        $type->getById($typeId);

        $contentAry =  Table\Content::findBy(['type_id' => $typeId, 'status' => 1], ['order' => 'order, id ASC'])->rows();
        $ary        = [];

        foreach ($contentAry as $cont) {
            if (class_exists('Phire\Fields\Model\FieldValue')) {
                $c    = \Phire\Fields\Model\FieldValue::getModelObject('Phire\Content\Model\Content', [$cont->id]);
                $data = $c->toArray();
            } else {
                $data = (array)$cont;
            }
            $c = $this->setContent($data);
            $sess = \Pop\Web\Session::getInstance();
            if (is_array($c['roles']) && (count($c['roles']) > 0)) {
                if ((isset($sess->user) && in_array($sess->user->role_id, $c['roles'])) ||
                    (isset($sess->member) && in_array($sess->member->role_id, $c['roles']))) {
                    $ary[] = $c;
                }
            } else {
                $ary[] = $c;
            }
        }

        return $ary;
    }

    /**
     * Get content by ID
     *
     * @param  int $id
     * @return void
     */
    public function getById($id)
    {
        $content = Table\Content::findById($id);
        if (isset($content->id)) {
            if (class_exists('Phire\Fields\Model\FieldValue')) {
                $this->data = array_merge($this->data, $content->getColumns());
                $c    = \Phire\Fields\Model\FieldValue::getModelObject($this);
                $data = $c->toArray();
            } else {
                $data = $content->getColumns();
            }

            $this->setContent($data);
        }
    }

    /**
     * Get content by URI
     *
     * @param  string $uri
     * @return void
     */
    public function getByUri($uri)
    {
        $content = Table\Content::findBy(['uri' => $uri]);
        if (isset($content->id)) {
            if (class_exists('Phire\Fields\Model\FieldValue')) {
                $c    = \Phire\Fields\Model\FieldValue::getModelObject('Phire\Content\Model\Content', [$content->id]);
                $data = $c->toArray();
            } else {
                $data = $content->getColumns();
            }
            $this->setContent($data);
        }
    }

    /**
     * Get content by date
     *
     * @param  string $date
     * @param  string $dateTimeFormat
     * @param  array  $filters
     * @param  string $limit
     * @param  string $page
     * @return array
     */
    public function getByDate($date, $dateTimeFormat, $filters, $limit = null, $page = null)
    {
        $sql1 = Table\Content::sql();
        $sql2 = clone $sql1;
        $sql2->select([
            'count'   => 'COUNT(*)',
            'in_date' => DB_PREFIX . 'content_types.in_date'
        ]);

        $sql1->select([
            'id'        => DB_PREFIX . 'content.id',
            'type_id'   => DB_PREFIX . 'content.type_id',
            'parent_id' => DB_PREFIX . 'content.parent_id',
            'title'     => DB_PREFIX . 'content.title',
            'uri'       => DB_PREFIX . 'content.uri',
            'slug'      => DB_PREFIX . 'content.slug',
            'status'    => DB_PREFIX . 'content.status',
            'publish'   => DB_PREFIX . 'content.publish',
            'expire'    => DB_PREFIX . 'content.expire',
            'in_date'   => DB_PREFIX . 'content_types.in_date'
        ]);

        $sql1->select()->join(
            DB_PREFIX . 'content_types', [DB_PREFIX . 'content_types.id' => DB_PREFIX . 'content.type_id']
        );

        $sql2->select()->join(
            DB_PREFIX . 'content_types', [DB_PREFIX . 'content_types.id' => DB_PREFIX . 'content.type_id']
        );

        $dateAry = explode('/', $date);

        if (count($dateAry) == 3) {
            $start = $dateAry[0] . '-' . $dateAry[1] . '-' . $dateAry[2] . ' 00:00:00';
            $end   = $dateAry[0] . '-' . $dateAry[1] . '-' . $dateAry[2] . ' 23:59:59';
        } else if (count($dateAry) == 2) {
            $start = $dateAry[0] . '-' . $dateAry[1] . '-01 00:00:00';
            $end   = $dateAry[0] . '-' . $dateAry[1] . '-' .
                date('t', strtotime($dateAry[0] . '-' . $dateAry[1] . '-01')) . ' 23:59:59';
        } else {
            $start = $dateAry[0] . '-01-01 00:00:00';
            $end   = $dateAry[0] . '-12-31 23:59:59';
        }

        $sql1->select()
             ->where('status = :status')
             ->where('publish >= :publish1')
             ->where('publish <= :publish2')
             ->where('in_date = :in_date');

        $sql2->select()
             ->where('status = :status')
             ->where('publish >= :publish1')
             ->where('publish <= :publish2')
             ->where('in_date = :in_date');

        $params = [
            'status'  => 1,
            'publish' => [
                $start,
                $end
            ],
            'in_date' => 1
        ];

        $count = Table\Content::execute((string)$sql2, $params)->count;

        if ($count > $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;
            $sql1->select()->offset($page)->limit($limit);
        }

        $sql1->select()->orderBy('publish', 'DESC');

        $rows = Table\Content::execute((string)$sql1, $params)->rows();

        if (class_exists('Phire\Fields\Model\FieldValue')) {
            foreach ($rows as $i => $row) {
                $fieldValues       = \Phire\Fields\Model\FieldValue::getModelObjectValues('Phire\Content\Model\Content', $row->id, $filters);
                $rows[$i]          = new \ArrayObject(array_merge((array)$row->getColumns(), $fieldValues), \ArrayObject::ARRAY_AS_PROPS);
                $rows[$i]->publish = date($dateTimeFormat, strtotime($rows[$i]->publish));
            }
        }

        return $rows;
    }

    /**
     * Get pages of content by date
     *
     * @param  string $date
     * @param  string $limit
     * @return mixed
     */
    public function getDatePages($date, $limit = null)
    {
        $sql = Table\Content::sql();
        $sql->select([
            'count'   => 'COUNT(*)',
            'in_date' => DB_PREFIX . 'content_types.in_date'
        ]);

        $sql->select()->join(
            DB_PREFIX . 'content_types', [DB_PREFIX . 'content_types.id' => DB_PREFIX . 'content.type_id']
        );

        $dateAry = explode('/', $date);

        if (count($dateAry) == 3) {
            $start = $dateAry[0] . '-' . $dateAry[1] . '-' . $dateAry[2] . ' 00:00:00';
            $end   = $dateAry[0] . '-' . $dateAry[1] . '-' . $dateAry[2] . ' 23:59:59';
        } else if (count($dateAry) == 2) {
            $start = $dateAry[0] . '-' . $dateAry[1] . '-01 00:00:00';
            $end   = $dateAry[0] . '-' . $dateAry[1] . '-' .
                date('t', strtotime($dateAry[0] . '-' . $dateAry[1] . '-01')) . ' 23:59:59';
        } else {
            $start = $dateAry[0] . '-01-01 00:00:00';
            $end   = $dateAry[0] . '-12-31 23:59:59';
        }

        $sql->select()
            ->where('status = :status')
            ->where('publish >= :publish1')
            ->where('publish <= :publish2')
            ->where('in_date = :in_date');

        $params = [
            'status'  => 1,
            'publish' => [
                $start,
                $end
            ],
            'in_date' => 1
        ];

        $count = Table\Content::execute((string)$sql, $params)->count;

        if ($count > $limit) {
            $pages = new Paginator($count, $limit);
            $pages->useInput(true);
        } else {
            $pages = null;
        }

        return $pages;
    }

    /**
     * Get content archive
     *
     * @param  boolean $count
     * @return mixed
     */
    public function getArchive($count = true)
    {
        $sql = Table\Content::sql();
        $sql->select([
            'publish' => DB_PREFIX . 'content.publish',
            'in_date' => DB_PREFIX . 'content_types.in_date'
        ]);

        $sql->select()->join(
            DB_PREFIX . 'content_types', [DB_PREFIX . 'content_types.id' => DB_PREFIX . 'content.type_id']
        );

        $sql->select()
            ->where('status = :status')
            ->where('in_date = :in_date');

        $sql->select()->orderBy('publish', 'DESC');

        $params = [
            'status'  => 1,
            'in_date' => 1
        ];

        $content = Table\Content::execute((string)$sql, $params);
        $archive = [];

        foreach ($content->rows() as $c) {
            $year = substr($c->publish, 0, 4);
            if (isset($archive[$year])) {
                $archive[$year]++;
            } else {
                $archive[$year] = 1;
            }
        }

        $archiveNav = null;

        if (count($archive) > 0) {
            $archiveNav = new Child('ul');
            $archiveNav->setAttributes([
                'id'    => 'archive-nav',
                'class' => 'archive-nav'
            ]);
            foreach ($archive as $year => $num) {
                $link = ($count) ? $year . ' <span>(' . $num . ')</span>' : $year;
                $a    = new Child('a', $link);
                $a->setAttribute('href', BASE_PATH . '/' . $year);

                $li = new Child('li');
                $li->addChild($a);
                $archiveNav->addChild($li);
            }
        }

        return $archiveNav;
    }

    /**
     * Determine if the URI is live
     *
     * @param  array $sess
     * @return boolean
     */
    public function isLive($sess)
    {
        $live = false;

        if (isset($this->data['id'])) {
            if ($this->data['status'] == 1) {
                if ($this->data['strict_publishing']) {
                    $live = ((null !== $this->data['publish']) && (time() >= strtotime($this->data['publish'])));
                    if (($live) && (!empty($this->data['expire']))) {
                        $live = (time() < strtotime($this->data['expire']));
                    }
                } else {
                    $live = true;
                }
            } else if (($this->data['status'] == 0) && isset($sess->user->id)) {
                $live = true;
            }
        }

        // Check roles
        if (($live) && (count($this->data['roles']) > 0)) {
            if (isset($sess->member) && !in_array($sess->member->role_id, $this->data['roles'])) {
                $live = false;
            } else if ((!isset($sess->member) && !isset($sess->user)) || (isset($sess->user) && !in_array($sess->user->role_id, $this->data['roles']))) {
                $live = false;
            }
        }

        return $live;
    }

    /**
     * Method to get content breadcrumb
     *
     * @param  int    $id
     * @param  string $sep
     * @return string
     */
    public function getBreadcrumb($id, $sep = '&gt;')
    {
        $breadcrumb = null;
        $content    = Table\Content::findById($id);
        if (isset($content->id)) {
            $breadcrumb = $content->title;
            $pId        = $content->parent_id;

            while (null !== $pId) {
                $content = Table\Content::findById($pId);
                if (isset($content->id)) {
                    if ($content->status == 1) {
                        $breadcrumb = '<a href="' . BASE_PATH . $content->uri . '">' . $content->title . '</a>' .
                            ' <span>' . $sep . '</span> ' . $breadcrumb;
                    }
                    $pId = $content->parent_id;
                }
            }
        }

        return $breadcrumb;
    }

    /**
     * Save new content
     *
     * @param  array $fields
     * @param  int $userId
     * @return void
     */
    public function save(array $fields, $userId)
    {
        $publish = null;
        $expire  = null;

        if (isset($fields['publish_date']) && !empty($fields['publish_date'])) {
            $publish = $fields['publish_date'];
            $publish .= (isset($fields['publish_time']) && !empty($fields['publish_time'])) ? ' ' . $fields['publish_time'] : ' 00:00:00';
            $publish = date('Y-m-d H:i:s', strtotime($publish));
        } else {
            $publish = date('Y-m-d H:i:s');
        }

        if (isset($fields['expire_date']) && !empty($fields['expire_date'])) {
            $expire = $fields['expire_date'];
            $expire .= (isset($fields['expire_time']) && !empty($fields['expire_time'])) ? ' ' . $fields['expire_time'] : ' 00:00:00';
            $expire = date('Y-m-d H:i:s', strtotime($expire));
        }

        $roles    = (isset($_POST['roles']) && is_array($_POST['roles']) && (count($_POST['roles']) > 0)) ? $_POST['roles'] : [];
        $parentId = ($fields['content_parent_id'] != '----') ? $fields['content_parent_id'] : null;

        $content = new Table\Content([
            'type_id'    => $fields['type_id'],
            'parent_id'  => $parentId,
            'title'      => $fields['title'],
            'uri'        => $fields['uri'],
            'slug'       => $fields['slug'],
            'publish'    => $publish,
            'expire'     => $expire,
            'status'     => (int)$fields['content_status'],
            'template'   => (($fields['content_template'] != '0') ? $fields['content_template'] : null),
            'roles'      => serialize($roles),
            'order'      => (int)$fields['order'],
            'force_ssl'  => (int)$fields['force_ssl'],
            'hierarchy'  => $this->getHierarchy($parentId),
            'created'    => date('Y-m-d H:i:s'),
            'created_by' => $userId
        ]);
        $content->save();

        $this->data = array_merge($this->data, $content->getColumns());
    }

    /**
     * Update an existing content
     *
     * @param  array $fields
     * @param  int $userId
     * @return void
     */
    public function update(array $fields, $userId)
    {
        $content = Table\Content::findById($fields['id']);
        if (isset($content->id)) {
            $publish = null;
            $expire  = null;

            if (isset($fields['publish_date']) && !empty($fields['publish_date'])) {
                $publish = $fields['publish_date'];
                $publish .= (isset($fields['publish_time']) && !empty($fields['publish_time'])) ? ' ' . $fields['publish_time'] : ' 00:00:00';
                $publish = date('Y-m-d H:i:s', strtotime($publish));
            } else {
                $publish = date('Y-m-d H:i:s');
            }

            if (isset($fields['expire_date']) && !empty($fields['expire_date'])) {
                $expire = $fields['expire_date'];
                $expire .= (isset($fields['expire_time']) && !empty($fields['expire_time'])) ? ' ' . $fields['expire_time'] : ' 00:00:00';
                $expire = date('Y-m-d H:i:s', strtotime($expire));
            }

            $roles    = (isset($_POST['roles']) && is_array($_POST['roles']) && (count($_POST['roles']) > 0)) ? $_POST['roles'] : [];
            $parentId = ($fields['content_parent_id'] != '----') ? $fields['content_parent_id'] : null;

            $content->type_id    = $fields['type_id'];
            $content->parent_id  = $parentId;
            $content->title      = $fields['title'];
            $content->uri        = $fields['uri'];
            $content->slug       = $fields['slug'];
            $content->publish    = $publish;
            $content->expire     = $expire;
            $content->status     = (int)$fields['content_status'];
            $content->template   = (($fields['content_template'] != '0') ? $fields['content_template'] : null);
            $content->roles      = serialize($roles);
            $content->order      = (int)$fields['order'];
            $content->force_ssl  = (int)$fields['force_ssl'];
            $content->hierarchy  = $this->getHierarchy($parentId);
            $content->updated    = date('Y-m-d H:i:s');
            $content->updated_by = $userId;
            $content->save();

            $this->changeDescendantUris($content->id, $content->uri);

            $this->data = array_merge($this->data, $content->getColumns());
        }
    }

    /**
     * Copy content
     *
     * @param  int $userId
     * @return void
     */
    public function copy($userId)
    {
        $oldId   = (int)$this->data['id'];
        $content = Table\Content::findById($oldId);

        if (isset($content->id)) {
            $i     = 1;
            $title = $content->title . ' (Copy ' . $i . ')';
            $uri   = $content->uri . '-' . $i;
            $slug  = $content->slug . '-' . $i;

            $dupeContent = Table\Content::findBy(['uri' => $uri]);

            while (isset($dupeContent->id)) {
                $i++;
                $title = $content->title . ' (Copy ' . $i . ')';
                $uri   = $content->uri . '-' . $i;
                $slug  = $content->slug . '-' . $i;
                $dupeContent = Table\Content::findBy(['uri' => $uri]);
            }

            $newContent = new Table\Content([
                'type_id'    => $content->type_id,
                'parent_id'  => $content->parent_id,
                'title'      => $title,
                'uri'        => $uri,
                'slug'       => $slug,
                'publish'    => date('Y-m-d H:i:s'),
                'expire'     => null,
                'status'     => -1,
                'template'   => $content->template,
                'roles'      => $content->roles,
                'order'      => $content->order,
                'force_ssl'  => $content->force_ssl,
                'created'    => date('Y-m-d H:i:s'),
                'created_by' => $userId
            ]);
            $newContent->save();

            if (class_exists('Phire\Fields\Table\FieldValues')) {
                $fv = \Phire\Fields\Table\FieldValues::findBy(['model_id' => $oldId]);
                if ($fv->count() > 0) {
                    foreach ($fv->rows() as $value) {
                        $v = new \Phire\Fields\Table\FieldValues([
                            'field_id'  => $value->field_id,
                            'model_id'  => $newContent->id,
                            'model'     => 'Phire\Content\Model\Content',
                            'value'     => $value->value,
                            'timestamp' => time(),
                            'history'   => $value->history
                        ]);
                        $v->save();
                    }
                }
            }

            $this->data = array_replace($this->data, $newContent->getColumns());
        }
    }

    /**
     * Process content
     *
     * @param  array $fields
     * @return void
     */
    public function process(array $fields)
    {
        if (isset($fields['process_content'])) {
            foreach ($fields['process_content'] as $id) {
                $content = Table\Content::findById((int)$id);
                if (isset($content->id)) {
                    if ($fields['content_process_action'] == -3) {
                        $content->delete();
                    } else {
                        $content->status = $fields['content_process_action'];
                        $content->save();
                    }
                }
            }
        }
    }

    /**
     * Determine if list of libraries has pages
     *
     * @param  int $limit
     * @param  int $typeId
     * @param  int $status
     * @return boolean
     */
    public function hasPages($limit, $typeId = null, $status = null)
    {
        if (null !== $typeId) {
            return (null !== $status) ?
                (Table\Content::findBy(['type_id' => $typeId, 'status' => $status])->count() > $limit) :
                (Table\Content::findBy(['type_id' => $typeId])->count() > $limit);
        } else {
            return (null !== $status) ?
                (Table\Content::findBy(['status' => $status])->count() > $limit) :
                (Table\Content::findAll()->count() > $limit);
        }
    }

    /**
     * Get count of libraries
     *
     * @param  int $typeId
     * @param  int $status
     * @return int
     */
    public function getCount($typeId = null, $status = null)
    {
        if (null !== $typeId) {
            return (null !== $status) ?
                Table\Content::findBy(['type_id' => $typeId, 'status' => $status])->count() :
                Table\Content::findBy(['type_id' => $typeId])->count();
        } else {
            return (null !== $status) ?
                Table\Content::findBy(['status' => $status])->count() :
                Table\Content::findAll()->count();
        }
    }

    /**
     * Get category flat map
     *
     * @return array
     */
    public function getFlatMap()
    {
        return $this->flatMap;
    }

    /**
     * Change the descendant URIs
     *
     * @param  int $id
     * @param  string $uri
     * @return mixed
     */
    protected function changeDescendantUris($id, $uri)
    {
        $children = Table\Content::findBy(['parent_id' => $id]);

        while ($children->count() > 0) {
            foreach ($children->rows() as $child) {
                $c = Table\Content::findById($child->id);
                if (isset($c->id)) {
                    $c->uri = $uri . '/' . $c->slug;
                    $c->save();
                }
                $children = $this->changeDescendantUris($c->id, $c->uri);
            }
        }

        return $children;
    }

    /**
     * Get parental hierarchy
     *
     * @param  int $parentId
     * @return string
     */
    protected function getHierarchy($parentId = null)
    {
        $parents = [];

        while (null !== $parentId) {
            array_unshift($parents, $parentId);
            $category = Table\Content::findById($parentId);
            if (isset($category->id)) {
                $parentId = $category->parent_id;
            }
        }

        return (count($parents) > 0) ? implode('|', $parents) : '';
    }

    /**
     * Get content
     *
     * @param  array $data
     * @return array
     */
    protected function setContent(array $data)
    {
        $type = new ContentType();
        $type->getById($data['type_id']);

        $data['content_type']           = $type->content_type;
        $data['content_type_force_ssl'] = $type->force_ssl;
        $data['strict_publishing']      = $type->strict_publishing;

        if (!empty($data['publish'])) {
            $publish = explode(' ', $data['publish']);
            $data['publish_date'] = $publish[0];
            $data['publish_time'] = $publish[1];

            if (isset($this->date_format)) {
                $data['publish_date'] = date($this->date_format, strtotime($data['publish_date']));
            }
            if (isset($this->time_format)) {
                $data['publish_time'] = date($this->time_format, strtotime($data['publish_time']));
            }
        }

        if (!empty($data['expire'])) {
            $expire = explode(' ', $data['expire']);
            $data['expire_date'] = $expire[0];
            $data['expire_time'] = $expire[1];
            if (isset($this->date_format)) {
                $data['expire_date'] = date($this->date_format, strtotime($data['expire_date']));
            }
            if (isset($this->time_format)) {
                $data['expire_time'] = date($this->time_format, strtotime($data['expire_time']));
            }
        }

        if (!empty($content->created_by)) {
            $createdBy = \Phire\Table\Users::findById($content->created_by);
            if (isset($createdBy->id)) {
                $data['created_by_username'] = $createdBy->username;
            }
        }

        if (!empty($content->updated_by)) {
            $updatedBy = \Phire\Table\Users::findById($content->updated_by);
            if (isset($updatedBy->id)) {
                $data['updated_by_username'] = $updatedBy->username;
            }
        }

        $data['content_parent_id'] = $data['parent_id'];
        $data['content_status']    = $data['status'];
        $data['content_template']  = $data['template'];
        $data['breadcrumb']        = $this->getBreadcrumb($data['id'], ((null !== $this->separator) ? $this->separator : '&gt;'));
        $data['breadcrumb_text']   = strip_tags($data['breadcrumb'], 'span');

        if (!is_array($data['roles']) && is_string($data['roles'])) {
            $data['roles'] = unserialize($data['roles']);
        }

        $this->data = array_merge($this->data, $data);
        return $this->data;
    }

    /**
     * Get content children
     *
     * @param  mixed   $content
     * @param  array   $selectFields
     * @param  array   $params
     * @param  boolean $trash
     * @param  string  $order
     * @param  int     $depth
     * @return array
     */
    protected function getChildren($content, $selectFields, $params, $trash, $order, $depth = 0)
    {
        $children = [];

        $sql = Table\Content::sql();
        $sql->select($selectFields)
            ->join(DB_PREFIX . 'users', [DB_PREFIX . 'users.id' => DB_PREFIX . 'content.created_by']);

        $params = ['parent_id' => $content->id] + $params;

        $by = explode(' ', $order);
        $sql->select()->orderBy($by[0], $by[1]);
        $sql->select()->where('parent_id = :parent_id');
        if (isset($params['type_id'])) {
            $sql->select()->where('type_id = :type_id');
        }

        if ($trash) {
            $sql->select()->where('status = :status');
        } else {
            $sql->select()->where('status > :status');
        }

        if (isset($params[DB_PREFIX . 'content.title'])) {
            $sql->select()->where(DB_PREFIX . 'content.title LIKE :title');
        }

        $child = Table\Content::execute((string)$sql, $params);

        if ($child->hasRows()) {
            foreach ($child->rows() as $c) {
                $this->flatMap[] = new \ArrayObject([
                    'id'                  => $c->id,
                    'type_id'             => $c->type_id,
                    'parent_id'           => $c->parent_id,
                    'title'               => $c->title,
                    'uri'                 => $c->uri,
                    'slug'                => $c->slug,
                    'status'              => $c->status,
                    'roles'               => $c->roles,
                    'order'               => $c->order,
                    'publish'             => $c->publish,
                    'expire'              => $c->expire,
                    'created'             => $c->created,
                    'updated'             => $c->updated,
                    'created_by'          => $c->created_by,
                    'updated_by'          => $c->updated_by,
                    'created_by_username' => $c->created_by_username,
                    'depth' => $depth + 1
                ], \ArrayObject::ARRAY_AS_PROPS);
                $c->depth    = $depth + 1;
                $c->children = $this->getChildren($c, $selectFields, $params, $trash, $order, ($depth + 1));
                $children[]  = $c;
            }
        }

        return $children;
    }

}
