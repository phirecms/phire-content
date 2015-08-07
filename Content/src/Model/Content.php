<?php

namespace Content\Model;

use Content\Table;
use Phire\Model\AbstractModel;
use Pop\Archive\Archive;
use Pop\File\Dir;
use Pop\File\Upload;

class Content extends AbstractModel
{

    /**
     * Get all content
     *
     * @param  int     $limit
     * @param  int     $page
     * @param  string  $sort
     * @param  string  $title
     * @param  boolean $trash
     * @return array
     */
    public function getAll($limit = null, $page = null, $sort = null, $title = null, $trash = false)
    {
        $sql = Table\Content::sql();
        $sql->select([
            'id'                  => DB_PREFIX . 'content.id',
            'type_id'             => DB_PREFIX . 'content.type_id',
            'parent_id'           => DB_PREFIX . 'content.parent_id',
            'title'               => DB_PREFIX . 'content.title',
            'uri'                 => DB_PREFIX . 'content.uri',
            'slug'                => DB_PREFIX . 'content.slug',
            'status'              => DB_PREFIX . 'content.status',
            'publish'             => DB_PREFIX . 'content.publish',
            'expire'              => DB_PREFIX . 'content.expire',
            'created'             => DB_PREFIX . 'content.created',
            'updated'             => DB_PREFIX . 'content.updated',
            'created_by'          => DB_PREFIX . 'content.created_by',
            'updated_by'          => DB_PREFIX . 'content.updated_by',
            'created_by_username' => DB_PREFIX . 'users.username'
        ])->join(DB_PREFIX . 'users', [DB_PREFIX . 'users.id' => DB_PREFIX . 'content.created_by']);

        if (null !== $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;

            $sql->select()->offset($page)->limit($limit);
        }

        $params = [
            'type_id' => $this->tid,
            'status'  => -2
        ];

        $order  = (null !== $sort) ? $this->getSortOrder($sort, $page) : 'id DESC';
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
        }

        return Table\Content::execute((string)$sql, $params)->rows();
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
     * @return void
     */
    public function getByUri($uri)
    {
        $content = Table\Content::findBy(['uri' => $uri]);
        if (isset($content->id)) {
            $this->getContent($content);
        }
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
     * Get parents
     *
     * @param  int $tid
     * @param  int $id
     * @return array
     */
    public function getParents($tid, $id = null)
    {
        $parents = [];

        $content = Table\Content::findBy(['type_id' => $tid]);
        foreach ($content->rows() as $c) {
            if ($c->id != $id) {
                $pid   = $c->parent_id;
                $depth = 0;
                $isAncestor = false;
                while (null !== $pid) {
                    if ($pid ==  $id) {
                        $isAncestor = true;
                        break;
                    }
                    $parent = Table\Content::findById($pid);
                    if (isset($parent->id)) {
                        $pid = $parent->parent_id;
                        $depth++;
                    }
                }

                if (!$isAncestor) {
                    $parents[$c->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth) .
                        (($depth > 0) ? ' - ' : '') . $c->title;
                }
            }
        }

        return $parents;
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

        $roles = (isset($fields['roles']) && is_array($fields['roles']) && (count($fields['roles']) > 0)) ? $fields['roles'] : [];

        $content = new Table\Content([
            'type_id'    => $fields['type_id'],
            'parent_id'  => ($fields['content_parent_id'] != '----') ? $fields['content_parent_id'] : null,
            'title'      => $fields['title'],
            'uri'        => $fields['uri'],
            'slug'       => $fields['slug'],
            'publish'    => $publish,
            'expire'     => $expire,
            'status'     => (int)$fields['content_status'],
            'template'   => (($fields['content_template'] != 0) ? $fields['content_template'] : null),
            'roles'      => serialize($roles),
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

            $roles = (isset($fields['roles']) && is_array($fields['roles']) && (count($fields['roles']) > 0)) ? $fields['roles'] : [];

            $content->type_id    = $fields['type_id'];
            $content->parent_id  = ($fields['content_parent_id'] != '----') ? $fields['content_parent_id'] : null;
            $content->title      = $fields['title'];
            $content->uri        = $fields['uri'];
            $content->slug       = $fields['slug'];
            $content->publish    = $publish;
            $content->expire     = $expire;
            $content->status     = (int)$fields['content_status'];
            $content->template   = (($fields['content_template'] != 0) ? $fields['content_template'] : null);
            $content->roles      = serialize($roles);
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
     * Determine if child is ancestor of parent
     *
     * @param  int $id
     * @param  int $pid
     * @return boolean
     */
    public function isAncestor($id, $pid = null)
    {
        $result = false;

        while (null !== $pid) {
            if ($pid ==  $id) {
                $result = true;
                break;
            }
            $parent = Table\Content::findById($pid);
            if (isset($parent->id)) {
                $pid = $parent->parent_id;
            }
        }

        return $result;
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
     * Get content
     *
     * @param  Table\Content $content
     * @return void
     */
    protected function getContent(Table\Content $content)
    {
        $data = $content->getColumns();
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
        $data['roles']             = unserialize($data['roles']);

        $this->data = array_merge($this->data, $data);
    }

}
