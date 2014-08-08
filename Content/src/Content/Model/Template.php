<?php
/**
 * @namespace
 */
namespace Content\Model;

use Pop\Data\Type\Html;
use Pop\Web\Session;
use Content\Form;
use Content\Table;

class Template extends AbstractModel
{

    /**
     * Static method to parse placeholders within string content
     *
     * @param  string $tmp
     * @param  mixed  $id
     * @param  mixed  $pid
     * @return string
     */
    public static function parse($tmp, $id = null, $pid = null)
    {
        // Parse any date placeholders
        $dates = array();
        preg_match_all('/\[\{date.*\}\]/', $tmp, $dates);
        if (isset($dates[0]) && isset($dates[0][0])) {
            foreach ($dates[0] as $date) {
                $pattern = str_replace('}]', '', substr($date, (strpos($date, '_') + 1)));
                $tmp = str_replace($date, date($pattern), $tmp);
            }
        }

        // Parse any archive placeholders
        $archives = array();
        preg_match_all('/\[\{archive.*\}\]/', $tmp, $archives);
        if (isset($archives[0]) && isset($archives[0][0])) {
            $site  = \Phire\Table\Sites::getSite();
            $phire = new Phire();
            foreach ($archives[0] as $archive) {
                $formattedArchive = null;
                $year = str_replace('}]', '', substr($archive, (strpos($archive, '_') + 1)));
                $arc = $phire->getContentByDate($year . '-12-31 23:59:59', $year . '-01-01 00:00:00', 0);
                $tmp = str_replace($archive, '<a href="' . $site->base_path . '/' . $year .'">' . $year . ' (' . count($arc) . ')</a>', $tmp);
            }
        }

        // Parse any template placeholders
        $tmpls = array();
        preg_match_all('/\[\{template_.*\}\]/', $tmp, $tmpls);
        if (isset($tmpls[0]) && isset($tmpls[0][0])) {
            foreach ($tmpls[0] as $tmpl) {
                $t = str_replace('}]', '', substr($tmpl, (strpos($tmpl, '_') + 1)));
                if (($t != $id) && ($t != $pid)) {
                    $template = (is_numeric($t)) ? Table\Templates::findById($t) : Table\Templates::findBy(array('name' => $t));
                    if (isset($template->id)) {
                        $t = self::parse(html_entity_decode($template->template, ENT_QUOTES, 'UTF-8'), $template->id, $id);
                        $tmp = str_replace($tmpl, $t, $tmp);
                    } else {
                        $tmp = str_replace($tmpl, '', $tmp);
                    }
                } else {
                    $tmp = str_replace($tmpl, '', $tmp);
                }
            }
        }

        // Parse any session placeholder
        $open  = array();
        $close = array();
        $merge = array();
        $sess  = array();
        preg_match_all('/\[\{sess\}\]/msi', $tmp, $open, PREG_OFFSET_CAPTURE);
        preg_match_all('/\[\{\/sess\}\]/msi', $tmp, $close, PREG_OFFSET_CAPTURE);

        // If matches are found, format and merge the results.
        if ((isset($open[0][0])) && (isset($close[0][0]))) {
            foreach ($open[0] as $key => $value) {
                $merge[] = array($open[0][$key][0] => $open[0][$key][1], $close[0][$key][0] => $close[0][$key][1]);
            }
        }
        foreach ($merge as $match) {
            $sess[] = substr($tmp, $match['[{sess}]'], (($match['[{/sess}]'] - $match['[{sess}]']) + 9));
        }

        if (count($sess) > 0) {
            $session = Session::getInstance();
            foreach ($sess as $s) {
                $sessString = str_replace(array('[{sess}]', '[{/sess}]'), array('', ''), $s);
                $isSess = null;
                $noSess = null;
                if (strpos($sessString, '[{or}]') !== false) {
                    $sessValues = explode('[{or}]', $sessString);
                    if (isset($sessValues[0])) {
                        $isSess = $sessValues[0];
                    }
                    if (isset($sessValues[1])) {
                        $noSess = $sessValues[1];
                    }
                } else {
                    $isSess = $sessString;
                }
                if (null !== $isSess) {
                    if (!isset($session->user)) {
                        $tmp = str_replace($s, $noSess, $tmp);
                    } else {
                        $newSess = $isSess;
                        foreach ($_SESSION as $sessKey => $sessValue) {
                            if ((is_array($sessValue) || ($sessValue instanceof \ArrayObject)) && (strpos($tmp, '[{' . $sessKey . '->') !== false)) {
                                foreach ($sessValue as $sessK => $sessV) {
                                    if (!is_array($sessV)) {
                                        $newSess = str_replace('[{' . $sessKey . '->' . $sessK . '}]', $sessV, $newSess);
                                    }
                                }
                            } else if (!is_array($sessValue) && !($sessValue instanceof \ArrayObject) && (strpos($tmp, '[{' . $sessKey) !== false)) {
                                $newSess = str_replace('[{' . $sessKey . '}]', $sessValue, $newSess);
                            }
                        }
                        if ($newSess != $isSess) {
                            $tmp = str_replace('[{sess}]' . $sessString . '[{/sess}]', $newSess, $tmp);
                        } else {
                            $tmp = str_replace($s, $noSess, $tmp);
                        }
                    }
                } else {
                    $tmp = str_replace($s, '', $tmp);
                }
            }
        }

        return $tmp;
    }

    /**
     * Parse categories method
     *
     * @param  mixed   $template
     * @return array
     */
    public static function parseCategories($template)
    {
        $catAry = array();
        $cats   = array();
        $phire  = new Phire();

        preg_match_all('/\[\{category_.*\}\]/', $template, $cats);
        if (isset($cats[0]) && isset($cats[0][0])) {
            foreach ($cats[0] as $cat) {
                $c = str_replace('}]', '', substr($cat, (strpos($cat, '_') + 1)));
                if (strpos($c, 'nav_') !== false) {
                    $id = (int)substr($c, (strpos($c, '_') + 1));
                    $catAry['category_nav_' . $id] =$phire->getCategoryNavigation($id);
                } else if ($c != 'nav') {
                    if (strpos($c, '_') !== false) {
                        $cAry = explode('_', $c);
                        $ct = $cAry[0];
                        $orderBy = (isset($cAry[1])) ? $cAry[1] : 'id ASC';
                        $limit = (isset($cAry[2])) ? $cAry[2] : null;
                        $cont = $phire->getContentByCategory($ct, $orderBy, $limit);
                    } else {
                        $cont = $phire->getContentByCategory($c, 'order ASC', null);
                    }

                    foreach ($cont as $key => $value) {
                        if (isset($value->created)) {
                            $value->created = date($phire->config->datetime_format, strtotime($value->created));
                        }
                        if (isset($value->updated)) {
                            $value->updated = date($phire->config->datetime_format, strtotime($value->updated));
                        }
                        if (isset($value->publish)) {
                            $value->publish = date($phire->config->datetime_format, strtotime($value->publish));
                        }

                        $iter = $value->getIterator();

                        // Get any substr conditions, i.e., [{content_150}]
                        foreach ($iter as $k => $v) {
                            if (strpos($template, '[{' . $k . '_') !== false) {
                                $keyMatches = array();
                                preg_match_all('/\[\{' . $k . '_.*\}\]/', $template, $keyMatches, PREG_OFFSET_CAPTURE);
                                if (isset($keyMatches[0]) && isset($keyMatches[0][0])) {
                                    foreach ($keyMatches[0] as $kMatch) {
                                        $d = substr($kMatch[0], (strpos($kMatch[0], '[{' . $k . '_') + strlen('[{' . $k . '_')));
                                        $d = substr($d, 0, strpos($d, '}'));
                                        if (isset($value[$k]) && is_numeric($d)) {
                                            $value[$k . '_' . $d] = substr(strip_tags($v), 0, $d);
                                            $iter->offsetUnset($k);
                                        }
                                    }
                                }
                            }
                        }

                        $cont[$key] = $value;
                    }

                    $catAry['category_' . $c] = $cont;
                }
            }
        }

        $cats = array();
        preg_match_all('/\[\{categories_.*\}\]/', $template, $cats);
        if (isset($cats[0]) && isset($cats[0][0])) {
            foreach ($cats[0] as $cat) {
                $c = str_replace('}]', '', substr($cat, (strpos($cat, '_') + 1)));
                if (strpos($c, '_') !== false) {
                    $cAry = explode('_', $c);
                    $ct = $cAry[0];
                    $limit = (isset($cAry[1])) ? $cAry[1] : null;
                    $catAry['categories_' . $c] = $phire->getChildCategories($ct, $limit);
                } else {
                    $catAry['categories_' . $c] = $phire->getChildCategories($c, null);
                }
            }
        }

        return $catAry;
    }

    /**
     * Parse recent content method
     *
     * @param  mixed   $template
     * @return array
     */
    public static function parseRecent($template)
    {
        $recentAry = array();
        $recent    = array();
        $phire     = new Phire();
        preg_match_all('/\[\{recent_.*\}\]/', $template, $recent);
        if (isset($recent[0]) && isset($recent[0][0])) {
            foreach ($recent[0] as $rec) {
                $c = str_replace('}]', '', substr($rec, (strpos($rec, '_') + 1)));
                if (is_numeric($c)) {
                    $recentAry['recent_' . $c] = $phire->getContentByDate(null, null, $c);
                }
            }
        }

        $config = \Phire\Table\Config::getSystemConfig();

        foreach ($recentAry['recent_' . $c] as $key => $recent) {
            $recentAry['recent_' . $c][$key]['publish'] = date($config->datetime_format, strtotime($recent->publish));
            foreach ($recent as $k => $v) {
                $matches = array();
                preg_match_all('/\[\{' . $k . '_\d+\}\]/', $template, $matches);
                if (isset($matches[0]) && isset($matches[0][0])) {
                    $count = substr($matches[0][0], (strpos($matches[0][0], '_') + 1));
                    $count = substr($count, 0, strpos($count, '}]'));
                    $recentAry['recent_' . $c][$key][$k . '_' . $count] = substr(strip_tags($recent[$k]), 0, $count);
                }
            }
        }

        return $recentAry;
    }

    /**
     * Get all templates method
     *
     * @param  string $sort
     * @param  string $page
     * @return void
     */
    public function getAll($sort = null, $page = null)
    {
        $order = $this->getSortOrder($sort, $page);
        $templates = Table\Templates::findAll($order['field'] . ' ' . $order['order']);
        $templateAry = array();

        foreach ($templates->rows as $template) {
            if (null === $template->parent_id) {
                $tmplAry = array(
                    'template' => $template,
                    'children' => array()
                );
                $children = Table\Templates::findAll('id ASC', array('parent_id' => $template->id));
                foreach ($children->rows as $child) {
                    $tmplAry['children'][] = $child;
                }
                $templateAry[] = $tmplAry;
            }
        }

        if ($this->data['acl']->isAuth('Content\Controller\Content\TemplatesController', 'remove')) {
            $removeCheckbox = '<input type="checkbox" name="remove_templates[]" id="remove_templates[{i}]" value="[{id}]" />';
            $removeCheckAll = '<input type="checkbox" id="checkall" name="checkall" value="remove_templates" />';
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

        $options = array(
            'form' => array(
                'id'      => 'template-remove-form',
                'action'  => BASE_PATH . APP_URI . '/structure/templates/remove',
                'method'  => 'post',
                'process' => $removeCheckbox,
                'submit'  => $submit
            ),
            'table' => array(
                'headers' => array(
                    'id'      => '<a href="' . BASE_PATH . APP_URI . '/structure/templates?sort=id">#</a>',
                    'edit'    => '<span style="display: block; margin: 0 auto; width: 100%; text-align: center;">' . $this->i18n->__('Edit') . '</span>',
                    'name'    => '<a href="' . BASE_PATH . APP_URI . '/structure/templates?sort=name">' . $this->i18n->__('Name') . '</a>',
                    'copy'    => '<span style="display: block; margin: 0 auto; width: 100%; text-align: center;">' . $this->i18n->__('Copy') . '</span>',
                    'process' => $removeCheckAll
                ),
                'class'       => 'data-table',
                'cellpadding' => 0,
                'cellspacing' => 0,
                'border'      => 0
            ),
            'separator' => '',
            'exclude'   => array('parent_id', 'template'),
            'indent'    => '        '
        );

        // Get template children
        $tmplAry = array();
        $devices = Form\Template::getMobileTemplates();
        if (isset($templateAry[0])) {
            foreach ($templateAry as $tmpl) {
                $t = (array)$tmpl['template'];

                if ($this->data['acl']->isAuth('Content\Controller\Content\TemplatesController', 'edit')) {
                    $t['edit'] = '<a class="edit-link" title="' . $this->i18n->__('Edit') . '" href="http://' . $_SERVER['HTTP_HOST'] . BASE_PATH . APP_URI . '/structure/templates/edit/' . $t['id'] . '">Edit</a>';
                } else {
                    $t['edit'] = null;
                }

                if ($this->data['acl']->isAuth('Content\Controller\Content\TemplatesController', 'copy')) {
                    $t['copy'] = '<a class="copy-link" href="' . BASE_PATH . APP_URI . '/structure/templates/copy/' . $t['id'] .'">Copy</a>';
                } else {
                    $t['copy'] = null;
                    unset($options['table']['headers']['copy']);
                }

                $t['device'] = $devices[$t['device']];

                $tAry = array(
                    'id' => $t['id']
                );

                $tAry['name'] = $t['name'];
                $tAry['content_type'] = $t['content_type'];
                $tAry['device'] = $t['device'];

                if (null !== $t['edit']) {
                    $tAry['edit'] = $t['edit'];
                }

                if (null !== $t['copy']) {
                    $tAry['copy'] = $t['copy'];
                }

                $tmplAry[] = $tAry;

                // Get child templates
                if (count($tmpl['children']) > 0) {
                    foreach ($tmpl['children'] as $child) {
                        $c = (array)$child;

                        if ($this->data['acl']->isAuth('Content\Controller\Content\TemplatesController', 'edit')) {
                            $c['edit'] = '<a class="edit-link" title="' . $this->i18n->__('Edit') . '" href="http://' . $_SERVER['HTTP_HOST'] . BASE_PATH . APP_URI . '/structure/templates/edit/' . $c['id'] . '">Edit</a>';
                        } else {
                            $c['edit'] = null;
                        }
                        $c['name'] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&gt; ' . $c['name'];
                        $c['device'] = $devices[$c['device']];
                        if ($this->data['acl']->isAuth('Content\Controller\Content\TemplatesController', 'copy')) {
                            $c['copy'] = '<a class="copy-link" href="' . BASE_PATH . APP_URI . '/structure/templates/copy/' . $c['id'] .'">Copy</a>';
                        } else {
                            $c['copy'] = null;
                        }

                        $cAry = array(
                            'id' => $c['id']
                        );

                        $cAry['name'] = $c['name'];
                        $cAry['content_type'] = $c['content_type'];
                        $cAry['device'] = $c['device'];

                        if (null !== $c['edit']) {
                            $cAry['edit'] = $c['edit'];
                        }

                        if (null !== $c['copy']) {
                            $cAry['copy'] = $c['copy'];
                        }

                        $tmplAry[] = $cAry;
                    }
                }
            }

            $table = Html::encode($tmplAry, $options);

            if ($this->data['acl']->isAuth('Content\Controller\Content\TemplatesController', 'edit')) {
                $tableLines = explode(PHP_EOL, $table);

                // Clean up the table
                foreach ($tableLines as $key => $value) {
                    if (strpos($value, '">&') !== false) {
                        $str = substr($value, (strpos($value, '">&') + 2));
                        $str = substr($str, 0, (strpos($str, ' ') + 1));
                        $value = str_replace($str, '', $value);
                        $tableLines[$key] = str_replace('<td><a', '<td>' . $str . '<a', $value);
                    }
                }
                $table = implode(PHP_EOL, $tableLines);
            }

            $this->data['table'] = $table;
        }
    }

    /**
     * Get template by ID method
     *
     * @param  int     $id
     * @return void
     */
    public function getById($id)
    {
        $template = Table\Templates::findById($id);

        if (isset($template->id)) {
            $templateValues = $template->getValues();
            $templateValues = array_merge($templateValues, \Phire\Model\FieldValue::getAll($id));
            $this->data = array_merge($this->data, $templateValues);
        }
    }

    /**
     * Save template
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function save(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $template = new Table\Templates(array(
            'parent_id'    => (((int)$fields['parent_id'] != 0) ? (int)$fields['parent_id'] : null),
            'name'         => $fields['name'],
            'content_type' => $fields['content_type'],
            'device'       => $fields['device'],
            'template'     => $fields['template']
        ));

        $template->save();
        $this->data['id'] = $template->id;

        \Phire\Model\FieldValue::save($fields, $template->id);
    }

    /**
     * Update template
     *
     * @param \Pop\Form\Form $form
     * @return void
     */
    public function update(\Pop\Form\Form $form)
    {
        $fields = $form->getFields();

        $template = Table\Templates::findById($fields['id']);
        $template->parent_id    = (((int)$fields['parent_id'] != 0) ? (int)$fields['parent_id'] : null);
        $template->name         = $fields['name'];
        $template->content_type = $fields['content_type'];
        $template->device       = $fields['device'];
        $template->template     = $fields['template'];
        $template->update();

        $this->data['id'] = $template->id;

        \Phire\Model\FieldValue::update($fields, $template->id);
    }

    /**
     * Copy template
     *
     * @param  int     $tid
     * @return void
     */
    public function copy($tid)
    {
        $template = Table\Templates::findById($tid);

        if (isset($template->id)) {
            // Check for dupe names
            $i = 1;
            $orgName = $template->name;
            $name = $orgName . ' (Copy ' . $i . ')';

            $dupe = Table\Templates::findBy(array('name' => $name));
            while (isset($dupe->id)) {
                $i++;
                $name = $orgName . ' (Copy ' . $i . ')';
                $dupe = Table\Templates::findBy(array('name' => $name));
            }

            $newTemplate = new Table\Templates(array(
                'parent_id'    => $template->parent_id,
                'name'         => $name,
                'content_type' => $template->content_type,
                'device'       => $template->device,
                'template'     => $template->template
            ));

            $newTemplate->save();

            $values = \Phire\Table\FieldValues::findAll(null, array('model_id' => $template->id));
            if (isset($values->rows[0])) {
                foreach ($values->rows as $value) {
                    $field = \Phire\Table\Fields::findById($value->field_id);
                    if (isset($field->id) && ($field->type != 'file') && (null === $field->group_id)) {
                        $val = new \Phire\Table\FieldValues(array(
                            'field_id'  => $value->field_id,
                            'model_id'  => $newTemplate->id,
                            'value'     => $value->value,
                            'timestamp' => $value->timestamp,
                            'history'   => $value->history
                        ));
                        $val->save();
                    }
                }
            }

            // Copy template children, if any
            $children = Table\Templates::findAll(null, array('parent_id' => $template->id));
            if (isset($children->rows[0])) {
                foreach ($children->rows as $child) {
                    // Check for dupe names
                    $i = 1;
                    $orgName = $child->name;
                    $name = $orgName . ' (Copy ' . $i . ')';

                    $dupe = Table\Templates::findBy(array('name' => $name));
                    while (isset($dupe->id)) {
                        $i++;
                        $name = $orgName . ' (Copy ' . $i . ')';
                        $dupe = Table\Templates::findBy(array('name' => $name));
                    }

                    $newChild = new Table\Templates(array(
                        'parent_id'    => $newTemplate->id,
                        'name'         => $name,
                        'content_type' => $child->content_type,
                        'device'       => $child->device,
                        'template'     => $child->template
                    ));

                    $newChild->save();

                    $values = \Phire\Table\FieldValues::findAll(null, array('model_id' => $child->id));
                    if (isset($values->rows[0])) {
                        foreach ($values->rows as $value) {
                            $field = Table\Fields::findById($value->field_id);
                            if (isset($field->id) && ($field->type != 'file')) {
                                $val = new \Phire\Table\FieldValues(array(
                                    'field_id'  => $value->field_id,
                                    'model_id'  => $newChild->id,
                                    'value'     => $value->value,
                                    'timestamp' => $value->timestamp,
                                    'history'   => $value->history
                                ));
                                $val->save();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Remove template
     *
     * @param  array   $post
     * @return void
     */
    public function remove(array $post)
    {
        if (isset($post['remove_templates'])) {
            foreach ($post['remove_templates'] as $id) {
                $template = Table\Templates::findById($id);
                if (isset($template->id)) {
                    $template->delete();
                }

                \Phire\Model\FieldValue::remove($id);
            }
        }
    }

}

