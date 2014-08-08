<?php
/**
 * @namespace
 */
namespace Content\Model;

use Pop\Archive\Archive;
use Pop\File\Dir;
use Content\Table;

class Extension extends AbstractModel
{

    /**
     * Get themes method
     *
     * @return array
     */
    public function getAllThemes()
    {
        $themes = \Phire\Table\Extensions::findAll('id ASC', array('type' => 0));
        return $themes->rows;
    }

    /**
     * Get all themes method
     *
     * @return void
     */
    public function getThemes()
    {
        $themes = \Phire\Table\Extensions::findAll('id ASC', array('type' => 0));
        $themeRows = $themes->rows;

        $themePath = $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/extensions/themes';

        $dir = new Dir($themePath, false, false, false);
        $themeFiles = array();

        $formats = Archive::formats();
        foreach ($dir->getFiles() as $file) {
            if (array_key_exists(substr($file, strrpos($file, '.') + 1), $formats)) {
                $themeFiles[substr($file, 0, strpos($file, '.'))] = $file;
            }
        }

        foreach ($themeRows as $key => $theme) {
            $themeName = $theme->name;
            if (file_exists($themePath . '/' . $theme->name . '/screenshot.jpg')) {
                $themeRows[$key]->screenshot = '<img class="theme-screenshot" src="' . BASE_PATH . CONTENT_PATH . '/extensions/themes/' . $theme->name . '/screenshot.jpg" width="100" />';
            } else if (file_exists($themePath . '/' . $theme->name . '/screenshot.png')) {
                $themeRows[$key]->screenshot = '<img class="theme-screenshot" src="' . BASE_PATH . CONTENT_PATH . '/extensions/themes/' . $theme->name . '/screenshot.png" width="100" />';
            } else {
                $themeRows[$key]->screenshot = null;
            }

            if (isset($themeFiles[$theme->name])) {
                unset($themeFiles[$theme->name]);
            }

            // Get theme info
            $assets = unserialize($theme->assets);
            $themeRows[$key]->author  = '';
            $themeRows[$key]->desc    = '';
            $themeRows[$key]->version = '';

            foreach ($assets['info'] as $k => $v) {
                if (stripos($k, 'name') !== false) {
                    $themeRows[$key]->name = $v;
                } else if (stripos($k, 'author') !== false) {
                    $themeRows[$key]->author = $v;
                } else if (stripos($k, 'desc') !== false) {
                    $themeRows[$key]->desc = $v;
                } else if (stripos($k, 'version') !== false) {
                    $themeRows[$key]->version = $v;
                }
            }

            $latest = '';
            $handle =@ fopen('http://update.phirecms.org/themes/' . strtolower($themeName) . '/version', 'r');
            if ($handle !== false) {
                $latest = trim(stream_get_contents($handle));
                fclose($handle);
            }
            if ((version_compare($themeRows[$key]->version, $latest) < 0) && ($this->data['acl']->isAuth('Phire\Controller\Phire\Config\IndexController', 'update'))) {
                $themeRows[$key]->version .= ' (<a href="' . BASE_PATH . APP_URI . '/config/update?theme=' . $themeName . '">' . $this->i18n->__('Update to') . ' ' . $latest . '</a>?)';
            }
        }

        $this->data['themes'] = $themeRows;
        $this->data['new'] = $themeFiles;
    }

    /**
     * Install themes method
     *
     * @throws \Phire\Exception
     * @return void
     */
    public function installThemes()
    {
        $docRoots = array();

        $sites = \Phire\Table\Sites::findAll();
        foreach ($sites->rows as $site) {
            $docRoots[] = $site->document_root;
        }

        try {
            $themePath = $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/extensions/themes';
            if (!is_writable($themePath)) {
                throw new \Phire\Exception($this->i18n->__('The themes folder is not writable.'));
            }

            $exts = \Phire\Table\Extensions::findAll(null, array('active' => 1));
            foreach ($exts->rows as $ext) {
                $e = \Phire\Table\Extensions::findById($ext->id);
                if (isset($e->id) && ($ext->type == 0)) {
                    $e->active = 0;
                    $e->update();
                }
            }

            $formats = Archive::formats();

            $last = null;
            foreach ($this->data['new'] as $name => $theme) {
                $ext = substr($theme, (strrpos($theme, '.') + 1));
                if (array_key_exists($ext, $formats)) {
                    $archive = new Archive($themePath . '/' . $theme);
                    $archive->extract($themePath . '/');
                    if ((stripos($theme, 'gz') || stripos($theme, 'bz')) && (file_exists($themePath . '/' . $name . '.tar'))) {
                        unlink($themePath . '/' . $name . '.tar');
                    }

                    $templates = array();

                    $dir = new Dir($themePath . '/' . $name);
                    foreach ($dir->getFiles() as $file) {
                        if (stripos($file, '.html') !== false) {
                            $tmplName = ucwords(str_replace(array('_', '-'), array(' ', ' '), substr($file, 0, strrpos($file, '.'))));
                            $templates['template_ph_' . $file] = $tmplName;
                        } else if ((stripos($file, '.phtml') !== false) || (stripos($file, '.php') !== false) || (stripos($file, '.php3') !== false)) {
                            $templates[] = $file;
                        }
                    }

                    $style = null;
                    $info = array();

                    // Check for a style sheet
                    if (file_exists($themePath . '/' . $name . '/style.css')) {
                        $style = $themePath . '/' . $name . '/style.css';
                    } else if (file_exists($themePath . '/' . $name . '/styles.css')) {
                        $style = $themePath . '/' . $name . '/styles.css';
                    } else if (file_exists($themePath . '/' . $name . '/css/style.css')) {
                        $style = $themePath . '/' . $name . '/css/style.css';
                    } else if (file_exists($themePath . '/' . $name . '/css/styles.css')) {
                        $style = $themePath . '/' . $name . '/css/styles.css';
                    }

                    // Try and get theme info from style sheet
                    if (null !== $style) {
                        $css = file_get_contents($style);
                        if (strpos($css, '*/') !== false) {
                            $cssHeader = substr($css, 0, strpos($css, '*/'));
                            $cssHeader = substr($cssHeader, (strpos($cssHeader, '/*') + 2));
                            $cssHeaderAry = explode("\n", $cssHeader);
                            foreach ($cssHeaderAry as $line) {
                                if (strpos($line, ':')) {
                                    $ary = explode(':', $line);
                                    if (isset($ary[0]) && isset($ary[1])) {
                                        $key = trim(str_replace('*', '', $ary[0]));
                                        $value = trim(str_replace('*', '', $ary[1]));
                                        $info[$key] = $value;
                                    }
                                }
                            }
                        }
                    }

                    $ext = new \Phire\Table\Extensions(array(
                        'name'   => $name,
                        'file'   => $theme,
                        'type'   => 0,
                        'active' => 0,
                        'assets' => serialize(array(
                            'templates' => $templates,
                            'info'      => $info
                        ))
                    ));
                    $ext->save();

                    foreach ($docRoots as $docRoot) {
                        $altThemePath = $docRoot . BASE_PATH . CONTENT_PATH . '/extensions/themes';
                        copy($themePath . '/' . $theme, $altThemePath . '/' . $theme);
                        $archive = new Archive($altThemePath . '/' . $theme);
                        $archive->extract($altThemePath . '/');
                        if ((stripos($theme, 'gz') || stripos($theme, 'bz')) && (file_exists($altThemePath . '/' . $name . '.tar'))) {
                            unlink($altThemePath . '/' . $name . '.tar');
                        }
                    }
                }
            }

            if (isset($ext->id)) {
                $ext->active = 1;
                $ext->update();
            }
        } catch (\Exception $e) {
            $this->data['error'] = $e->getMessage();
        }
    }

    /**
     * Process themes method
     *
     * @param  array $post
     * @return void
     */
    public function processThemes($post)
    {
        $sql = \Phire\Table\Extensions::getSql();

        $sql->update(array(
            'active' => 0
        ))->where()->equalTo('type', 0);

        \Phire\Table\Extensions::execute($sql->render(true));

        if (isset($post['theme_active'])) {
            $ext = \Phire\Table\Extensions::findById($post['theme_active']);
            $ext->active = 1;
            $ext->save();
        }

        $active = false;
        if (isset($post['remove_themes'])) {
            $docRoots = array($_SERVER['DOCUMENT_ROOT']);

            $sites = \Phire\Table\Sites::findAll();
            foreach ($sites->rows as $site) {
                $docRoots[] = $site->document_root;
            }

            foreach ($post['remove_themes'] as $id) {
                $ext = \Phire\Table\Extensions::findById($id);

                if (isset($ext->id)) {
                    if ($ext->active) {
                        $active = true;
                    }

                    $assets = unserialize($ext->assets);
                    $tmpls = array();

                    foreach ($assets['templates'] as $key => $value) {
                        if (strpos($key, 'template_') !== false) {
                            $tmpls[] = substr($key, (strpos($key, '_') + 1));
                        }
                    }

                    foreach ($docRoots as $docRoot) {
                        $contentPath = $docRoot . BASE_PATH . CONTENT_PATH;
                        $exts = array('.zip', '.tar.gz', '.tar.bz2', '.tgz', '.tbz', '.tbz2');

                        if (file_exists($contentPath . '/extensions/themes/' . $ext->name)) {
                            $dir = new Dir($contentPath . '/extensions/themes/' . $ext->name);
                            $dir->emptyDir(null, true);
                        }

                        foreach ($exts as $e) {
                            if (file_exists($contentPath . '/extensions/themes/' . $ext->name . $e) &&
                                is_writable($contentPath . '/extensions/themes/' . $ext->name . $e)) {
                                unlink($contentPath . '/extensions/themes/' . $ext->name . $e);
                            }
                        }
                    }

                    $ext->delete();
                }
            }
        }

        if ($active) {
            $themes = \Phire\Table\Extensions::findAll('id ASC', array('type' => 0));
            if (isset($themes->rows[0])) {
                $theme = \Phire\Table\Extensions::findById($themes->rows[0]->id);
                $theme->active = 1;
                $theme->save();
            }
        }
    }

}

