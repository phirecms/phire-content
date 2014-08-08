<?php
/**
 * Module Name: Content
 * Author: Nick Sagona
 * Description: This is the Content module for Phire 2.0. It is the main content management module for Phire v2.0
 * Version: 1.0
 */

return array(
    'Content' => new \Pop\Config(array(
        'base'   => realpath(__DIR__ . '/../'),
        'config' => realpath(__DIR__ . '/../config'),
        'data'   => realpath(__DIR__ . '/../data'),
        'src'    => realpath(__DIR__ . '/../src'),
        //'view'   => realpath(__DIR__ . '/../view'),
        'install' => function() {
            require_once __DIR__ . '/../src/Content/Table/Content.php';
            require_once __DIR__ . '/../src/Content/Model/AbstractModel.php';
            require_once __DIR__ . '/../src/Content/Model/Extension.php';

            $user = \Phire\Table\Users::findAll('id ASC');
            if (isset($user->rows[0])) {
                $userId = $user->rows[0]->id;
                $content = \Content\Table\Content::findAll();

                $field = \Phire\Table\Fields::findBy(array('name' => 'description'));
                $descFieldId = $field->id;

                $field = \Phire\Table\Fields::findBy(array('name' => 'keywords'));
                $keywordsFieldId = $field->id;

                $field = \Phire\Table\Fields::findBy(array('name' => 'content'));
                $contentFieldId = $field->id;

                $fields = array(
                    'field_' . $descFieldId     => 'This is Phire CMS 2.',
                    'field_' . $keywordsFieldId => 'default site, phire cms 2',
                    'field_' . $contentFieldId  => '<p>This is a default page for Phire CMS 2.</p>',
                );

                $i = 1;
                foreach ($content->rows as $cont) {
                    $c = \Content\Table\Content::findById($cont->id);
                    if (isset($c->id)) {
                        $c->template   = ($i == 1) ? 'index.phtml' : 'sub.phtml';
                        $c->created    = date('Y-m-d H:i:s');
                        $c->publish    = date('Y-m-d H:i:s');
                        $c->created_by = $userId;
                        $c->update();

                        \Phire\Model\FieldValue::save($fields, $c->id, 'GET');
                        $i++;
                    }
                }
            }

            copy(__DIR__ . '/../data/default.zip', __DIR__ . '/../../../themes/default.zip');
            chmod(__DIR__ . '/../../../themes/default.zip', 0777);
            $ext = new \Content\Model\Extension();
            $ext->getThemes();
            $ext->installThemes();
        },
        // Main Content Routes
        'routes' => array(
            '/' => 'Content\Controller\IndexController',
            APP_URI => array(
                '/content'  => array(
                    '/'           => 'Content\Controller\Content\IndexController',
                    '/types'      => 'Content\Controller\Content\TypesController',
                    '/config'     => 'Content\Controller\Content\ConfigController',
                ),
                '/structure' => array(
                    '/categories' => 'Content\Controller\Structure\CategoriesController',
                    '/navigation' => 'Content\Controller\Structure\NavigationController',
                    '/templates'  => 'Content\Controller\Structure\TemplatesController'
                ),
                '/extensions' => array(
                    '/themes' => 'Content\Controller\Extensions\ThemesController'
                )
            )
        ),
        'module_nav' => array(
            array(
                'name' => 'Content',
                'href' => BASE_PATH . APP_URI . '/content/config',
                'acl'  => array(
                    'resource'   => 'Content\Controller\Content\ConfigController',
                    'permission' => 'index'
                )
            )
        ),
        // Main Content Navigation
        'nav'    => array(
            array(
                'name' => 'Content',
                'href' => BASE_PATH . APP_URI . '/content',
                'acl' => array(
                    'resource'   => 'Content\Controller\Content\IndexController',
                    'permission' => 'index'
                ),
                'children' => array(
                    array(
                        'name' => 'Content',
                        'href' => '',
                        'acl' => array(
                            'resource'   => 'Content\Controller\Content\IndexController',
                            'permission' => 'index'
                        )
                    ),
                    array(
                        'name' => 'Content Types',
                        'href' => 'types',
                        'acl' => array(
                            'resource'   => 'Content\Controller\Content\TypesController',
                            'permission' => 'index'
                        )
                    )
                )
            )
        ),
        'events' => array(
            'dispatch.pre' => array(
                'action' => function($router) {
                    if (($router->getRequest()->isPost()) &&
                        ($router->getControllerClass() == 'Phire\Controller\Phire\Config\SitesController') &&
                        ($router->getAction() == 'remove')) {
                        if (isset($_POST['remove_sites'])) {
                            foreach ($_POST['remove_sites'] as $id) {
                                $content = new \Content\Table\Content();
                                $content->delete(array('site_id' => (int)$id));
                            }
                        }
                    }
                    if (($router->getRequest()->isPost()) &&
                        ($router->getControllerClass() == 'Phire\Controller\Phire\Extensions\IndexController') &&
                        ($router->getAction() == 'themes') && (null !== $router->getRequest()->getPath(1)) &&
                        ($router->getRequest()->getPath(1) == 'process')) {
                        $post = $router->getRequest()->getPost();
                        if (isset($post['remove_themes'])) {
                            foreach ($post['remove_themes'] as $id) {
                                $ext = \Phire\Table\Extensions::findById($id);

                                if (isset($ext->id)) {
                                    $assets = unserialize($ext->assets);
                                    $tmpls = array();

                                    foreach ($assets['templates'] as $key => $value) {
                                        if (strpos($key, 'template_') !== false) {
                                            $tmpls[] = substr($key, (strpos($key, '_') + 1));
                                        }
                                    }

                                    if (count($tmpls) > 0) {
                                        foreach ($tmpls as $tId) {
                                            $t = \Content\Table\Templates::findById($tId);
                                            if (isset($t->id)) {
                                                $t->delete();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                },
                'priority' => 1000
            ),
            'dispatch' => array(
                'action' => function($controller) {
                    $view = $controller->getView();

                    if ($controller instanceof \Phire\Controller\Phire\IndexController) {
                        $view->set('phire', new \Content\Model\Phire());
                        if ($controller->getProject()->router()->getAction() == 'index') {
                            $content = new \Content\Model\Content();
                            $ext     = new \Content\Model\Extension();
                            $view->set('recent', $content->getRecent());
                            $view->set('themes', $ext->getAllThemes());
                            if (strpos(str_replace('\\', '/', realpath($view->getTemplate())), 'vendor/Phire/view/phire/index.phtml') !== false) {
                                $view->setTemplate(__DIR__ . '/../view/phire/index.phtml');
                            }
                        }
                    }

                    if (($controller instanceof \Phire\Controller\Phire\Structure\IndexController) && ($controller->getProject()->router()->getAction() == 'index')) {
                        $view->setTemplate(__DIR__ . '/../view/structure/index.phtml');
                    }

                    if (($controller instanceof \Phire\Controller\Phire\Extensions\IndexController) && ($controller->getProject()->router()->getAction() == 'index')) {
                        $view->setTemplate(__DIR__ . '/../view/extensions/index.phtml');
                    }

                    if (($controller instanceof \Phire\Controller\Phire\Config\SitesController) && ($controller->getProject()->router()->getAction() == 'index')) {
                        $view->setTemplate(__DIR__ . '/../view/phire/config/sites.phtml');
                    }

                    // Add content types to phireNav
                    if (isset($view->phireNav) && isset($view->acl) && isset($view->role) && ($view->acl->hasRole($view->role->getName()))) {
                        $view->phireNav->setConfig(array(
                            'top' => array(
                                'node'  => 'ul',
                                'id'    => 'phire-nav'
                            ),
                        ));
                        $view->phireNav->setAcl($view->acl);
                        $view->phireNav->setRole($view->role);

                        $tree = $view->phireNav->getTree();

                        // If the sub-children haven't been added yet
                        if (isset($tree[0])) {
                            $i18n = \Phire\Table\Config::getI18n();

                            $view->phireNav->addLeaf($i18n->__('Structure'), array(
                                'name' => $i18n->__('Templates'),
                                'href' => 'templates',
                                'acl' => array(
                                    'resource'   => 'Content\Controller\Structure\TemplatesController',
                                    'permission' => 'index'
                                )
                            ));

                            $view->phireNav->addLeaf($i18n->__('Structure'), array(
                                'name' => $i18n->__('Navigation'),
                                'href' => 'navigation',
                                'acl' => array(
                                    'resource'   => 'Content\Controller\Structure\NavigationController',
                                    'permission' => 'index'
                                )
                            ));

                            $view->phireNav->addLeaf($i18n->__('Structure'), array(
                                'name' => $i18n->__('Categories'),
                                'href' => 'categories',
                                'acl' => array(
                                    'resource'   => 'Content\Controller\Structure\CategoriesController',
                                    'permission' => 'index'
                                )
                            ));

                            $view->phireNav->addLeaf($i18n->__('Extensions'), array(
                                'name' => $i18n->__('Themes'),
                                'href' => 'themes',
                                'acl' => array(
                                    'resource'   => 'Content\Controller\Extensions\ThemesController',
                                    'permission' => 'index'
                                )
                            ));

                            // And any content types to the main phire nav
                            $contentTypes = \Content\Table\ContentTypes::findAll('order ASC');
                            if (isset($contentTypes->rows)) {
                                foreach ($contentTypes->rows as $type) {
                                    $perm = 'index_' . $type->id;
                                    if ($view->acl->isAuth('Content\Controller\Content\IndexController', 'index') &&
                                        $view->acl->isAuth('Content\Controller\Content\IndexController', 'index_' . $type->id)) {
                                        $perm = 'index';
                                    }

                                    $view->phireNav->addLeaf($i18n->__('Content'), array(
                                        'name'     => $type->name,
                                        'href'     => 'index/' . $type->id,
                                        'acl' => array(
                                            'resource'   => 'Content\Controller\Content\IndexController',
                                            'permission' => $perm
                                        )
                                    ), 1);
                                }
                            }

                            // Set the language
                            $tree = $view->phireNav->getTree();
                            foreach ($tree as $key => $value) {
                                if (isset($value['name'])) {
                                    $tree[$key]['name'] = $i18n->__($value['name']);
                                    if (isset($value['children']) && (count($value['children']) > 0)) {
                                        foreach ($value['children'] as $k => $v) {
                                            if (($v['name'] == 'Fields') && isset($tree[$key]['children'][$k]['children'][0]['name'])) {
                                                $tree[$key]['children'][$k]['children'][0]['name'] = $i18n->__($tree[$key]['children'][$k]['children'][0]['name']);
                                            }
                                            $tree[$key]['children'][$k]['name'] = $i18n->__($v['name']);

                                        }
                                    }
                                }
                            }

                            $view->phireNav->setTree($tree);
                        }

                        $view->phireNav->rebuild();
                        $view->phireNav->nav()->setIndent('    ');
                    }
                },
                'priority' => 1000
            ),
            'dispatch.send' => array(
                'action' => function($controller) {
                    $model = $controller->getView()->getData();
                    $i18n  = \Phire\Table\Config::getI18n();

                    if ((get_class($controller) == 'Content\Controller\IndexController') &&
                        isset($model['incontent_editing']) && ($model['incontent_editing'])) {
                        if (isset($model['phireNav'])) {
                            $body = $controller->getResponse()->getBody();
                            $phireNav = $model['phireNav'];
                            $phireNav->addBranch(array(
                                'name' => $i18n->__('Edit This Page'),
                                'href' => BASE_PATH . APP_URI . '/content/edit/' . $controller->getView()->get('id') . '?live=1',
                                'acl'  => array(
                                    'resource'   => 'Phire\Controller\Phire\Content\IndexController',
                                    'permission' => 'edit_' . $controller->getView()->get('type_id')
                                )
                            ), true);
                            $phireNav->setConfig(array(
                                'top' => array(
                                    'id'         => 'phire-nav-incontent',
                                    'attributes' => array('style' => 'display: none;')
                                )
                            ));
                            $phireNav->rebuild();
                            if (strpos($body, 'jax.3.2.0.min.js') === false) {
                                $body = str_replace('</head>', '    <script type="text/javascript" src="' . BASE_PATH . CONTENT_PATH . '/assets/js/jax.3.2.0.min.js"></script>' . PHP_EOL . '</head>', $body);
                            }
                            $body = str_replace('</head>', '    <script type="text/javascript" src="' . BASE_PATH . CONTENT_PATH . '/assets/content/js/content.edit.js"></script>' . PHP_EOL . '</head>', $body);
                            $body = str_replace('</head>', '    <link type="text/css" rel="stylesheet" href="' . BASE_PATH . CONTENT_PATH . '/assets/content/css/content.edit.css" />' . PHP_EOL . '</head>', $body);
                            $body = str_replace('</body>', '<a id="phire-nav-flame" href="#" onclick="$(\'#phire-nav-incontent\').toggle(); return false;">Open</a>' . PHP_EOL . $phireNav . PHP_EOL . '</body>', $body);
                            $controller->getResponse()->setBody($body);
                        }
                    }
                },
                'priority' => 1000
            ),
            'dispatch.post' => array(
                'action' => function($router) {
                    if (($router->getControllerClass() == 'Phire\Controller\Phire\Extensions\IndexController') &&
                        ($router->getAction() == 'themes') && (null !== $router->getRequest()->getPath(1)) &&
                        ($router->getRequest()->getPath(1) == 'install')) {
                        $extensions = \Phire\Table\Extensions::findAll(null, array('type' => 0));
                        foreach ($extensions->rows as $extension) {
                            $assets = unserialize($extension->assets);
                            $newTemplates = array();
                            if (count($assets['templates']) > 0) {
                                foreach ($assets['templates'] as $key => $template) {
                                    if (strpos($key, 'template_ph_') !== false) {
                                        $file = substr($key, (strrpos($key, '_') + 1));
                                        $tmpl = file_get_contents(realpath($_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $extension->name . DIRECTORY_SEPARATOR . $file));
                                        $tmplName = ucwords(str_replace(array('_', '-'), array(' ', ' '), substr($file, 0, strrpos($file, '.'))));
                                        $t = new \Content\Table\Templates(array(
                                            'name'         => $tmplName,
                                            'content_type' => 'text/html',
                                            'device'       => 'desktop',
                                            'template'     => $tmpl
                                        ));
                                        $t->save();
                                        $newTemplates['template_' . $t->id] = $tmplName;
                                    }
                                }
                                if (count($newTemplates) > 0) {
                                    $assets['templates'] = $newTemplates;
                                    $ext = \Phire\Table\Extensions::findById($extension->id);
                                    if (isset($ext->id)) {
                                        $ext->assets = serialize($assets);
                                        $ext->update();
                                    }
                                }
                            }

                        }
                    }
                },
                'priority' => 1000
            ),
        ),
        'remove' => function() {
            $settings = array(
                'feed_type',
                'feed_limit',
                'open_authoring',
                'incontent_editing'
            );
            foreach ($settings as $setting) {
                $cfg = \Phire\Table\Config::findById($setting);
                if (isset($cfg->setting)) {
                    $cfg->delete();
                }
            }

            $exts = \Phire\Table\Extensions::findAll(null, array('type' => 0));
            foreach ($exts->rows as $ext) {
                $ex = \Phire\Table\Extensions::findById($ext->id);
                if (isset($ex->id)) {
                    if (file_exists(__DIR__ . '/../../../themes/' . $ex->file)) {
                        unlink(__DIR__ . '/../../../themes/' . $ex->file);
                    }
                    if (file_exists(__DIR__ . '/../../../themes/' . $ex->name)) {
                        $dir = new \Pop\File\Dir(__DIR__ . '/../../../themes/' . $ex->name);
                        $dir->emptyDir(null, true);
                    }
                    $ex->delete();
                }
            }

            $fields = \Phire\Table\Fields::findAll();
            foreach ($fields->rows as $field) {
                $models = unserialize($field->models);
                foreach ($models as $key => $model) {
                    if (substr($model['model'], 0, 8) == 'Content\\') {
                        unset($models[$key]);
                    }
                }
                $fld = \Phire\Table\Fields::findById($field->id);
                if (isset($fld->id)) {
                    $fld->models = serialize($models);
                    $fld->update();
                }
            }
        }
    ))
);

