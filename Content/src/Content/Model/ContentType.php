<?php
/**
 * @namespace
 */
namespace Content\Model;

use Pop\Data\Type\Html;
use Content\Table;

class ContentType extends AbstractModel
{

    /**
     * Get all content types method
     *
     * @param  string $sort
     * @param  string $page
     * @return void
     */
    public function getAll($sort = null, $page = null)
    {
        $order = $this->getSortOrder($sort, $page);
        $types = Table\ContentTypes::findAll($order['field'] . ' ' . $order['order'], null, $order['limit'], $order['offset']);

        if ($this->data['acl']->isAuth('Content\Controller\Content\TypesController', 'remove')) {
            $removeCheckbox = '<input type="checkbox" name="remove_types[]" id="remove_types[{i}]" value="[{id}]" />';
            $removeCheckAll = '<input type="checkbox" id="checkall" name="checkall" value="remove_types" />';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Remove')
            );
        } else {
            $removeCheckbox = '&nbsp;';
            $removeCheckAll = '&nbsp;';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Remove'),
                'style' => 'display: none;'
            );
        }

        if ($this->data['acl']->isAuth('Content\Controller\Content\TypesController', 'edit')) {
            $edit = '<a class="edit-link" title="' . $this->i18n->__('Edit') . '" href="' . BASE_PATH . APP_URI . '/content/types/edit/[{id}]">Edit</a>';
        } else {
            $edit = null;
        }

        $options = array(
            'form' => array(
                'id'      => 'content-type-remove-form',
                'action'  => BASE_PATH . APP_URI . '/content/types/remove',
                'method'  => 'post',
                'process' => $removeCheckbox,
                'submit'  => $submit
            ),
            'table' => array(
                'headers' => array(
                    'id'      => '<a href="' . BASE_PATH . APP_URI . '/content/types?sort=id">#</a>',
                    'edit'    => '<span style="display: block; margin: 0 auto; width: 100%; text-align: center;">' . $this->i18n->__('Edit') . '</span>',
                    'name'    => '<a href="' . BASE_PATH . APP_URI . '/content/types?sort=name">' . $this->i18n->__('Name') . '</a>',
                    'process' => $removeCheckAll
                ),
                'class'       => 'data-table',
                'cellpadding' => 0,
                'cellspacing' => 0,
                'border'      => 0
            ),
            'separator' => '',
            'exclude'   => array('uri'),
            'indent'    => '        '
        );

        if (isset($types->rows[0])) {
            if (null !== $edit) {
                $typesAry = array();
                foreach ($types->rows as $type) {
                    $typesAry[] = array(
                        'id'    => $type->id,
                        'name'  => $type->name,
                        'order' => $type->order,
                        'edit'  => str_replace('[{id}]', $type->id, $edit)
                    );
                }
            } else {
                $typesAry = $types->rows;
            }
            $this->data['table'] = Html::encode($typesAry, $options, $this->config->pagination_limit, $this->config->pagination_range, Table\ContentTypes::getCount());
        }
    }

    /**
     * Get content type by ID method
     *
     * @param  int     $id
     * @return void
     */
    public function getById($id)
    {
        $type = Table\ContentTypes::findById($id);
        if (isset($type->id)) {
            $typeValues = $type->getValues();
            $typeValues = array_merge($typeValues, \Phire\Model\FieldValue::getAll($id));
            $this->data = array_merge($this->data, $typeValues);
        }
    }

    /**
     * Save content type
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function save(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $type = new Table\ContentTypes(array(
            'name'  => $fields['name'],
            'uri'   => (int)$fields['uri'],
            'order' => (int)$fields['order']
        ));

        $type->save();
        $this->data['id'] = $type->id;

        \Phire\Model\FieldValue::save($fields, $type->id);
    }

    /**
     * Update content type
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function update(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $type = Table\ContentTypes::findById($fields['id']);
        $type->name  = $fields['name'];
        $type->uri   = (int)$fields['uri'];
        $type->order = (int)$fields['order'];
        $type->update();

        $this->data['id'] = $type->id;

        \Phire\Model\FieldValue::update($fields, $type->id);
    }

    /**
     * Remove content type
     *
     * @param  array   $post
     * @return void
     */
    public function remove(array $post)
    {
        if (isset($post['remove_types'])) {
            foreach ($post['remove_types'] as $id) {
                $type = Table\ContentTypes::findById($id);
                if (isset($type->id)) {
                    if (!$type->uri) {
                        $content = Table\Content::findBy(array('type_id' => $type->id));
                        foreach ($content->rows as $c) {
                            $site = \Phire\Table\Sites::getSite((int)$c->site_id);
                            if (file_exists($site->document_root . $site->base_path . CONTENT_PATH . '/media/' . $c->uri) &&
                                !is_dir($site->document_root . $site->base_path . CONTENT_PATH . '/media/' . $c->uri)) {
                                \Phire\Model\Media::remove($c->uri, $site->document_root . $site->base_path);
                            }
                        }
                    }

                    \Phire\Table\Fields::deleteByType($type->id);
                    $type->delete();
                }

                \Phire\Model\FieldValue::remove($id);
            }
        }
    }

}

