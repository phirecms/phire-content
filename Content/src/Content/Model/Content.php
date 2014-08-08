<?php
/**
 * @namespace
 */
namespace Content\Model;

use Pop\Archive\Archive;
use Pop\Data\Type\Html;
use Pop\File\Dir;
use Pop\File\File;
use Pop\Web\Session;
use Content\Table;

class Content extends AbstractModel
{

    /**
     * Constant for expired
     */
    const EXPIRED = 4;

    /**
     * Constant for scheduled
     */
    const SCHEDULED = 3;

    /**
     * Constant for published
     */
    const PUBLISHED = 2;

    /**
     * Constant for draft
     */
    const DRAFT = 1;

    /**
     * Constant for unpublished
     */
    const UNPUBLISHED = 0;

    /**
     * Constant for trash
     */
    const TRASH = -1;

    /**
     * Constant for removal
     */
    const REMOVE = -2;

    /**
     * Instantiate the model object.
     *
     * @param  array $data
     * @return self
     */
    public function __construct(array $data = null)
    {
        parent::__construct($data);

        $this->config->open_authoring    = \Phire\Table\Config::findById('open_authoring')->value;
        $this->config->feed_type         = \Phire\Table\Config::findById('feed_type')->value;
        $this->config->feed_limit        = \Phire\Table\Config::findById('feed_limit')->value;
        $this->config->incontent_editing = \Phire\Table\Config::findById('incontent_editing')->value;
    }

    /**
     * Method to check is content object is allowed
     *
     * @param  mixed $content
     * @return boolean
     */
    public static function isAllowed($content)
    {
        $sess = Session::getInstance();
        $user = (isset($sess->user)) ? $sess->user : null;

        // Get any content roles
        $rolesAry = array();

        if (isset($content->title)) {
            $roles = (null !== $content->roles) ? unserialize($content->roles) : array();
            foreach ($roles as $id) {
                $rolesAry[] = $id;
            }
        }

        // If there are no roles, or the user's role is allowed
        if ((count($rolesAry) == 0) || ((count($rolesAry) > 0) && (null !== $user) && in_array($user['role_id'], $rolesAry))) {
            $allowed = true;
        // Else, not allowed
        } else {
            $allowed = false;
        }

        // Check if the content is published, a draft or expired
        if (isset($content->title) && (null !== $content->status)) {
            // If a regular URI type
            if ((strtotime($content->publish) >= time()) ||
                ((null !== $content->expire) && ($content->expire != '0000-00-00 00:00:00') && (strtotime($content->expire) <= time()))) {
                $allowed = false;
            }

            // Published status override
            if (((int)$content->status == self::UNPUBLISHED) || ((int)$content->status == self::TRASH)) {
                $allowed = false;
            } else if ((int)$content->status == self::DRAFT) {
                $allowed = (isset($sess->user) && (strtolower($sess->user->type) == 'user'));
            }

            $site = \Phire\Table\Sites::getSite();
            if ((int)$content->site_id !== (int)$site->id)  {
                $allowed = false;
            }
        }

        return $allowed;
    }

    /**
     * Get all content types method
     *
     * @return array
     */
    public function getContentTypes()
    {
        $types = Table\ContentTypes::findAll('order ASC');
        return $types->rows;
    }

    /**
     * Get recent content method
     *
     * @param  int  $limit
     * @return array
     */
    public function getRecent($limit = 10)
    {
        $sql = Table\Content::getSql();
        $sql->select(array(
            DB_PREFIX . 'content.id',
            DB_PREFIX . 'content.site_id',
            DB_PREFIX . 'content.type_id',
            DB_PREFIX . 'content_types.name',
            'type_uri' => DB_PREFIX . 'content_types.uri',
            DB_PREFIX . 'content.title',
            DB_PREFIX . 'content.uri',
            DB_PREFIX . 'content.created',
            DB_PREFIX . 'content.created_by',
            'user_id' => DB_PREFIX . 'users.id',
            DB_PREFIX . 'users.username',
            DB_PREFIX . 'content.status',
            DB_PREFIX . 'content.publish',
            DB_PREFIX . 'content.expire'
        ))->join(DB_PREFIX . 'content_types', array('type_id', 'id'), 'LEFT JOIN')
          ->join(DB_PREFIX . 'users', array('created_by', 'id'), 'LEFT JOIN')
          ->orderBy(DB_PREFIX . 'content.created', 'DESC')
          ->orderBy(DB_PREFIX . 'content.id', 'DESC')
          ->limit((int)$limit);

        $sql->select()->where()->notEqualTo('status', ':status')->isNull('status', 'OR');
        $content = Table\Content::execute($sql->render(true), array('status' => self::TRASH));

        foreach ($content->rows as $key => $value) {
            $site = \Phire\Table\Sites::getSite((int)$value->site_id);
            $content->rows[$key]->domain    = $site->domain;
            $content->rows[$key]->base_path = $site->base_path;
        }

        return $content->rows;
    }

    /**
     * Get all content method
     *
     * @param  int     $typeId
     * @param  string  $sort
     * @param  string  $page
     * @param  boolean $trash
     * @return void
     */
    public function getAll($typeId, $sort = null, $page = null, $trash = false)
    {
        $sess        = Session::getInstance();
        $order       = $this->getSortOrder($sort, $page, 'DESC');
        $contentType = Table\ContentTypes::findById($typeId);

        $sql = Table\Content::getSql();
        $order['field'] = ($order['field'] == 'id') ? DB_PREFIX . 'content.id' : $order['field'];

        $searchString = null;
        if (isset($_GET['search_title'])) {
            $searchString = '&site_search=' . $_GET['site_search'] . '&cat_search=' . $_GET['cat_search'] . '&search_title=' . $_GET['search_title'];
            if (isset($_GET['nav_search'])) {
                $searchString .= '&nav_search=' . $_GET['nav_search'];
            };
        }

        $sql->select(array(
            DB_PREFIX . 'content.id',
            DB_PREFIX . 'content.parent_id',
            DB_PREFIX . 'content.site_id',
            DB_PREFIX . 'content.type_id',
            DB_PREFIX . 'content_types.name',
            'type_uri' => DB_PREFIX . 'content_types.uri',
            DB_PREFIX . 'content.title',
            DB_PREFIX . 'content.uri',
            DB_PREFIX . 'content.publish',
            DB_PREFIX . 'content.expire',
            DB_PREFIX . 'content.created',
            DB_PREFIX . 'content.created_by',
            DB_PREFIX . 'content.updated',
            DB_PREFIX . 'content.updated_by',
            'user_id' => DB_PREFIX . 'users.id',
            DB_PREFIX . 'users.username',
            DB_PREFIX . 'content.status'
        ))->join(DB_PREFIX . 'content_types', array('type_id', 'id'), 'LEFT JOIN')
          ->join(DB_PREFIX . 'users', array('created_by', 'id'), 'LEFT JOIN')
          ->orderBy($order['field'], $order['order']);

        $sql->select()->where()->equalTo(DB_PREFIX . 'content.type_id', ':type_id');
        $params = array('type_id' => $typeId);

        if ($contentType->uri) {
            if ($trash) {
                $sql->select()->where()->equalTo(DB_PREFIX . 'content.status', ':status');
                $params['status'] = -1;
            } else {
                $sql->select()->where()->notEqualTo(DB_PREFIX . 'content.status', ':status');
                $params['status'] = -1;
            }
        }

        if (isset($_GET['search_title']) && !empty($_GET['search_title'])) {
            $sql->select()->where()->like(DB_PREFIX . 'content.title', ':title');
            $params['title'] = '%' . strip_tags($_GET['search_title']) . '%';
            $this->data['searchTitle'] = htmlentities(strip_tags($_GET['search_title']), ENT_QUOTES, 'UTF-8');
        } else {
            $this->data['searchTitle'] = null;
        }

        $siteMarked = null;
        $catMarked  = null;
        $navMarked  = null;
        $count      = null;

        if (isset($_GET['site_search'])) {
            if ($_GET['site_search'] != '--') {
                $sql->select()->where()->equalTo(DB_PREFIX . 'content.site_id', ':site_id');
                $siteMarked = (int)$_GET['site_search'];
                $params['site_id'] = $siteMarked;
            }

            if ($_GET['cat_search'] != '--') {
                $sql->select()->join(DB_PREFIX . 'content_to_categories', array('id', 'content_id'), 'LEFT JOIN');
                $sql->select()->where()->equalTo(DB_PREFIX . 'content_to_categories.category_id', ':category_id');
                $catMarked = (int)$_GET['cat_search'];
                $params['category_id'] = $catMarked;
            }

            if (isset($_GET['nav_search']) && ($_GET['nav_search'] != '--')) {
                $sql->select()->join(DB_PREFIX . 'content_to_navigation', array('id', 'content_id'), 'LEFT JOIN');
                $sql->select()->where()->equalTo(DB_PREFIX . 'content_to_navigation.navigation_id', ':navigation_id');
                $navMarked = (int)$_GET['nav_search'];
                $params['navigation_id'] = $navMarked;
            }

            $contentCount = Table\Content::execute($sql->render(true), $params);
            $count = $contentCount->count();
        }

        if (null !== $order['limit']) {
            $sql->select()->limit($order['limit'])
                          ->offset($order['offset']);
        }

        $content = Table\Content::execute($sql->render(true), $params);
        $this->data['type'] = $contentType->name;

        if (($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'process')) &&
            ($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'process_' . $typeId))) {
            $removeCheckbox = '<input type="checkbox" name="process_content[]" value="[{id}]" id="process_content[{i}]" />';
            $removeCheckAll = '<input type="checkbox" id="checkall" name="checkall" value="process_content" />';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Process'),
                'style' => 'float: right;'
            );
        } else {
            $removeCheckbox = '&nbsp;';
            $removeCheckAll = '&nbsp;';
            $submit = array(
                'class' => 'remove-btn',
                'value' => $this->i18n->__('Process'),
                'style' => 'display: none;'
            );
        }

        // Set headers based on URI or file
        if ($contentType->uri) {
            $headers = array(
                'id'           => '<a href="' . BASE_PATH . APP_URI . '/content/index/' . $typeId . '?sort=id' . $searchString . '">#</a>',
                'title'        => '<a href="' . BASE_PATH . APP_URI . '/content/index/' . $typeId . '?sort=title' . $searchString . '">' . $this->i18n->__('Title') . '</a>',
                'created_date' => '<a href="' . BASE_PATH . APP_URI . '/content/index/' . $typeId . '?sort=created' . $searchString . '">' . $this->i18n->__('Created') . '</a>',
                'updated_date' => '<a href="' . BASE_PATH . APP_URI . '/content/index/' . $typeId . '?sort=updated' . $searchString . '">' . $this->i18n->__('Updated') . '</a>',
                'status'       => '<a href="' . BASE_PATH . APP_URI . '/content/index/' . $typeId . '?sort=status' . $searchString . '">' . $this->i18n->__('Status') . '</a>',
                'uri'          => $this->i18n->__('URI') . ' (' . $this->i18n->__('Click to View') . ')',
                'username'     => $this->i18n->__('Author'),
                'edit'         => '<span style="display: block; margin: 0 auto; width: 100%; text-align: center;">' . $this->i18n->__('Edit') . '</span>',
                'copy'         => '<span style="display: block; margin: 0 auto; width: 100%; text-align: center;">' . $this->i18n->__('Copy') . '</span>',
                'process'      => $removeCheckAll
            );
        } else {
            $headers = array(
                'id'           => '<a href="' . BASE_PATH . APP_URI . '/content/index/' . $typeId . '?sort=id' . $searchString . '">#</a>',
                'title'        => '<a href="' . BASE_PATH . APP_URI . '/content/index/' . $typeId . '?sort=title' . $searchString . '">' . $this->i18n->__('Title') . '</a>',
                'created_date' => '<a href="' . BASE_PATH . APP_URI . '/content/index/' . $typeId . '?sort=created' . $searchString . '">' . $this->i18n->__('Created') . '</a>',
                'updated_date' => '<a href="' . BASE_PATH . APP_URI . '/content/index/' . $typeId . '?sort=updated' . $searchString . '">' . $this->i18n->__('Updated') . '</a>',
                'username'     => $this->i18n->__('Author'),
                'status'       => $this->i18n->__('File'),
                'size'         => $this->i18n->__('Size'),
                'uri'          => $this->i18n->__('URI') . ' (' . $this->i18n->__('Click to View') . ')',
                'edit'         => '<span style="display: block; margin: 0 auto; width: 100%; text-align: center;">' . $this->i18n->__('Edit') . '</span>',
                'process'      => $removeCheckAll
            );
        }

        $options = array(
            'form' => array(
                'id'      => 'content-remove-form',
                'action'  => BASE_PATH . APP_URI . '/content/process/' . $typeId,
                'method'  => 'post',
                'process' => $removeCheckbox,
                'submit'  => $submit
            ),
            'table' => array(
                'headers'     => $headers,
                'class'       => 'data-table',
                'cellpadding' => 0,
                'cellspacing' => 0,
                'border'      => 0
            ),
            'separator' => '',
            'date'      => 'M j, Y',
            'exclude'   => array(
                'site_id', 'parent_id', 'type_id', 'type_uri', 'name', 'order', 'created_by', 'updated_by', 'user_id', 'publish', 'expire'
            ),
            'indent'    => '        '
        );


        $this->data['title']   = (isset($contentType->id)) ? $contentType->name : null;
        $this->data['type']    = $contentType->name;
        $this->data['typeUri'] = $contentType->uri;

        if ($trash) {
            $this->data['title'] .= ' ' . $this->config->separator . ' ' . $this->i18n->__('Trash');
        }

        $status = array(
            '-1' =>  '<strong class="error">' . $this->i18n->__('Trash') . '</strong>',
            '0'  => '<strong class="error">' . $this->i18n->__('Unpublished') . '</strong>',
            '1'  => '<strong class="orange">' . $this->i18n->__('Draft') . '</strong>',
            '2'  => '<strong class="green">' . $this->i18n->__('Published') . '</strong>',
            '3'  => '<strong class="blue">' . $this->i18n->__('Scheduled') . '</strong>',
            '4'  => '<strong class="red">' . $this->i18n->__('Expired') . '</strong>',
        );
        $contentAry = array();
        $ids = array();

        foreach ($content->rows as $content) {
            $c = (array)$content;
            $site = \Phire\Table\Sites::getSite((int)$c['site_id']);
            $domain   = $site->domain;
            $basePath = $site->base_path;
            $docRoot  = $site->document_root;

            // Track open authoring
            if ((!$this->config->open_authoring) && ($c['created_by'] != $this->user->id)) {
                $ids[] = $c['id'];
                $c['edit'] = '&nbsp;';
            } else {
                if (($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'edit')) &&
                    ($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'edit_' . $typeId))) {
                    $c['edit'] = '<a class="edit-link" title="' . $this->i18n->__('Edit') . '" href="http://' . $_SERVER['HTTP_HOST'] . BASE_PATH . APP_URI . '/content/edit/' . $c['id'] . '">Edit</a>';
                } else {
                    $c['edit'] = '&nbsp;';
                }
            }

            if ((!$this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'edit')) ||
                (!$this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'edit_' . $typeId))) {
                unset($c['edit']);
            }

            // Adjust URI link based on URI or file
            if ((substr($c['uri'], 0, 1) == '/') || (substr($c['uri'], 0, 4) == 'http')) {
                if (isset($c['status'])) {
                    if ($c['status'] == self::PUBLISHED) {
                        if ((null !== $c['publish']) && (strtotime($c['publish']) > time())) {
                            $c['status'] = $status[self::SCHEDULED];
                        } else if ((null !== $c['expire']) && (strtotime($c['expire']) <= time())) {
                            $c['status'] = $status[self::EXPIRED];
                        } else {
                            $c['status'] = $status[self::PUBLISHED];
                        }
                    } else {
                        $c['status'] = $status[$c['status']];
                    }
                } else {
                    $c['status'] = '';
                }

                if (substr($c['uri'], 0, 4) != 'http') {
                    $c['uri'] = '<a href="http://' . $domain . $basePath . $c['uri'] . '" target="_blank">http://' . $domain . $basePath . $c['uri'] . '</a>';
                } else {
                    $c['uri'] = '<a href="' . $c['uri'] . '" target="_blank">' . $c['uri'] . '</a>';
                }
            } else {
                $fileInfo = \Phire\Model\Media::getFileIcon($c['uri'], $docRoot . $basePath . CONTENT_PATH . '/media');
                $c['status'] = '<a href="http://' . $domain . $basePath . CONTENT_PATH . '/media/' . $c['uri'] . '" target="_blank"><img src="http://' . $domain . $basePath . CONTENT_PATH . $fileInfo['fileIcon'] . '" width="32" /></a>';
                $c['size'] = $fileInfo['fileSize'];
                $c['uri'] = '<a href="http://' . $domain . $basePath . CONTENT_PATH . '/media/' . $c['uri'] . '" target="_blank">http://' . $domain . $basePath . CONTENT_PATH . '/media/' . $c['uri'] . '</a>';
            }
            $c['created_date'] = $c['created'];
            $c['updated_date'] = $c['updated'];
            // Add copy link
            if (($contentType->uri) && ($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'copy')) &&
                ($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'copy_' . $typeId))) {
                $c['copy'] = '<a class="copy-link" title="' . $this->i18n->__('Copy') . '" href="' . BASE_PATH . APP_URI . '/content/copy/' . $c['id'] . '">' . $this->i18n->__('Copy') . '</a>';
            }

            if ($trash) {
                unset($c['copy']);
                unset($c['edit']);
            }

            if (in_array($c['site_id'], $sess->user->site_ids)) {
               unset($c['created']);
                $cAry = array(
                    'id'   => $c['id']
                );

                $cAry['parent_id']    = $c['parent_id'];
                $cAry['type_id']      = $c['type_id'];
                $cAry['name']         = $c['name'];
                $cAry['type_uri']     = $c['type_uri'];
                $cAry['title']        = $c['title'];
                $cAry['uri']          = $c['uri'];
                $cAry['publish']      = $c['publish'];
                $cAry['expire']       = $c['expire'];
                $cAry['created_by']   = $c['created_by'];
                $cAry['updated_by']   = $c['updated_by'];
                $cAry['user_id']      = $c['user_id'];
                $cAry['site_id']      = $c['site_id'];
                $cAry['username']     = $c['username'];
                $cAry['status']       = $c['status'];
                $cAry['created_date'] = $c['created_date'];
                $cAry['updated_date'] = $c['updated_date'];

                if (isset($c['size'])) {
                    $cAry['size'] = $c['size'];
                }

                if (isset($c['edit'])) {
                    $cAry['edit'] = $c['edit'];
                }

                if (isset($c['copy'])) {
                    $cAry['copy'] = $c['copy'];
                }

                $contentAry[] = $cAry;
            }
        }

        $trashCount = Table\Content::getCount(array('type_id' => $typeId, 'status' => -1));

        if (null === $count) {
            $count = Table\Content::getCount(array('type_id' => $typeId));
            if ($trash) {
                $count = $trashCount;
            } else {
                $count -= $trashCount;
            }
        }

        if (isset($contentAry[0])) {
            $table = Html::encode($contentAry, $options, $this->config->pagination_limit, $this->config->pagination_range, $count);
            if (($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'process')) &&
                ($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'process_' . $typeId))) {
                // If there are open authoring ids, remove "remove" checkbox
                if (count($ids) > 0) {
                    foreach ($ids as $id) {
                        $rm = substr($table, strpos($table, '<input type="checkbox" name="process_content[]" value="' . $id . '" id="process_content'));
                        $rm = substr($rm, 0, (strpos($rm, ' />') + 3));
                        $table = str_replace($rm, '&nbsp;', $table);
                    }
                }
            }
            if ($this->data['typeUri']) {
                $contentProcess = '<select name="content_process" id="content-process"><option value="2">' . $this->i18n->__('Publish') . '</option><option value="1">' . $this->i18n->__('Draft') . '</option><option value="0">' . $this->i18n->__('Unpublish') . '</option><option value="-1">' . $this->i18n->__('Trash') . '</option><option value="-2">' . $this->i18n->__('Remove') . '</option></select>';
                if (($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'trash')) &&
                    ($this->data['acl']->isAuth('Content\Controller\Content\IndexController', 'trash_' . $typeId))) {
                    if (($trashCount > 0) && (!$trash)) {
                        $contentProcess .= '<div id="trash"><a href="' . BASE_PATH . APP_URI . '/content/trash/' . $typeId . '">' . $this->i18n->__('Trash') . ' (' . $trashCount . ')</a></div>';
                    }
                }
            } else {
                $contentProcess = '<input type="hidden" name="content_process" id="content-process" value="-2" />';
            }
            $this->data['table'] = str_replace('value="' . $this->i18n->__('Process') . '" style="float: right;" />', 'value="' . $this->i18n->__('Process') . '" style="float: right;" />' . $contentProcess, $table);
        }

        $sites    = \Phire\Table\Sites::findAll();
        $cats     = Table\Categories::findAll();
        $navs     = Table\Navigation::findAll();
        $siteAry  = array('--' => '(' . $this->i18n->__('All Sites') . ')');
        $catAry   = array('--' => '(' . $this->i18n->__('All Categories') . ')');
        $navAry   = array('--' => '(' . $this->i18n->__('All Navigation') . ')');

        if (in_array(0, $this->user->site_ids)) {
            $siteAry[0] = $_SERVER['HTTP_HOST'];
        }

        foreach ($sites->rows as $site) {
            if (in_array($site->id, $this->user->site_ids)) {
                $siteAry[$site->id] = $site->domain;
            }
        }

        foreach ($cats->rows as $cat) {
            $catAry[$cat->id] = $cat->title;
        }

        foreach ($navs->rows as $nav) {
            $navAry[$nav->id] = $nav->navigation;
        }

        $this->data['siteSearch'] = new \Pop\Form\Element\Select('site_search', $siteAry, $siteMarked);
        $this->data['catSearch']  = new \Pop\Form\Element\Select('cat_search', $catAry, $catMarked);
        $this->data['navSearch']  = ($contentType->uri) ? new \Pop\Form\Element\Select('nav_search', $navAry, $navMarked) : null;
    }

    /**
     * Get content by URI method
     *
     * @param  string  $uri
     * @return void
     */
    public function getByUri($uri)
    {
        $sql = Table\Content::getSql();
        $sql->select(array(
            0          => DB_PREFIX . 'content.id',
            1          => DB_PREFIX . 'content.site_id',
            2          => DB_PREFIX . 'content.type_id',
            3          => DB_PREFIX . 'content.parent_id',
            4          => DB_PREFIX . 'content.template',
            5          => DB_PREFIX . 'content.title',
            'uri'      => DB_PREFIX . 'content.uri',
            7          => DB_PREFIX . 'content.slug',
            8          => DB_PREFIX . 'content.feed',
            9          => DB_PREFIX . 'content.force_ssl',
            10         => DB_PREFIX . 'content.status',
            11         => DB_PREFIX . 'content.roles',
            12         => DB_PREFIX . 'content.created',
            13         => DB_PREFIX . 'content.updated',
            14         => DB_PREFIX . 'content.publish',
            15         => DB_PREFIX . 'content.expire',
            16         => DB_PREFIX . 'content.created_by',
            17         => DB_PREFIX . 'content.updated_by',
            'type_uri' => DB_PREFIX . 'content_types.uri'
        ))->where()->equalTo(DB_PREFIX . 'content.uri', ':uri');

        $sql->select()->join(DB_PREFIX . 'content_types', array('type_id', 'id'), 'LEFT JOIN');
        $content = Table\Content::execute($sql->render(true), array('uri' => $uri));

        if (isset($content->rows[0])) {
            $contentMatch = null;
            $site = \Phire\Table\Sites::findBy(array('document_root' => $_SERVER['DOCUMENT_ROOT']));
            $siteId = (isset($site->id)) ? (int)$site->id : 0;
            foreach ($content->rows as $content) {
                if ((int)$content->site_id == $siteId) {
                    $this->data['allowed'] = self::isAllowed($content);
                    $contentValues = (array)$content;
                    $contentValues['title'] = html_entity_decode($contentValues['title'], ENT_QUOTES, 'UTF-8');
                    $fieldValues = \Phire\Model\FieldValue::getAll($content->id, true);
                    foreach ($fieldValues as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                if (is_array($v)) {
                                    foreach ($v as $ky => $vl) {
                                        $v[$ky] = html_entity_decode($vl, ENT_QUOTES, 'UTF-8');
                                    }
                                    $value[$k] = $v;
                                } else {
                                    $value[$k] = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
                                }
                            }
                            $fieldValues[$key] = $value;
                        } else {
                            $fieldValues[$key] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                        }
                    }
                    $contentValues = array_merge($contentValues, $fieldValues);
                    $this->data = array_merge($this->data, $contentValues);
                }
            }
            $this->filterContent();
        }
    }

    /**
     * Get content by date method
     *
     * @param  array   $date
     * @param  string  $page
     * @return void
     */
    public function getByDate($date, $page = null)
    {
        $this->data['date'] = $date['match'];

        $order   = $this->getSortOrder(null, $page);
        $content = Table\Content::findByDate($date);

        if (empty($date['uri'])) {
            $items = $content->rows;
            foreach ($items as $key => $item) {
                if (self::isAllowed($item)) {
                    $item->title = html_entity_decode($item->title, ENT_QUOTES, 'UTF-8');
                    $fv = \Phire\Model\FieldValue::getAll($item->id, true);
                    if (count($fv) > 0) {
                        foreach ($fv as $k => $v) {
                            if (is_array($v)) {
                                foreach ($v as $ky => $vl) {
                                    $v[$ky] = html_entity_decode($vl, ENT_QUOTES, 'UTF-8');
                                }
                                $items[$key]->{$k} = $v;
                            } else {
                                $items[$key]->{$k} = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
                            }
                        }
                    }
                } else {
                    unset($items[$key]);
                }
            }

            $count = count($items);

            if ($count > (int)$order['limit']) {
                $items = array_slice($items, $order['offset'], $order['limit']);
                $pg = new \Pop\Paginator\Paginator($items, $this->config->pagination_limit, $this->config->pagination_range, $count);
                $this->data['page_links'] = implode('', $pg->getLinks(((null !== $page) ? $page : 1)));
            } else {
                $this->data['page_links'] = null;
            }

            $this->data['noitems'] = array();

            if (count($items) == 0) {
                $this->data['noitems'][] = 1;
            }

            $this->data['items'] = $items;
        } else if (isset($content->id)) {
            $this->data['allowed'] = self::isAllowed($content);
            $contentValues = $content->getValues();
            $contentValues['title'] = html_entity_decode($contentValues['title'], ENT_QUOTES, 'UTF-8');
            $fieldValues = \Phire\Model\FieldValue::getAll($content->id, true);
            foreach ($fieldValues as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $ky => $vl) {
                        $value[$ky] = html_entity_decode($vl, ENT_QUOTES, 'UTF-8');
                    }
                    $fieldValues[$key] = $value;
                } else {
                    $fieldValues[$key] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                }
            }
            $contentValues = array_merge($contentValues, $fieldValues);
            $this->data = array_merge($this->data, $contentValues);
            $this->filterContent();
        }
    }

    /**
     * Search for content
     *
     * @param  \Pop\Http\Request $request
     * @param  string            $page
     * @return void
     */
    public function search($request, $page = null)
    {
        $this->data['keys']  = array();
        $this->data['items'] = array();

        $track = array();
        $order = $this->getSortOrder(null, $page);

        // Get search keys
        if ($request->isPost()) {
            $this->data['keys'] = array_keys($request->getPost());
            $search = $request->getPost();
        } else {
            $this->data['keys'] = array_keys($request->getQuery());
            $search = $request->getQuery();
        }

        // Perform search
        if (count($this->data['keys']) > 0) {
            $items = array();

            // If just a search by content title
            if (isset($search['title'])) {
                $sql = Table\Content::getSql();
                $sql->select()->where()->like('title', ':title');
                $params = array('title' => '%' . $search['title'] . '%');
                if (isset($search['type_id'])) {
                    $sql->select()->where()->equalTo('type_id', ':type_id');
                    $params['type_id'] = $search['type_id'];
                }
                $content = Table\Content::execute($sql->render(true), $params);
                $items = $content->rows;
            }

            foreach ($this->data['keys'] as $key) {
                if (isset($search[$key]) && ($search[$key] != '')) {
                    $field = \Phire\Table\Fields::findBy(array('name' => $key));
                    if (isset($field->id)) {
                        $sql = \Phire\Table\FieldValues::getSql();
                        $sql->select(array(
                            DB_PREFIX . 'field_values.field_id',
                            DB_PREFIX . 'field_values.model_id',
                            DB_PREFIX . 'field_values.value'
                        ));
                        $sql->select()
                            ->where()
                            ->equalTo(DB_PREFIX . 'field_values.field_id', ':field_id')->like('value', ':value');

                        // Execute field values SQL
                        $fieldValues = \Phire\Table\FieldValues::execute(
                            $sql->render(true),
                            array(
                                'field_id' => $field->id,
                                'value' => '%' . $search[$key] . '%'
                            )
                        );

                        // If field values are found, extrapolate the table class from the model class
                        if (isset($fieldValues->rows[0])) {
                            foreach ($fieldValues->rows as $fv) {
                                // If table class is found, find model object
                                if (!in_array($fv->model_id, $track)) {
                                    $cont = Table\Content::findById($fv->model_id);
                                    if (isset($cont->id)) {
                                        $items[] = $cont;
                                        $track[] = $fv->model_id;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach ($items as $key => $item) {
                if (self::isAllowed($item)) {
                    if (isset($item['title'])) {
                        $item['title'] = html_entity_decode($item['title'], ENT_QUOTES, 'UTF-8');
                    }
                    $fv = \Phire\Model\FieldValue::getAll($item->id, true);
                    if (count($fv) > 0) {
                        foreach ($fv as $k => $v) {
                            if (is_array($v)) {
                                foreach ($v as $ky => $vl) {
                                    $v[$ky] = html_entity_decode($vl, ENT_QUOTES, 'UTF-8');
                                }
                                $items[$key]->{$k} = $v;
                            } else {
                                $items[$key]->{$k} = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
                            }
                        }
                    }
                } else {
                    unset($items[$key]);
                }
            }

            $count = count($items);

            if ($count > (int)$order['limit']) {
                $items = array_slice($items, $order['offset'], $order['limit']);
                $pg = new \Pop\Paginator\Paginator($items, $this->config->pagination_limit, $this->config->pagination_range, $count);
                $this->data['page_links'] = implode('', $pg->getLinks(((null !== $page) ? $page : 1)));
            } else {
                $this->data['page_links'] = null;
            }

            $this->data['noitems'] = array();

            if (count($items) == 0) {
                $this->data['noitems'][] = 1;
            }

            $this->data['items'] = $items;
        } else {
            $this->data['noitems'] = array(1);
        }
    }

    /**
     * Get content by ID method
     *
     * @param  int     $id
     * @return void
     */
    public function getById($id)
    {
        $content = Table\Content::findById($id);
        if (isset($content->id)) {
            $type = Table\ContentTypes::findById($content->type_id);

            $contentValues = $content->getValues();
            $contentValues['type_name'] = (isset($type->id) ? $type->name : null);
            $contentValues['content_title'] = $contentValues['title'];
            $contentValues['full_uri'] = $contentValues['uri'];
            $contentValues['uri'] = $contentValues['slug'];
            unset($contentValues['title']);
            unset($contentValues['slug']);

            $publishAry = explode(' ', $contentValues['publish']);
            $dateAry = explode('-', $publishAry[0]);
            $timeAry = explode(':', $publishAry[1]);

            $contentValues['publish_month'] = $dateAry[1];
            $contentValues['publish_day'] = $dateAry[2];
            $contentValues['publish_year'] = $dateAry[0];
            $contentValues['publish_hour'] = $timeAry[0];
            $contentValues['publish_minute'] = $timeAry[1];

            if ((null !== $contentValues['expire']) && ($contentValues['expire'] != '0000-00-00 00:00:00')) {
                $expireAry = explode(' ', $contentValues['expire']);
                $dateAry = explode('-', $expireAry[0]);
                $timeAry = explode(':', $expireAry[1]);

                $contentValues['expire_month'] = $dateAry[1];
                $contentValues['expire_day'] = $dateAry[2];
                $contentValues['expire_year'] = $dateAry[0];
                $contentValues['expire_hour'] = $timeAry[0];
                $contentValues['expire_minute'] = $timeAry[1];
            }

            $cats = Table\ContentToCategories::findAll(null, array('content_id' => $id));
            if (isset($cats->rows[0])) {
                $catAry = array();
                foreach ($cats->rows as $cat) {
                    $catAry[] = $cat->category_id;
                }
                $contentValues['category_id'] = $catAry;
            }

            // Get roles
            $content = Table\Content::findById($id);
            $roles = (null !== $content->roles) ? unserialize($content->roles) : array();

            if (isset($roles[0])) {
                $rolesAry = array();
                foreach ($roles as $rid) {
                    $rolesAry[] = $rid;
                }
                $contentValues['roles'] = $rolesAry;
            } else {
                $contentValues['roles'] = array();
            }

            $contentValues['created'] = '<strong>' . $this->i18n->__('Created') . ':</strong> ' . date($this->config->datetime_format, strtotime($contentValues['created']));
            if (null !== $contentValues['created_by']) {
                $u = \Phire\Table\Users::findById($contentValues['created_by']);
                if (isset($u->username)) {
                    $contentValues['created'] .= ' ' . $this->i18n->__('by') . ' <strong>' . $u->username . '</strong>';
                }
            }

            if (($contentValues['updated'] != '0000-00-00 00:00:00') && (null !== $contentValues['updated'])) {
                $contentValues['updated'] = '<strong>' . $this->i18n->__('Updated') . ':</strong> ' . date($this->config->datetime_format, strtotime($contentValues['updated']));
                if (null !== $contentValues['updated_by']) {
                    $u = \Phire\Table\Users::findById($contentValues['updated_by']);
                    if (isset($u->username)) {
                        $contentValues['updated'] .= ' ' . $this->i18n->__('by') . ' <strong>' . $u->username . '</strong>';
                    }
                }
            } else {
                $contentValues['updated'] = '<strong>' . $this->i18n->__('Updated') . ':</strong> ' . $this->i18n->__('Never');
            }

            $contentValues['typeUri'] = $type->uri;
            $contentValues = array_merge($contentValues, \Phire\Model\FieldValue::getAll($id));

            if (!((!$this->config->open_authoring) && ($contentValues['created_by'] != $this->user->id))) {
                $this->data = array_merge($this->data, $contentValues);
            }
        }
    }

    /**
     * Get content feed
     *
     * @param  int $limit
     * @return array
     */
    public function getFeed($limit = 0)
    {
        if ($limit == 0) {
            $limit = null;
        }

        $entries = array();

        $content = Table\Content::findAll('publish DESC', array('feed' => 1), $limit);
        foreach ($content->rows as $c) {
            if (((null === $c->status) || ($c->status == self::PUBLISHED)) &&
                (strtotime($c->publish) <= time()) &&
                ((null === $c->expire) || ((null !== $c->expire) && (strtotime($c->expire) >= time())))) {

                $site = \Phire\Table\Sites::getSite((int)$c->site_id);

                if (null !== $c->status) {
                    $uri   = $c['uri'];
                    $title = $c->title;
                    $description = '<![CDATA[<a href="http://' . $site->domain . $site->base_path . $uri . '">http://' . $site->domain . $site->base_path . $uri . '</a>]]>';
                } else {
                    $uri   = CONTENT_PATH . '/media/' . $c['uri'];
                    $fileIcon = \Phire\Model\Media::getFileIcon($c['uri'], $site->document_root . $site->base_path . CONTENT_PATH . '/media');
                    $title = $c->title;
                    $description = '<![CDATA[<a href="http://' . $site->domain . $site->base_path . $uri . '"><img src="http://' . $site->domain . $site->base_path . CONTENT_PATH . $fileIcon['fileIcon'] . '" width="80" alt="' . $c['uri'] . '" /></a>]]>';
                }

                $entries[] = array(
                    'title'       => $c->title,
                    'link'        => 'http://' . $site->domain . $site->base_path. $uri,
                    'updated'     => $c['publish'],
                    'summary'     => $title,
                    'description' => $description
                );
            }
        }

        return $entries;
    }

    /**
     * Method to get content breadcrumb
     *
     * @return string
     */
    public function getBreadcrumb()
    {
        $breadcrumb = $this->title;
        $pId = $this->parent_id;
        $sep = $this->config->separator;

        while ($pId != 0) {
            $content = Table\Content::findById($pId);
            if (isset($content->id)) {
                $site = \Phire\Table\Sites::getSite((int)$content->site_id);
                if ($content->status == self::PUBLISHED) {
                    $breadcrumb = '<a href="' . $site->base_path . $content->uri . '">' . $content->title . '</a> ' .
                        $sep . ' ' . $breadcrumb;
                }
                $pId = $content->parent_id;
            }
        }

        return $breadcrumb;
    }

    /**
     * Save content
     *
     * @param \Pop\Form\Form $form
     * @throws \Pop\File\Exception
     * @return void
     */
    public function save(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $parentId = null;
        $publish  = null;
        $expire   = null;
        $uri      = null;
        $slug     = null;

        if (isset($fields['parent_id'])) {
            $parentId = ((int)$fields['parent_id'] != 0) ? (int)$fields['parent_id'] : null;
        }

        if (isset($fields['publish_year']) && ($fields['publish_year'] != '----') && ($fields['publish_month'] != '--') &&
            ($fields['publish_day'] != '--') && ($fields['publish_hour'] != '--') && ($fields['publish_minute'] != '--')) {
            $publish = $fields['publish_year'] . '-' . $fields['publish_month'] . '-' .
                $fields['publish_day'] . ' ' . $fields['publish_hour'] . ':' . $fields['publish_minute'] . ':00';
        } else {
            $publish = date('Y-m-d H:i:s');
        }

        if (isset($fields['expire_year']) && ($fields['expire_year'] != '----') && ($fields['expire_month'] != '--') &&
            ($fields['expire_day'] != '--') && ($fields['expire_hour'] != '--') && ($fields['expire_minute'] != '--')) {
            $expire = $fields['expire_year'] . '-' . $fields['expire_month'] . '-' .
                $fields['expire_day'] . ' ' . $fields['expire_hour'] . ':' . $fields['expire_minute'] . ':00';
        }

        if (($_FILES) && isset($_FILES['uri']) && ($_FILES['uri']['error'] == 1)) {
            throw new \Pop\File\Exception("The file exceeds the PHP 'upload_max_filesize' setting of " . ini_get('upload_max_filesize') . ".");
        // If content is a file
        } else if (($_FILES) && isset($_FILES['uri']) && ($_FILES['uri']['tmp_name'] != '')) {
            $site = \Phire\Table\Sites::getSite((int)$fields['site_id']);
            $dir = $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media';
            $fileName = File::checkDupe($_FILES['uri']['name'], $dir);

            File::upload(
                $_FILES['uri']['tmp_name'], $dir . DIRECTORY_SEPARATOR . $fileName,
                $this->config->media_max_filesize, $this->config->media_allowed_types
            );
            chmod($dir . DIRECTORY_SEPARATOR . $fileName, 0777);
            if (preg_match(\Phire\Model\Media::getImageRegex(), $fileName)) {
                \Phire\Model\Media::process($fileName, $this->config, $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media');
            }

            $title = ($fields['content_title'] != '') ?
                $fields['content_title'] :
                ucwords(str_replace(array('_', '-'), array(' ', ' '), substr($fileName, 0, strrpos($fileName, '.'))));
            $uri = $fileName;
            $slug = $fileName;
        // Else, if the content is a regular content object
        } else {
            $title = $fields['content_title'];
            $slug = $fields['uri'];
            $uri = $fields['uri'];

            if (((int)$fields['parent_id'] != 0) && (substr($uri, 0, 4) != 'http') && (substr($uri, 0, 1) != '/')) {
                $pId = $fields['parent_id'];
                while ($pId != 0) {
                    $parentContent = Table\Content::findById($pId);
                    if (isset($parentContent->id)) {
                        $pId = $parentContent->parent_id;
                        $pSlug = (substr($parentContent->slug, -1) == '#') ? substr($parentContent->slug, 0, -1) : $parentContent->slug;
                        $uri = $pSlug . '/' . $uri;
                    }
                }
            }

            // URI clean up
            if (substr($uri, 0, 4) != 'http') {
                if (substr($uri, 0, 1) != '/') {
                    $uri = '/' . $uri;
                } else if (substr($uri, 0, 2) == '//') {
                    $uri = substr($uri, 1);
                } else if ($uri == '') {
                    $uri = '/';
                }
            }
        }

        $content = new Table\Content(array(
            'site_id'    => (int)$fields['site_id'],
            'type_id'    => $fields['type_id'],
            'parent_id'  => $parentId,
            'template'   => ((isset($fields['template']) && ($fields['template'] != '0')) ? $fields['template'] : null),
            'title'      => $title,
            'uri'        => $uri,
            'slug'       => $slug,
            'feed'       => (int)$fields['feed'],
            'force_ssl'  => ((isset($fields['force_ssl']) ? (int)$fields['force_ssl'] : null)),
            'status'     => ((isset($fields['status']) ? (int)$fields['status'] : null)),
            'roles'      => ((isset($fields['roles']) ? serialize($fields['roles']) : null)),
            'created'    => date('Y-m-d H:i:s'),
            'updated'    => null,
            'publish'    => $publish,
            'expire'     => $expire,
            'created_by' => ((isset($this->user) && isset($this->user->id)) ? $this->user->id : null),
            'updated_by' => null
        ));

        $content->save();
        $this->data['id'] = $content->id;
        $this->data['uri'] = $content->uri;

        // Save content navs
        if (isset($fields['navigation_id'])) {
            foreach ($fields['navigation_id'] as $nav) {
                $contentToNav = new Table\ContentToNavigation(array(
                    'navigation_id' => $nav,
                    'content_id'    => $content->id,
                    'order'         => (int)$_POST['navigation_order_' . $nav]
                ));
                $contentToNav->save();
            }
        }

        // Save content categories
        if (isset($fields['category_id'])) {
            foreach ($fields['category_id'] as $cat) {
                $contentToCategory = new Table\ContentToCategories(array(
                    'content_id'  => $content->id,
                    'category_id' => $cat,
                    'order'       => (int)$_POST['category_order_' . $cat]
                ));
                $contentToCategory->save();
            }
        }

        \Phire\Model\FieldValue::save($fields, $content->id);
    }

    /**
     * Update content
     *
     * @param \Pop\Form\Form $form
     * @throws \Pop\File\Exception
     * @return void
     */
    public function update(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $content   = Table\Content::findById($fields['id']);
        $oldSiteId = $content->site_id;
        $oldUri    = $content->uri;

        $parentId = null;
        $uri      = null;
        $slug     = null;
        $expire   = null;

        if (isset($fields['parent_id'])) {
            $parentId = ((int)$fields['parent_id'] != 0) ? (int)$fields['parent_id'] : null;
        }

        if (isset($fields['publish_year']) && ($fields['publish_year'] != '----') && ($fields['publish_month'] != '--') &&
            ($fields['publish_day'] != '--') && ($fields['publish_hour'] != '--') && ($fields['publish_minute'] != '--')) {
            $publish = $fields['publish_year'] . '-' . $fields['publish_month'] . '-' .
                $fields['publish_day'] . ' ' . $fields['publish_hour'] . ':' . $fields['publish_minute'] . ':00';
        } else {
            $publish = $content->publish;
        }

        if (isset($fields['expire_year']) && ($fields['expire_year'] != '----') && ($fields['expire_month'] != '--') &&
            ($fields['expire_day'] != '--') && ($fields['expire_hour'] != '--') && ($fields['expire_minute'] != '--')) {
            $expire = $fields['expire_year'] . '-' . $fields['expire_month'] . '-' .
                $fields['expire_day'] . ' ' . $fields['expire_hour'] . ':' . $fields['expire_minute'] . ':00';
        } else if (isset($fields['expire_year']) && ($fields['expire_year'] == '----') && ($fields['expire_month'] == '--') &&
            ($fields['expire_day'] == '--') && ($fields['expire_hour'] == '--') && ($fields['expire_minute'] == '--')) {
            $expire = null;
        }

        // If content is a file
        if (!isset($fields['parent_id'])) {
            if (($_FILES) && isset($_FILES['uri']) && ($_FILES['uri']['error'] == 1)) {
                throw new \Pop\File\Exception("The file exceeds the PHP 'upload_max_filesize' setting of " . ini_get('upload_max_filesize') . ".");
            // If content is a file
            } else if (($_FILES) && isset($_FILES['uri']) && ($_FILES['uri']['tmp_name'] != '')) {
                $site = \Phire\Table\Sites::getSite((int)$fields['site_id']);
                $dir = $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media';
                \Phire\Model\Media::remove($content->uri, $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media');
                $fileName = File::checkDupe($_FILES['uri']['name'], $dir);
                File::upload(
                    $_FILES['uri']['tmp_name'], $dir . DIRECTORY_SEPARATOR . $fileName,
                    $this->config->media_max_filesize, $this->config->media_allowed_types
                );
                chmod($dir . DIRECTORY_SEPARATOR . $fileName, 0777);
                if (preg_match(\Phire\Model\Media::getImageRegex(), $fileName)) {
                    \Phire\Model\Media::process($fileName, $this->config, $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media');
                }
                $title = ($fields['content_title'] != '') ?
                    $fields['content_title'] :
                    ucwords(str_replace(array('_', '-'), array(' ', ' '), substr($fileName, 0, strrpos($fileName, '.'))));

                $uri = $fileName;
                $slug = $fileName;
            } else {
                $title = $fields['content_title'];
                $uri = $content->uri;
                $slug = $content->slug;
            }
        // Else, if the content is a regular content object
        } else {
            $title = $fields['content_title'];
            $slug = $fields['uri'];
            $uri = $fields['uri'];

            if (((int)$fields['parent_id'] != 0) && (substr($uri, 0, 4) != 'http') && (substr($uri, 0, 1) != '/')) {
                $pId = $fields['parent_id'];
                while ($pId != 0) {
                    $parentContent = Table\Content::findById($pId);
                    if (isset($parentContent->id)) {
                        $pId = $parentContent->parent_id;
                        $pSlug = (substr($parentContent->slug, -1) == '#') ? substr($parentContent->slug, 0, -1) : $parentContent->slug;
                        $uri = $pSlug . '/' . $uri;
                    }
                }
            }

            // URI clean up
            if (substr($uri, 0, 4) != 'http') {
                if (substr($uri, 0, 1) != '/') {
                    $uri = '/' . $uri;
                } else if (substr($uri, 0, 2) == '//') {
                    $uri = substr($uri, 1);
                } else if ($uri == '') {
                    $uri = '/';
                }
            }

            // If the URI changed, change the child URIs
            if ($uri != $oldUri) {
                $children = Table\Content::findAll(null, array('parent_id' => $content->id));
                if (isset($children->rows[0])) {
                    $newUri = (substr($uri, -1) == '#') ? substr($uri, 0, -1) : $uri;
                    $this->changeChildUris($newUri, $children->rows);
                }
            }
        }

        $content->site_id    = (int)$fields['site_id'];
        $content->type_id    = $fields['type_id'];
        $content->parent_id  = $parentId;
        $content->template   = ((isset($fields['template']) && ($fields['template'] != '0')) ? $fields['template'] : null);
        $content->title      = $title;
        $content->uri        = $uri;
        $content->slug       = $slug;
        $content->feed       = (int)$fields['feed'];
        $content->force_ssl  = ((isset($fields['force_ssl']) ? (int)$fields['force_ssl'] : null));
        $content->status     = ((isset($fields['status']) ? (int)$fields['status'] : null));
        $content->roles      = ((isset($fields['roles']) ? serialize($fields['roles']) : null));
        $content->updated    = date('Y-m-d H:i:s');
        $content->publish    = $publish;
        $content->expire     = $expire;
        $content->updated_by = ((isset($this->user) && isset($this->user->id)) ? $this->user->id : null);

        $content->update();
        $this->data['id'] = $content->id;
        $this->data['uri'] = $content->uri;

        if (!isset($fields['parent_id']) && ($oldSiteId != $content->site_id)) {
            $oldSite = \Phire\Table\Sites::getSite((int)$oldSiteId);
            $newSite = \Phire\Table\Sites::getSite((int)$content->site_id);

            if (file_exists($oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $content->uri) &&
                !file_exists($newSite->document_root . $newSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $content->uri)) {
                rename(
                    $oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $content->uri,
                    $newSite->document_root . $newSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $content->uri
                );
                chmod($newSite->document_root . $newSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $content->uri, 0777);
            } else if (file_exists($oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $oldUri)) {
                unlink($oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $oldUri);
            }

            $dirs = new Dir($oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media');
            foreach ($dirs->getFiles() as $size) {
                if (is_dir($oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size) &&
                    is_dir($newSite->document_root . $newSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR  . $size)) {
                    if (file_exists($oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $content->uri) &&
                        !file_exists($newSite->document_root . $newSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $content->uri)) {
                        rename(
                            $oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $content->uri,
                            $newSite->document_root . $newSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $content->uri
                        );
                        chmod($newSite->document_root . $newSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $content->uri, 0777);
                    } else if (file_exists($oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $oldUri)) {
                        unlink($oldSite->document_root . $oldSite->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR .$size . DIRECTORY_SEPARATOR .  $oldUri);
                    }
                }
            }
        }

        // Update content navs
        $contentToNavigation = Table\ContentToNavigation::findBy(array('content_id' => $content->id));
        foreach ($contentToNavigation->rows as $nav) {
            $contentToNav = Table\ContentToNavigation::findById(array($nav->navigation_id, $content->id, null));
            if (isset($contentToNav->content_id)) {
                $contentToNav->delete();
            }
        }

        if (isset($_POST['navigation_id'])) {
            foreach ($_POST['navigation_id'] as $nav) {
                $contentToNav = new Table\ContentToNavigation(array(
                    'content_id'    => $content->id,
                    'navigation_id' => $nav,
                    'order'         => (int)$_POST['navigation_order_' . $nav]
                ));
                $contentToNav->save();
            }
        }

        // Update content categories
        $contentToCategories = Table\ContentToCategories::findBy(array('content_id' => $content->id));
        foreach ($contentToCategories->rows as $cat) {
            $contentToCat = Table\ContentToCategories::findById(array($content->id, $cat->category_id));
            if (isset($contentToCat->content_id)) {
                $contentToCat->delete();
            }
        }

        if (isset($_POST['category_id'])) {
            foreach ($_POST['category_id'] as $cat) {
                $contentToCategory = new Table\ContentToCategories(array(
                    'content_id'  => $content->id,
                    'category_id' => $cat,
                    'order'       => (int)$_POST['category_order_' . $cat]
                ));
                $contentToCategory->save();
            }
        }

        \Phire\Model\FieldValue::update($fields, $content->id);
    }

    /**
     * Copy content
     *
     * @return void
     */
    public function copy()
    {
        $id    = $this->data['id'];
        $title = $this->data['content_title'] . ' (Copy ';
        $uri   = $this->data['full_uri'];
        $slug  = $this->data['uri'];

        // Check for dupe uris
        $i = 1;
        $dupe = Table\Content::findBy(array('uri' => $uri . '-' . $i));
        while (isset($dupe->id)) {
            $i++;
            $dupe = Table\Content::findBy(array('uri' => $uri . '-' . $i));
        }

        $title .= $i . ')';
        $uri   .= '-' . $i;
        $slug  .= '-' . $i;

        $content = new Table\Content(array(
            'site_id'    => $this->data['site_id'],
            'type_id'    => $this->data['type_id'],
            'parent_id'  => $this->data['parent_id'],
            'template'   => $this->data['template'],
            'title'      => $title,
            'uri'        => $uri,
            'slug'       => $slug,
            'feed'       => $this->data['feed'],
            'force_ssl'  => $this->data['force_ssl'],
            'status'     => 0,
            'roles'      => (isset($this->data['roles']) ? serialize($this->data['roles']) : null),
            'created'    => date('Y-m-d H:i:s'),
            'updated'    => null,
            'publish'    => date('Y-m-d H:i:s'),
            'expire'     => null,
            'created_by' => ((isset($this->user) && isset($this->user->id)) ? $this->user->id : null),
            'updated_by' => null
        ));

        $content->save();
        $this->data['id'] = $content->id;

        // Save any content categories
        $cats = Table\ContentToCategories::findAll(null, array('content_id' => $id));
        if (isset($cats->rows[0])) {
            foreach ($cats->rows as $cat) {
                $contentToCategory = new Table\ContentToCategories(array(
                    'content_id'  => $content->id,
                    'category_id' => $cat->category_id,
                    'order'       => 0
                ));
                $contentToCategory->save();
            }
        }

        $values = \Phire\Table\FieldValues::findAll(null, array('model_id' => $id));
        if (isset($values->rows[0])) {
            foreach ($values->rows as $value) {
                $field = \Phire\Table\Fields::findById($value->field_id);
                if (isset($field->id) && ($field->type != 'file') && (null === $field->group_id)) {
                    $val = new \Phire\Table\FieldValues(array(
                        'field_id'  => $value->field_id,
                        'model_id'  => $content->id,
                        'value'     => $value->value,
                        'timestamp' => $value->timestamp,
                        'history'   => $value->history
                    ));
                    $val->save();
                }
            }
        }

        $contents = Table\Content::findAll(null, array('type_id' => $this->data['type_id']));
        $count    = $contents->count();
        if ($count > $this->config->pagination_limit) {
            $this->data['page'] = (($count - ($count % $this->config->pagination_limit)) / $this->config->pagination_limit) + 1;
        }
    }

    /**
     * Process batch
     *
     * @throws \Pop\File\Exception
     * @return void
     */
    public function batch()
    {
        $batchErrors = array();
        $contentIds  = array();
        $config      = \Phire\Table\Config::getSystemConfig();

        // Check for global file setting configurations
        if ($_FILES) {
            $regex = '/^.*\.(' . implode('|', array_keys($config->media_allowed_types))  . ')$/i';

            foreach ($_FILES as $key => $value) {
                if (($_FILES) && isset($_FILES[$key]) && ($_FILES[$key]['error'] == 1)) {
                    throw new \Pop\File\Exception("A file exceeds the PHP 'upload_max_filesize' setting of " . ini_get('upload_max_filesize') . ".");
                } else if (!empty($value['name'])) {
                    if ($value['size'] > $config->media_max_filesize) {
                        $batchErrors[] = 'The file \'' . $value['name'] . '\' must be less than ' . $config->media_max_filesize_formatted . '.';
                    }
                    if (preg_match($regex, $value['name']) == 0) {
                        $type = strtoupper(substr($value['name'], (strrpos($value['name'], '.') + 1)));
                        $batchErrors[] = 'The ' . $type . ' file type is not allowed.';
                    }
                }
            }
        }

        $this->data['batchErrors'] = $batchErrors;

        if (count($batchErrors) == 0) {
            if ($_FILES) {
                $site = \Phire\Table\Sites::getSite((int)$_POST['site_id']);
                $dir = $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media';
                if (($_FILES) && isset($_FILES['archive_file']) && ($_FILES['archive_file']['error'] == 1)) {
                    throw new \Pop\File\Exception("The archive file exceeds the PHP 'upload_max_filesize' setting of " . ini_get('upload_max_filesize') . ".");
                } else if (!empty($_FILES['archive_file']) && ($_FILES['archive_file']['name'] != '')) {
                    mkdir($dir . DIRECTORY_SEPARATOR . 'tmp');
                    chmod($dir . DIRECTORY_SEPARATOR . 'tmp', 0777);

                    $archive = Archive::upload(
                        $_FILES['archive_file']['tmp_name'], $dir . DIRECTORY_SEPARATOR . $_FILES['archive_file']['name'],
                        $this->config->media_max_filesize, $this->config->media_allowed_types
                    );
                    $archive->setPermissions(0777);
                    $archive->extract($dir . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR);
                    $archive->delete();

                    if (stripos($_FILES['archive_file']['name'], '.tar') !== false) {
                        $filename = substr($_FILES['archive_file']['name'], 0, (strpos($_FILES['archive_file']['name'], '.tar') + 4));
                        if (file_exists($dir . DIRECTORY_SEPARATOR . $filename) && !is_dir($dir . DIRECTORY_SEPARATOR . $filename)) {
                            unlink($dir . DIRECTORY_SEPARATOR . $filename);
                        }
                    } else if ((stripos($_FILES['archive_file']['name'], '.tgz') !== false) ||
                               (stripos($_FILES['archive_file']['name'], '.tbz') !== false)) {
                        $filename = substr($_FILES['archive_file']['name'], 0, strpos($_FILES['archive_file']['name'], '.t')) . '.tar';
                        if (file_exists($dir . DIRECTORY_SEPARATOR . $filename) && !is_dir($dir . DIRECTORY_SEPARATOR . $filename)) {
                            unlink($dir . DIRECTORY_SEPARATOR . $filename);
                        }
                    }

                    $tmpDir = new Dir($dir . DIRECTORY_SEPARATOR . 'tmp', true, true, false);
                    $allowed = $this->config->media_allowed_types;

                    foreach ($tmpDir->getFiles() as $file) {
                        $pathParts = pathinfo($file);
                        if ((filesize($file) <= $this->config->media_max_filesize) && array_key_exists($pathParts['extension'], $allowed)) {
                            $fileName = File::checkDupe($pathParts['basename'], $dir);
                            copy($file, $dir . DIRECTORY_SEPARATOR . $fileName);
                            chmod($dir . DIRECTORY_SEPARATOR . $fileName, 0777);
                            if (preg_match(\Phire\Model\Media::getImageRegex(), $fileName)) {
                                \Phire\Model\Media::process($fileName, $this->config, $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media');
                            }
                            $content = new Table\Content(array(
                                'site_id'    => $_POST['site_id'],
                                'type_id'    => $_POST['type_id'],
                                'title'      => ucwords(str_replace(array('_', '-'), array(' ', ' '), substr($fileName, 0, strrpos($fileName, '.')))),
                                'uri'        => $fileName,
                                'slug'       => $fileName,
                                'feed'       => 0,
                                'force_ssl'  => null,
                                'status'     => null,
                                'created'    => date('Y-m-d H:i:s'),
                                'updated'    => null,
                                'publish'    => date('Y-m-d H:i:s'),
                                'expire'     => null,
                                'created_by' => ((isset($this->user) && isset($this->user->id)) ? $this->user->id : null),
                                'updated_by' => null
                            ));

                            $content->save();
                            $contentIds[] = $content->id;
                        } else {
                            if (filesize($file) > $this->config->media_max_filesize) {
                                $this->data['batchErrors'][] = 'The file \'' . $pathParts['basename'] . '\' must be less than ' . $config->media_max_filesize_formatted . '.';
                            }
                            if (!array_key_exists($pathParts['extension'], $allowed)) {
                                $this->data['batchErrors'][] = 'The ' . strtoupper($pathParts['extension']) . ' file type is not allowed.';
                            }
                        }
                    }

                    $tmpDir->emptyDir(null, true);
                }

                foreach ($_FILES as $key => $value) {
                    if (($key != 'archive_file') && ($value['name'] != '')) {
                        $id = substr($key, (strrpos($key, '_') + 1));
                        $fileName = File::checkDupe($value['name'], $dir);
                        $upload = File::upload(
                            $value['tmp_name'], $dir . DIRECTORY_SEPARATOR . $fileName,
                            $this->config->media_max_filesize, $this->config->media_allowed_types
                        );
                        $upload->setPermissions(0777);
                        if (preg_match(\Phire\Model\Media::getImageRegex(), $fileName)) {
                            \Phire\Model\Media::process($fileName, $this->config, $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media');
                        }

                        $title = ($_POST['file_title_' . $id] != '') ?
                            $_POST['file_title_' . $id] :
                            ucwords(str_replace(array('_', '-'), array(' ', ' '), substr($fileName, 0, strrpos($fileName, '.'))));

                        $content = new Table\Content(array(
                            'site_id'    => $_POST['site_id'],
                            'type_id'    => $_POST['type_id'],
                            'title'      => $title,
                            'uri'        => $fileName,
                            'slug'       => $fileName,
                            'feed'       => 0,
                            'force_ssl'  => null,
                            'status'     => null,
                            'created'    => date('Y-m-d H:i:s'),
                            'updated'    => null,
                            'publish'    => date('Y-m-d H:i:s'),
                            'expire'     => null,
                            'created_by' => ((isset($this->user) && isset($this->user->id)) ? $this->user->id : null),
                            'updated_by' => null
                        ));

                        $content->save();
                        $contentIds[] = $content->id;
                    }
                }
            }
        }

        // Save content categories
        if ((count($contentIds) > 0) && isset($_POST['category_id'])) {
            foreach ($contentIds as $cid) {
                foreach ($_POST['category_id'] as $cat) {
                    $contentToCategory = new Table\ContentToCategories(array(
                        'content_id'  => $cid,
                        'category_id' => $cat
                    ));
                    $contentToCategory->save();
                }
            }
        }
    }

    /**
     * Process batch
     *
     * @param  array $post
     * @return void
     */
    public function process(array $post)
    {
        $process = (int)$post['content_process'];
        if (isset($post['process_content'])) {
            $open = $this->config('open_authoring');
            foreach ($post['process_content'] as $id) {
                $content = Table\Content::findById($id);
                $createdBy = null;
                if (isset($content->id)) {
                    $createdBy = $content->created_by;
                    if (!((!$open) && ($content->created_by != $this->user->id))) {
                        if ($process == self::REMOVE) {
                            $type = Table\ContentTypes::findById($content->type_id);
                            if (isset($type->id) && (!$type->uri)) {
                                $site = \Phire\Table\Sites::getSite((int)$content->site_id);
                                \Phire\Model\Media::remove($content->uri, $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media');
                            }
                            $content->delete();
                        } else {
                            $content->status = $process;
                            $content->update();
                        }
                    }
                }

                // If the Fields module is installed, and if there are fields for this form/model
                if (($process == self::REMOVE) && !((!$open) && ($createdBy != $this->user->id))) {
                    \Phire\Model\FieldValue::remove($id);
                }
            }
        }
    }

    /**
     * Get images for WYSIWYG editor
     *
     * @param  string  $sort
     * @param  string  $page
     * @return void
     */
    public function getImages($sort = null, $page = null)
    {
        $order = $this->getSortOrder($sort, $page, 'DESC');
        $order['field'] = ($order['field'] == 'id') ? DB_PREFIX . 'content.id' : $order['field'];

        $sess = \Pop\Web\Session::getInstance();
        $sizes = array_keys($this->config->media_actions);

        // Get images
        $sql = Table\Content::getSql();
        $sql->select(array(
            'content_id'      => DB_PREFIX . 'content.id',
            'content_site_id' => DB_PREFIX . 'content.site_id',
            'content_type_id' => DB_PREFIX . 'content.type_id',
            'content_uri'     => DB_PREFIX . 'content.uri',
            'content_title'   => DB_PREFIX . 'content.title',
            'type_id'         => DB_PREFIX . 'content_types.id',
            'type_uri'        => DB_PREFIX . 'content_types.uri',
        ))->join(DB_PREFIX . 'content_types', array('type_id', 'id'), 'LEFT JOIN')
            ->where()
            ->equalTo(DB_PREFIX . 'content_types.uri', 0)
            ->like(DB_PREFIX . 'content.uri', '%.jpg', 'AND')
            ->like(DB_PREFIX . 'content.uri', '%.jpe', 'OR')
            ->like(DB_PREFIX . 'content.uri', '%.jpeg', 'OR')
            ->like(DB_PREFIX . 'content.uri', '%.png', 'OR')
            ->like(DB_PREFIX . 'content.uri', '%.gif', 'OR');

        $content = Table\Content::execute($sql->render(true));
        $contentRows = $content->rows;

        // Set the onlick action based on the editor
        if ($_GET['editor'] == 'ckeditor') {
            $onclick = "window.opener.CKEDITOR.tools.callFunction(funcNum, this.href); window.close(); return false;";
        } else if ($_GET['editor'] == 'tinymce') {
            $onclick = "top.tinymce.activeEditor.windowManager.getParams().oninsert(this.href); top.tinymce.activeEditor.windowManager.close(); return false;";
        } else {
            $onclick = 'return false;';
        }

        // Format the select column
        foreach ($contentRows as $key => $value) {
            if (in_array($value->content_site_id, $sess->user->site_ids)) {
                $site = \Phire\Table\Sites::getSite((int)$value->content_site_id);

                $fileInfo = \Phire\Model\Media::getFileIcon($value->content_uri, $site->document_root . $site->base_path . CONTENT_PATH . '/media');
                $value->file_icon = $fileInfo['fileIcon'];
                $value->file_size = $fileInfo['fileSize'];

                $select = '[ <a href="http://' . $site->domain . $site->base_path . CONTENT_PATH . '/media/' . $value->content_uri . '" onclick="' . $onclick . '">' . $this->i18n->__('Original') . ' </a>';
                foreach ($sizes as $size) {
                    if (file_exists($site->document_root . $site->base_path . CONTENT_PATH . '/media/' . $size . '/' . $value->content_uri)){
                        $select .= ' | <a href="http://' . $site->domain . $site->base_path . CONTENT_PATH . '/media/' . $size . '/' . $value->content_uri . '" onclick="' . $onclick . '">' . ucfirst($size) . '</a>';
                    }
                }
                $select .= ' ]';
                $value->select     = $select;
                $value->domain     = $site->domain;
                $value->base_path  = $site->base_path;
                $contentRows[$key] = $value;
            } else {
                unset($contentRows[$key]);
            }
        }

        $count = count($contentRows);

        if ($count > (int)$order['limit']) {
            $contentRows = array_slice($contentRows, $order['offset'], $order['limit']);
            $pg = new \Pop\Paginator\Paginator($contentRows, $this->config->pagination_limit, $this->config->pagination_range, $count);
            $this->data['page_links'] = implode('', $pg->getLinks(((null !== $page) ? $page : 1)));
        } else {
            $this->data['page_links'] = null;
        }

        $this->data['sizes']   = $sizes;
        $this->data['content'] = $contentRows;
    }

    /**
     * Get files and URIs for WYSIWYG editor
     *
     * @param  string  $sort
     * @param  string  $page
     * @param  string  $tid
     * @return void
     */
    public function getFiles($sort = null, $page = null, $tid = null)
    {
        $order = $this->getSortOrder($sort, $page, 'DESC');
        $order['field'] = ($order['field'] == 'id') ? DB_PREFIX . 'content.id' : $order['field'];

        $sess = \Pop\Web\Session::getInstance();
        $sizes = array_keys($this->config->media_actions);

        // Get files
        $sql = Table\Content::getSql();
        $sql->select(array(
            'content_id'      => DB_PREFIX . 'content.id',
            'content_site_id' => DB_PREFIX . 'content.site_id',
            'content_type_id' => DB_PREFIX . 'content.type_id',
            'content_uri'     => DB_PREFIX . 'content.uri',
            'content_title'   => DB_PREFIX . 'content.title',
            'type_id'         => DB_PREFIX . 'content_types.id',
            'type_uri'        => DB_PREFIX . 'content_types.uri',
        ))->join(DB_PREFIX . 'content_types', array('type_id', 'id'), 'LEFT JOIN')
            ->where()
            ->equalTo(DB_PREFIX . 'content_types.uri', 0);

        if (null !== $tid) {
            $sql->select()->where()->equalTo(DB_PREFIX . 'content.type_id', (int)$tid);
        }

        $content = Table\Content::execute($sql->render(true));
        $contentRows = $content->rows;

        // Set the onlick action based on the editor
        if ($_GET['editor'] == 'ckeditor') {
            $onclick = "window.opener.CKEDITOR.tools.callFunction(funcNum, this.href); window.close(); return false;";
        } else if ($_GET['editor'] == 'tinymce') {
            $onclick = "top.tinymce.activeEditor.windowManager.getParams().oninsert(this.href); top.tinymce.activeEditor.windowManager.close(); return false;";
        } else {
            $onclick = 'return false;';
        }

        // Format the select column
        foreach ($contentRows as $key => $value) {
            if (in_array($value->content_site_id, $sess->user->site_ids)) {
                $site = \Phire\Table\Sites::getSite((int)$value->content_site_id);

                $fileInfo = \Phire\Model\Media::getFileIcon($value->content_uri, $site->document_root . $site->base_path . CONTENT_PATH . '/media');
                $value->file_icon = $fileInfo['fileIcon'];
                $value->file_size = $fileInfo['fileSize'];

                $select = '[ <a href="http://' . $site->domain . $site->base_path . CONTENT_PATH . '/media/' . $value->content_uri . '" onclick="' . $onclick . '">' . $this->i18n->__('Original') . '</a>';
                foreach ($sizes as $size) {
                    if (file_exists($site->document_root . $site->base_path . CONTENT_PATH . '/media/' . $size . '/' . $value->content_uri)){
                        $select .= ' | <a href="http://' . $site->domain . $site->base_path . CONTENT_PATH . '/media/' . $size . '/' . $value->content_uri . '" onclick="' . $onclick . '">' . ucfirst($size) . '</a>';
                    }
                }
                $select .= ' ]';
                $value->select     = $select;
                $value->domain     = $site->domain;
                $value->base_path  = $site->base_path;
                $contentRows[$key] = $value;
            } else {
                unset($contentRows[$key]);
            }
        }

        // Get URIs
        $sql =Table\Content::getSql();
        $sql->select(array(
            'content_id'      => DB_PREFIX . 'content.id',
            'content_site_id' => DB_PREFIX . 'content.site_id',
            'content_type_id' => DB_PREFIX . 'content.type_id',
            'content_uri'     => DB_PREFIX . 'content.uri',
            'content_title'   => DB_PREFIX . 'content.title',
            'type_id'         => DB_PREFIX . 'content_types.id',
            'type_uri'        => DB_PREFIX . 'content_types.uri',
        ))->join(DB_PREFIX . 'content_types', array('type_id', 'id'), 'LEFT JOIN')
            ->where()
            ->equalTo(DB_PREFIX . 'content_types.uri', 1);

        if (null !== $tid) {
            $sql->select()->where()->equalTo(DB_PREFIX . 'content.type_id', (int)$tid);
        }

        $content = Table\Content::execute($sql->render(true));
        $uriRows = $content->rows;

        // Format the select column
        foreach ($uriRows as $key => $value) {
            if (in_array($value->content_site_id, $sess->user->site_ids)) {
                $site = \Phire\Table\Sites::getSite((int)$value->content_site_id);

                $value->file_icon = null;
                $value->file_size = null;

                $value->select    = '[ <a href="http://' . $site->domain . $site->base_path . $value->content_uri . '" onclick="' . $onclick . '">' . $this->i18n->__('URI') . '</a> ]';
                $value->domain    = $site->domain;
                $value->base_path = $site->base_path;
                $uriRows[$key]    = $value;
            } else {
                unset($uriRows[$key]);
            }
        }

        $contentRows = array_merge($uriRows, $contentRows);

        $count = count($contentRows);

        if ($count > (int)$order['limit']) {
            $contentRows = array_slice($contentRows, $order['offset'], $order['limit']);
            $pg = new \Pop\Paginator\Paginator($contentRows, $this->config->pagination_limit, $this->config->pagination_range, $count);
            $this->data['page_links'] = implode('', $pg->getLinks(((null !== $page) ? $page : 1)));
        } else {
            $this->data['page_links'] = null;
        }

        $this->data['sizes'] = $sizes;
        $this->data['content'] = $contentRows;
    }

    /**
     * Upload file
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function upload(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        // If content is a file
        if (($_FILES) && isset($_FILES['uri']) && ($_FILES['uri']['tmp_name'] != '')) {
            $site = \Phire\Table\Sites::getSite((int)$fields['site_id']);

            $dir = $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media';
            $fileName = \Pop\File\File::checkDupe($_FILES['uri']['name'], $dir);

            \Pop\File\File::upload(
                $_FILES['uri']['tmp_name'], $dir . DIRECTORY_SEPARATOR . $fileName,
                $this->config->media_max_filesize, $this->config->media_allowed_types
            );
            chmod($dir . DIRECTORY_SEPARATOR . $fileName, 0777);
            if (preg_match(\Phire\Model\Media::getImageRegex(), $fileName)) {
                \Phire\Model\Media::process($fileName, $this->config, $site->document_root . $site->base_path . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media');
            }

            $title = ucwords(str_replace(array('_', '-'), array(' ', ' '), substr($fileName, 0, strrpos($fileName, '.'))));
            $uri = $fileName;
            $slug = $fileName;

            $content = new Table\Content(array(
                'type_id'    => $fields['type_id'],
                'site_id'    => $fields['site_id'],
                'title'      => $title,
                'uri'        => $uri,
                'slug'       => $slug,
                'feed'       => 1,
                'force_ssl'  => 0,
                'created'    => date('Y-m-d H:i:s'),
                'publish'    => date('Y-m-d H:i:s'),
                'created_by' => ((isset($this->user) && isset($this->user->id)) ? $this->user->id : null),
                'updated_by' => null
            ));

            $content->save();
            $this->data['id'] = $content->id;
        }
    }

    /**
     * Update content config
     *
     * @param  array $post
     * @return void
     */
    public function updateConfig($post)
    {
        $cfg = \Phire\Table\Config::findById('feed_type');
        if (isset($cfg->setting)) {
            $cfg->value = (int)$post['feed_type'];
            $cfg->update();
        }

        $cfg = \Phire\Table\Config::findById('feed_limit');
        if (isset($cfg->setting)) {
            $cfg->value = (int)$post['feed_limit'];
            $cfg->update();
        }

        $cfg = \Phire\Table\Config::findById('open_authoring');
        if (isset($cfg->setting)) {
            $cfg->value = (int)$post['open_authoring'];
            $cfg->update();
        }

        $cfg = \Phire\Table\Config::findById('incontent_editing');
        if (isset($cfg->setting)) {
            $cfg->value = (int)$post['incontent_editing'];
            $cfg->update();
        }
    }

    /**
     * Migrate sites
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function migrate($form)
    {
        $siteFromId = ($form->site_from == 'Main') ? 0 : (int)$form->site_from;
        $siteToId   = ($form->site_to == 'Main') ? 0 : (int)$form->site_to;

        $siteFrom         = \Phire\Table\Sites::getSite($siteFromId);
        $siteFromDomain   = $siteFrom->domain;
        $siteFromDocRoot  = $siteFrom->document_root;
        $siteFromBasePath = (substr($siteFrom->base_path, 0, 1) == '/') ? substr($siteFrom->base_path, 1) : $siteFrom->base_path;

        $siteTo         = \Phire\Table\Sites::getSite($siteToId);
        $siteToDomain   = $siteTo->domain;
        $siteToDocRoot  = $siteTo->document_root;
        $siteToBasePath = (substr($siteTo->base_path, 0, 1) == '/') ? substr($siteTo->base_path, 1) : $siteTo->base_path;

        if ($siteFromBasePath != '') {
            $search = array(
                'href="http://' . $siteFromDomain . '/' . $siteFromBasePath,
                'src="http://' . $siteFromDomain . '/' . $siteFromBasePath,
                'href="/' . $siteFromBasePath,
                'src="/' . $siteFromBasePath
            );
        } else {
            $search = array(
                'href="http://' . $siteFromDomain,
                'src="http://' . $siteFromDomain,
                'href="',
                'src="'
            );
        }
        if ($siteToBasePath != '') {
            $replace = array(
                'href="http://' . $siteToDomain . '/' . $siteToBasePath,
                'src="http://' . $siteToDomain . '/' . $siteToBasePath,
                'href="/' . $siteToBasePath,
                'src="/' . $siteToBasePath
            );
        } else {
            $replace = array(
                'href="http://' . $siteToDomain,
                'src="http://' . $siteToDomain,
                'href="',
                'src="'
            );
        }

        $contentFrom  = Table\Content::findAll(null, array('site_id' => $siteFromId));

        foreach ($contentFrom->rows as $content) {
            $migrate = true;
            if ($form->migrate != '----') {
                $type = $cType = Table\ContentTypes::findById($content->type_id);
                if (($form->migrate == 'URI') && (!$type->uri)) {
                    $migrate = false;
                } else if (($form->migrate == 'File') && ($type->uri)) {
                    $migrate = false;
                }
            }

            if ($migrate) {
                $newContentId = null;
                $c = Table\Content::findBy(array(
                    'site_id' => $siteToId,
                    'uri'     => $content->uri
                ));

                // If content object already exists under the "from site" with the same URI
                if (isset($c->id)) {
                    $newContentId = $c->id;
                    $c->title = $content->title;
                    $c->slug  = $content->slug;

                    // If content object is a file
                    if (substr($c->uri, 0, 1) != '/') {
                        $sizes = \Phire\Table\Config::getMediaSizes();

                        if (file_exists($siteFromDocRoot . DIRECTORY_SEPARATOR . $siteFromBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $c->uri)) {
                            copy(
                                $siteFromDocRoot . DIRECTORY_SEPARATOR . $siteFromBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $c->uri,
                                $siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $c->uri
                            );
                            chmod($siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $c->uri, 0777);
                        }

                        foreach ($sizes as $size) {
                            if (file_exists($siteFromDocRoot . DIRECTORY_SEPARATOR . $siteFromBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $c->uri)) {
                                copy(
                                    $siteFromDocRoot . DIRECTORY_SEPARATOR . $siteFromBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $c->uri,
                                    $siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $c->uri
                                );
                                chmod($siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $c->uri, 0777);
                            }
                        }
                    }

                    $c->update();

                    $fv = \Phire\Table\FieldValues::findAll(null, array('model_id' => $content->id));
                    if (isset($fv->rows[0])) {
                        foreach ($fv->rows as $f) {
                            $field = \Phire\Table\Fields::findById($f->field_id);
                            if (isset($field->id) && ($field->type != 'file')) {
                                // Change out the site base path
                                if ((strpos($field->type, 'text') !== false) && ($siteFromBasePath != $siteToBasePath)) {
                                    $v = json_encode(str_replace($search, $replace, json_decode($f->value, true)));
                                    if (null !== $f->history) {
                                        $history = json_decode($f->history, true);
                                        foreach ($history as $key => $value) {
                                            $history[$key] = str_replace($search, $replace, $value);
                                        }
                                        $h = json_encode($history);
                                    } else {
                                        $h = $f->history;
                                    }
                                } else {
                                    $v = $f->value;
                                    $h = $f->history;
                                }

                                $newFv = \Phire\Table\FieldValues::findBy(array('field_id' => $f->field_id, 'model_id' => $c->id), null, 1);
                                if (isset($newFv->field_id)) {
                                    $newFv->value     = $v;
                                    $newFv->timestamp = $f->timestamp;
                                    $newFv->history   = $h;
                                    $newFv->update();
                                } else {
                                    $newFv = new \Phire\Table\FieldValues(array(
                                        'field_id'  => $f->field_id,
                                        'model_id'  => $c->id,
                                        'value'     => $v,
                                        'timestamp' => $f->timestamp,
                                        'history'   => $h
                                    ));
                                    $newFv->save();
                                }
                            }
                        }
                    }
                    // Create new content object
                } else {
                    $oldParent   = Table\Content::findById($content->parent_id);
                    $newParentId = null;
                    if (isset($oldParent->id)) {
                        $newParent   = Table\Content::findBy(array('site_id' => $siteToId, 'uri' => $oldParent->uri));
                        $newParentId = (isset($newParent->id)) ? $newParent->id : null;
                    }

                    $newContent = new Table\Content(array(
                        'site_id'    => $siteToId,
                        'type_id'    => $content->type_id,
                        'parent_id'  => $newParentId,
                        'template'   => $content->template,
                        'title'      => $content->title,
                        'uri'        => $content->uri,
                        'slug'       => $content->slug,
                        'feed'       => $content->feed,
                        'force_ssl'  => $content->force_ssl,
                        'status'     => $content->status,
                        'roles'      => $content->roles,
                        'created'    => $content->created,
                        'updated'    => $content->updated,
                        'publish'    => $content->publish,
                        'expire'     => $content->expire,
                        'created_by' => $content->created_by,
                        'updated_by' => $content->updated_by
                    ));

                    $newContent->save();
                    $newContentId = $newContent->id;

                    // If content object is a file
                    if (substr($content->uri, 0, 1) != '/') {
                        $sizes = \Phire\Table\Config::getMediaSizes();
                        $contentPath = $siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media';
                        $newUri = File::checkDupe($content->uri, $contentPath);

                        if (file_exists($siteFromDocRoot . DIRECTORY_SEPARATOR . $siteFromBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $content->uri)) {
                            copy(
                                $siteFromDocRoot . DIRECTORY_SEPARATOR . $siteFromBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $content->uri,
                                $siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $newUri
                            );
                            chmod($siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $newUri, 0777);
                        }

                        foreach ($sizes as $size) {
                            if (file_exists($siteFromDocRoot . DIRECTORY_SEPARATOR . $siteFromBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $content->uri)) {
                                if (!file_exists($siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size)) {
                                    mkdir($siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size);
                                    copy(
                                        $siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'index.html',
                                        $siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . 'index.html'
                                    );
                                }
                                copy(
                                    $siteFromDocRoot . DIRECTORY_SEPARATOR . $siteFromBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $content->uri,
                                    $siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $newUri
                                );
                                chmod($siteToDocRoot . DIRECTORY_SEPARATOR . $siteToBasePath . CONTENT_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $newUri, 0777);
                            }
                        }

                        $newContent->uri = $newUri;
                        $newContent->update();
                    }

                    $fv = \Phire\Table\FieldValues::findAll(null, array('model_id' => $content->id));
                    if (isset($fv->rows[0])) {
                        foreach ($fv->rows as $f) {
                            $field = \Phire\Table\Fields::findById($f->field_id);
                            if (isset($field->id) && ($field->type != 'file')) {
                                // Change out the site base path
                                if ((strpos($field->type, 'text') !== false) && ($siteFromBasePath != $siteToBasePath)) {
                                    $v = json_encode(str_replace($search, $replace, json_decode($f->value, true)));
                                    if (null !== $f->history) {
                                        $history = json_decode($f->history, true);
                                        foreach ($history as $key => $value) {
                                            $history[$key] = str_replace($search, $replace, $value);
                                        }
                                        $h = json_encode($history);
                                    } else {
                                        $h = $f->history;
                                    }
                                } else {
                                    $v = $f->value;
                                    $h = $f->history;
                                }

                                $newFv = new \Phire\Table\FieldValues(array(
                                    'field_id'  => $f->field_id,
                                    'model_id'  => $newContent->id,
                                    'value'     => $v,
                                    'timestamp' => $f->timestamp,
                                    'history'   => $h,
                                ));
                                $newFv->save();
                            }
                        }
                    }
                }

                if (null !== $newContentId) {
                    // Save any content categories
                    $cats = Table\ContentToCategories::findAll(null, array('content_id' => $content->id));
                    if (isset($cats->rows[0])) {
                        foreach ($cats->rows as $cat) {
                            $contentToCategory = new Table\ContentToCategories(array(
                                'content_id'  => $newContentId,
                                'category_id' => $cat->category_id
                            ));
                            $contentToCategory->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Change child URIs
     *
     * @param  string $newUri
     * @param  array  $children
     * @return void
     */
    protected function changeChildUris($newUri, $children)
    {
        foreach ($children as $child) {
            $c = Table\Content::findById($child->id);
            if (isset($c->id)) {
                $c->uri = $newUri . '/' . $c->slug;
                $c->update();
                $chldren = Table\Content::findAll(null, array('parent_id' => $c->id));
                if (isset($chldren->rows[0])) {
                    $this->changeChildUris($c->uri, $chldren->rows);
                }
            }
        }
    }

}

