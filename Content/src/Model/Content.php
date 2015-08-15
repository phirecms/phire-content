<?php

namespace Content\Model;

use Content\Table;
use Phire\Model\AbstractModel;
use Pop\Paginator\Paginator;

class Content extends AbstractModel
{

    protected $flatMap = [];

    /**
     * Get all content
     *
     * @param  string  $sort
     * @param  string  $title
     * @param  boolean $trash
     * @return array
     */
    public function getAll($sort = null, $title = null, $trash = false)
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
            'publish'             => DB_PREFIX . 'content.publish',
            'expire'              => DB_PREFIX . 'content.expire',
            'created'             => DB_PREFIX . 'content.created',
            'updated'             => DB_PREFIX . 'content.updated',
            'created_by'          => DB_PREFIX . 'content.created_by',
            'updated_by'          => DB_PREFIX . 'content.updated_by',
            'created_by_username' => DB_PREFIX . 'users.username'
        ];

        $sql = Table\Content::sql();
        $sql->select($selectFields)
            ->join(DB_PREFIX . 'users', [DB_PREFIX . 'users.id' => DB_PREFIX . 'content.created_by']);

        $params = [
            'type_id'   => $this->tid,
            'status'    => -2
        ];

        $order  = (null !== $sort) ? $this->getSortOrder($sort) : 'order ASC';
        $by     = explode(' ', $order);
        $sql->select()->orderBy($by[0], $by[1]);
        $sql->select()->where('type_id = :type_id');

        if ($trash) {
            $sql->select()->where('status = :status');
        } else {
            $sql->select()->where('status > :status');
        }

        if (null !== $title) {
            $params['title'] = '%' . $title . '%';
            $sql->select()->where('title LIKE :title');
        } else if (!$trash) {
            $sql->select()->where('parent_id IS NULL');
        }

        $content    = Table\Content::execute((string)$sql, $params);
        $contentAry = [];

        foreach ($content->rows() as $c) {
            $this->flatMap[] = new \ArrayObject([
                'id'                  => $c->id,
                'type_id'             => $c->type_id,
                'parent_id'           => $c->parent_id,
                'title'               => $c->title,
                'uri'                 => $c->uri,
                'slug'                => $c->slug,
                'status'              => $c->status,
                'roles'               => $c->roles,
                'publish'             => $c->publish,
                'expire'              => $c->expire,
                'created'             => $c->created,
                'updated'             => $c->updated,
                'created_by'          => $c->created_by,
                'updated_by'          => $c->updated_by,
                'created_by_username' => $c->created_by_username,
                'depth' => 0
            ], \ArrayObject::ARRAY_AS_PROPS);
            $c->depth     = 0;
            if ((null === $title) && (!$trash)) {
                $c->children = $this->getChildren($c, $selectFields, $params, $trash, $order);
            }
            $contentAry[] = $c;
        }

        return $contentAry;
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
            $this->getContent($content);
        }
    }

    /**
     * Get content by URI
     *
     * @param  string $uri
     * @param  boolean $fields
     * @return void
     */
    public function getByUri($uri, $fields = false)
    {
        $content = Table\Content::findBy(['uri' => $uri]);
        if (isset($content->id)) {
            $this->getContent($content, $fields);
        }
    }

    /**
     * Get content by date
     *
     * @param  string  $date
     * @param  string  $dateTimeFormat
     * @param  int     $summaryLength
     * @param  string  $limit
     * @param  string  $page
     * @param  boolean $fields
     * @return array
     */
    public function getByDate($date, $dateTimeFormat, $summaryLength, $limit = null, $page = null, $fields = false)
    {
        $sql1 = Table\Content::sql();
        $sql2 = clone $sql1;
        $sql2->select(['count' => 'COUNT(*)']);

        $sql1->select([
            'id'        => DB_PREFIX . 'content.id',
            'type_id'   => DB_PREFIX . 'content.type_id',
            'parent_id' => DB_PREFIX . 'content.parent_id',
            'title'     => DB_PREFIX . 'content.title',
            'uri'       => DB_PREFIX . 'content.uri',
            'slug'      => DB_PREFIX . 'content.slug',
            'status'    => DB_PREFIX . 'content.status',
            'publish'   => DB_PREFIX . 'content.publish',
            'expire'    => DB_PREFIX . 'content.expire'
        ]);

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
            ->where('publish <= :publish2');

        $sql2->select()
            ->where('status = :status')
            ->where('publish >= :publish1')
            ->where('publish <= :publish2');

        $params = [
            'status' => 1,
            'publish' => [
                $start,
                $end
            ]
        ];

        $count = Table\Content::execute((string)$sql2, $params)->count;

        if ($count > $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;

            $sql1->select()->offset($page)->limit($limit);
            $pages = new Paginator($count, $limit);
            $pages->useInput(true);
        } else {
            $pages = null;
        }

        $rows = Table\Content::execute((string)$sql1, $params)->rows();

        if ($fields) {
            $filters = ['strip_tags' => null];
            if ($summaryLength > 0) {
                $filters['substr'] = [0, $summaryLength];
            };
            foreach ($rows as $i => $row) {
                $fieldValues = \Fields\Model\FieldValue::getModelObjectValues('Content\Model\Content', $row->id, $filters);
                $rows[$i] = new \ArrayObject(array_merge((array)$row, $fieldValues), \ArrayObject::ARRAY_AS_PROPS);
                $rows[$i]->publish = date($dateTimeFormat, strtotime($rows[$i]->publish));
            }
        }

        return [
            'rows'  => $rows,
            'pages' => $pages
        ];
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
                $live = ((null !== $this->data['publish']) && (time() >= strtotime($this->data['publish'])));
                if (($live) && (null !== $this->data['expire'])) {
                    $live = (time() < strtotime($this->data['expire']));
                }
            } else if (($this->data['status'] == 0) && isset($sess->user->id)) {
                $live = true;
            }
        }

        // Check roles
        if (($live) && (count($this->data['roles']) > 0)) {
            if ((!isset($sess->user)) || (isset($sess->user) && !in_array($sess->user->role_id, $this->data['roles']))) {
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

        if (isset($fields['publish_year']) && ($fields['publish_year'] != '----') && ($fields['publish_month'] != '--')
            && ($fields['publish_day'] != '--')) {
            $publish = $fields['publish_year'] . '-' . $fields['publish_month'] . '-' . $fields['publish_day'];
            $publish .= (($fields['publish_hour'] != '--') && ($fields['publish_minute'] != '--')) ?
                ' ' . $fields['publish_hour'] . ':' . $fields['publish_minute'] . ':00' : ' 00:00:00';
        } else {
            $publish = date('Y-m-d H:i:s');
        }

        if (isset($fields['expire_year']) && ($fields['expire_year'] != '----') && ($fields['expire_month'] != '--')
            && ($fields['expire_day'] != '--')) {
            $expire = $fields['expire_year'] . '-' . $fields['expire_month'] . '-' . $fields['expire_day'];
            $expire .= (($fields['expire_hour'] != '--') && ($fields['expire_minute'] != '--')) ?
                ' ' . $fields['expire_hour'] . ':' . $fields['expire_minute'] . ':00' : ' 00:00:00';
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

            if (isset($fields['publish_year']) && ($fields['publish_year'] != '----') && ($fields['publish_month'] != '--') &&
                ($fields['publish_day'] != '--')) {
                $publish = $fields['publish_year'] . '-' . $fields['publish_month'] . '-' . $fields['publish_day'];
                $publish .= (($fields['publish_hour'] != '--') && ($fields['publish_minute'] != '--')) ?
                    ' ' . $fields['publish_hour'] . ':' . $fields['publish_minute'] . ':00' : ' 00:00:00';
            } else {
                $publish = date('Y-m-d H:i:s');
            }

            if (isset($fields['expire_year']) && ($fields['expire_year'] != '----') && ($fields['expire_month'] != '--') &&
                ($fields['expire_day'] != '--')) {
                $expire = $fields['expire_year'] . '-' . $fields['expire_month'] . '-' . $fields['expire_day'];
                $expire .= (($fields['expire_hour'] != '--') && ($fields['expire_minute'] != '--')) ?
                    ' ' . $fields['expire_hour'] . ':' . $fields['expire_minute'] . ':00' : ' 00:00:00';
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
     * @param  int     $userId
     * @param  boolean $fields
     * @return void
     */
    public function copy($userId, $fields = false)
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
                'template'   => $content->content_template,
                'roles'      => $content->roles,
                'order'      => $content->order,
                'created'    => date('Y-m-d H:i:s'),
                'created_by' => $userId
            ]);
            $newContent->save();

            if ($fields) {
                $fv = \Fields\Table\FieldValues::findBy(['model_id' => $oldId]);
                if ($fv->count() > 0) {
                    foreach ($fv->rows() as $value) {
                        $v = new \Fields\Table\FieldValues([
                            'field_id'  => $value->field_id,
                            'model_id'  => $newContent->id,
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
     * @param  int $status
     * @return boolean
     */
    public function hasPages($limit, $status = null)
    {
        return (null !== $status) ?
            (Table\Content::findBy(['status' => $status])->count() > $limit) :
            (Table\Content::findAll()->count() > $limit);
    }

    /**
     * Get count of libraries
     *
     * @param  int $status
     * @return int
     */
    public function getCount($status = null)
    {
        return (null !== $status) ?
            Table\Content::findBy(['status' => $status])->count() :
            Table\Content::findAll()->count();
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
     * @param  Table\Content $content
     * @param  boolean       $fields
     * @return void
     */
    protected function getContent(Table\Content $content, $fields = false)
    {
        if ($fields) {
            $c    = \Fields\Model\FieldValue::getModelObject('Content\Model\Content', [$content->id]);
            $data = $c->toArray();
        } else {
            $data = $content->getColumns();
        }

        $type = new ContentType();
        $type->getById($data['type_id']);

        $data['content_type'] = $type->content_type;

        if (null !== $data['publish']) {
            $publish     = explode(' ', $data['publish']);
            $publishDate = explode('-', $publish[0]);
            $publishTime = explode(':', $publish[1]);

            $data['publish_month']  = $publishDate[1];
            $data['publish_day']    = $publishDate[2];
            $data['publish_year']   = $publishDate[0];
            $data['publish_hour']   = $publishTime[0];
            $data['publish_minute'] = $publishTime[1];
        }

        if (null !== $data['expire']) {
            $expire     = explode(' ', $data['expire']);
            $expireDate = explode('-', $expire[0]);
            $expireTime = explode(':', $expire[1]);

            $data['expire_month']  = $expireDate[1];
            $data['expire_day']    = $expireDate[2];
            $data['expire_year']   = $expireDate[0];
            $data['expire_hour']   = $expireTime[0];
            $data['expire_minute'] = $expireTime[1];
        }

        if (null !== $content->created_by) {
            $createdBy = \Phire\Table\Users::findById($content->created_by);
            if (isset($createdBy->id)) {
                $data['created_by_username'] = $createdBy->username;
            }
        }

        if (null !== $content->updated_by) {
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
    }

    /**
     * Get content children
     *
     * @param  \ArrayObject|array $content
     * @param  array              $selectFields
     * @param  array              $params
     * @param  boolean            $trash
     * @param  string             $order
     * @param  int                $depth
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
        $sql->select()->where('type_id = :type_id');

        if ($trash) {
            $sql->select()->where('status = :status');
        } else {
            $sql->select()->where('status > :status');
        }

        if (isset($params['title'])) {
            $sql->select()->where('title LIKE :title');
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
