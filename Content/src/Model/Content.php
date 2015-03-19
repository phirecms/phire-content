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
     * @param  int    $limit
     * @param  int    $page
     * @param  string $sort
     * @return array
     */
    public function getAll($limit = null, $page = null, $sort = null)
    {
        $order = $this->getSortOrder($sort, $page);

        if (null !== $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;

            return Table\Content::findBy(['type_id' => $this->tid], null, [
                'offset' => $page,
                'limit'  => $limit,
                'order'  => $order
            ])->rows();
        } else {
            return Table\Content::findBy(['type_id' => $this->tid], null, [
                'order'  => $order
            ])->rows();
        }
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
            $data = $content->getColumns();

            $type = new ContentType();
            $type->getById($data['type_id']);

            $data['content_type'] = $type->content_type;

            if (null !== $data['publish']) {
                $publish     = explode(' ', $data['publish']);
                $publishDate = explode('-', $publish[0]);
                $publishTime = explode('-', $publish[1]);

                $data['publish_month']  = $publishDate[1];
                $data['publish_day']    = $publishDate[2];
                $data['publish_year']   = $publishDate[0];
                $data['publish_hour']   = $publishTime[0];
                $data['publish_minute'] = $publishTime[1];

            }

            if (null !== $data['expire']) {
                $expire     = explode(' ', $data['expire']);
                $expireDate = explode('-', $expire[0]);
                $expireTime = explode('-', $expire[1]);

                $data['expire_month']  = $expireDate[1];
                $data['expire_day']    = $expireDate[2];
                $data['expire_year']   = $expireDate[0];
                $data['expire_hour']   = $expireTime[0];
                $data['expire_minute'] = $publishTime[1];
            }

            $this->data = array_merge($this->data, $data);
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
            $data = $content->getColumns();

            $type = new ContentType();
            $type->getById($data['type_id']);

            $data['content_type'] = $type->content_type;

            if (null !== $data['publish']) {
                $publish     = explode(' ', $data['publish']);
                $publishDate = explode('-', $publish[0]);
                $publishTime = explode('-', $publish[1]);

                $data['publish_month']  = $publishDate[1];
                $data['publish_day']    = $publishDate[2];
                $data['publish_year']   = $publishDate[0];
                $data['publish_hour']   = $publishTime[0];
                $data['publish_minute'] = $publishTime[1];

            }

            if (null !== $data['expire']) {
                $expire     = explode(' ', $data['expire']);
                $expireDate = explode('-', $expire[0]);
                $expireTime = explode('-', $expire[1]);

                $data['expire_month']  = $expireDate[1];
                $data['expire_day']    = $expireDate[2];
                $data['expire_year']   = $expireDate[0];
                $data['expire_hour']   = $expireTime[0];
                $data['expire_minute'] = $publishTime[1];
            }

            $this->data = array_merge($this->data, $data);
        }
    }

    /**
     * Save new content
     *
     * @param  array $fields
     * @return void
     */
    public function save(array $fields)
    {
        $publish = null;
        $expire  = null;

        if (isset($fields['publish_year']) && ($fields['publish_year'] != '----') && ($fields['publish_month'] != '--') && ($fields['publish_day'] != '--')) {
            $publish = $fields['publish_year'] . '-' . $fields['publish_month'] . '-' . $fields['publish_day'];
            $publish .= (($fields['publish_hour'] != '--') && ($fields['publish_minute'] != '--')) ?
                ' ' . $fields['publish_hour'] . ':' . $fields['publish_minute'] . ':00' : ' 00:00:00';
        } else {
            $publish = date('Y-m-d H:i:s');
        }

        if (isset($fields['expire_year']) && ($fields['expire_year'] != '----') && ($fields['expire_month'] != '--') && ($fields['expire_day'] != '--')) {
            $expire = $fields['expire_year'] . '-' . $fields['expire_month'] . '-' . $fields['expire_day'];
            $expire = (($fields['expire_hour'] != '--') && ($fields['expire_minute'] != '--')) ?
                ' ' . $fields['expire_hour'] . ':' . $fields['expire_minute'] . ':00' : ' 00:00:00';
        }

        $content = new Table\Content([
            'type_id' => $fields['type_id'],
            'title'   => $fields['title'],
            'uri'     => $fields['uri'],
            'publish' => $publish,
            'expire'  => $expire,
            'status'  => (int)$fields['status']
        ]);
        $content->save();

        $this->data = array_merge($this->data, $content->getColumns());
    }

    /**
     * Update an existing content
     *
     * @param  array $fields
     * @return void
     */
    public function update(array $fields)
    {
        $content = Table\Content::findById($fields['id']);
        if (isset($content->id)) {
            $publish = null;
            $expire  = null;

            if (isset($fields['publish_year']) && ($fields['publish_year'] != '----') && ($fields['publish_month'] != '--') && ($fields['publish_day'] != '--')) {
                $publish = $fields['publish_year'] . '-' . $fields['publish_month'] . '-' . $fields['publish_day'];
                $publish .= (($fields['publish_hour'] != '--') && ($fields['publish_minute'] != '--')) ?
                    ' ' . $fields['publish_hour'] . ':' . $fields['publish_minute'] . ':00' : ' 00:00:00';
            } else {
                $publish = date('Y-m-d H:i:s');
            }

            if (isset($fields['expire_year']) && ($fields['expire_year'] != '----') && ($fields['expire_month'] != '--') && ($fields['expire_day'] != '--')) {
                $expire = $fields['expire_year'] . '-' . $fields['expire_month'] . '-' . $fields['expire_day'];
                $expire = (($fields['expire_hour'] != '--') && ($fields['expire_minute'] != '--')) ?
                    ' ' . $fields['expire_hour'] . ':' . $fields['expire_minute'] . ':00' : ' 00:00:00';
            }

            $content->type_id = $fields['type_id'];
            $content->title   = $fields['title'];
            $content->uri     = $fields['uri'];
            $content->publish = $publish;
            $content->expire  = $expire;
            $content->status  = (int)$fields['status'];
            $content->save();

            $this->data = array_merge($this->data, $content->getColumns());
        }
    }

    /**
     * Remove a content
     *
     * @param  array $fields
     * @return void
     */
    public function remove(array $fields)
    {
        if (isset($fields['rm_content'])) {
            foreach ($fields['rm_content'] as $id) {
                $content = Table\Content::findById((int)$id);
                if (isset($content->id)) {
                    $content->delete();
                }
            }
        }
    }

    /**
     * Determine if list of libraries has pages
     *
     * @param  int $limit
     * @return boolean
     */
    public function hasPages($limit)
    {
        return (Table\Content::findAll()->count() > $limit);
    }

    /**
     * Get count of libraries
     *
     * @return int
     */
    public function getCount()
    {
        return Table\Content::findAll()->count();
    }

    /**
     * Add content types to models of the module config for the application
     *
     * @param  \Phire\Application $application
     * @return void
     */
    public static function addModels(\Phire\Application $application)
    {
        $resources = $application->config()['resources'];
        $params    = $application->services()->getParams('nav.phire');
        $types = \Content\Table\ContentTypes::findAll(null, ['order' => 'order ASC']);

        foreach ($types->rows() as $type) {
            if (isset($config['models']) && isset($config['models']['Content\Model\Content'])) {
                $config['models']['Content\Model\Content'][] = [
                    'type_field' => 'type_id',
                    'type_value' => $type->id,
                    'type_name'  => $type->name
                ];

                $resources['content-type-' . $type->id . '|content-type-' . str_replace(' ', '-', strtolower($type->name))] = [
                    'index', 'add', 'edit', 'remove'
                ];

                if (!isset($params['tree']['content']['children'])) {
                    $params['tree']['content']['children'] = [];
                }

                $params['tree']['content']['children']['content-type-' . $type->id] = [
                        'name' => $type->name,
                        'href' => '/content/' . $type->id,
                        'acl'  => [
                            'resource'   => 'content-type-' . $type->id,
                            'permission' => 'index'
                        ]
                ];
            }
        }

        $application->mergeConfig(['resources' => $resources]);
        $application->services()->setParams('nav.phire', $params);
    }

}
