<?php

namespace Content\Model;

use Content\Table;
use Phire\Model\AbstractModel;
use Pop\File\Dir;

class ContentType extends AbstractModel
{

    /**
     * Content Types
     * @var array
     */
    protected $contentTypes = [
        'text/html',
        'text/plain',
        'text/css',
        'text/javascript',
        'text/xml',
        'application/xml',
        'application/rss+xml',
        'application/json',
        'other'
    ];

    /**
     * Get all content types
     *
     * @param  int    $limit
     * @param  int    $page
     * @param  string $sort
     * @return array
     */
    public function getAll($limit = null, $page = null, $sort = null)
    {
        $order = (null !== $sort) ? $this->getSortOrder($sort, $page) : 'order ASC';

        if (null !== $limit) {
            $page = ((null !== $page) && ((int)$page > 1)) ?
                ($page * $limit) - $limit : null;

            return Table\ContentTypes::findAll(null, [
                'offset' => $page,
                'limit' => $limit,
                'order' => $order
            ])->rows();
        } else {
            return  Table\ContentTypes::findAll(null, [
                'order'  => $order
            ])->rows();
        }
    }

    /**
     * Get content type by ID
     *
     * @param  int $id
     * @return void
     */
    public function getById($id)
    {
        $type = Table\ContentTypes::findById($id);
        if (isset($type->id)) {
            $data = $type->getColumns();

            if (!in_array($data['content_type'], $this->contentTypes)) {
                $data['content_type_other'] = $data['content_type'];
                $data['content_type']       = 'other';
            }

            $this->data = array_merge($this->data, $data);
        }
    }

    /**
     * Save new content type
     *
     * @param  array $fields
     * @return void
     */
    public function save(array $fields)
    {
        $contentType = (!empty($fields['content_type_other']) && ($fields['content_type'] == 'other')) ?
            $fields['content_type_other'] : $fields['content_type'];

        $type = new Table\ContentTypes([
            'name'           => $fields['name'],
            'content_type'   => $contentType,
            'open_authoring' => (int)$fields['open_authoring'],
            'order'          => (int)$fields['order']
        ]);
        $type->save();

        $this->data = array_merge($this->data, $type->getColumns());
    }

    /**
     * Update an existing content type
     *
     * @param  array $fields
     * @return void
     */
    public function update(array $fields)
    {
        $type = Table\ContentTypes::findById($fields['id']);
        if (isset($type->id)) {
            $contentType = (!empty($fields['content_type_other']) && ($fields['content_type'] == 'other')) ?
                $fields['content_type_other'] : $fields['content_type'];

            $type->name           = $fields['name'];
            $type->content_type   = $contentType;
            $type->open_authoring = (int)$fields['open_authoring'];
            $type->order          = (int)$fields['order'];
            $type->save();

            $this->data = array_merge($this->data, $type->getColumns());
        }
    }

    /**
     * Remove a content type
     *
     * @param  array $fields
     * @return void
     */
    public function remove(array $fields)
    {
        if (isset($fields['rm_content_types'])) {
            foreach ($fields['rm_content_types'] as $id) {
                $type = Table\ContentTypes::findById((int)$id);
                if (isset($type->id)) {
                    $type->delete();
                }
            }
        }
    }

    /**
     * Determine if list of content types has pages
     *
     * @param  int $limit
     * @return boolean
     */
    public function hasPages($limit)
    {
        return (Table\ContentTypes::findAll()->count() > $limit);
    }

    /**
     * Get count of content types
     *
     * @return int
     */
    public function getCount()
    {
        return Table\ContentTypes::findAll()->count();
    }

}
