<?php
/**
 * @namespace
 */
namespace Content\Model;

abstract class AbstractModel extends \Phire\Model\AbstractModel
{

    /**
     * Override method to filter the content and replace any placeholders
     *
     * @param   array $data
     * @param   int   $siteId
     * @returns array
     */
    protected function filterContent(array $data = null, $siteId = null)
    {
        $dataAry = (null === $data) ? $this->data : $data;

        if (isset($dataAry['site_id'])) {
            $siteId = (int)$dataAry['site_id'];
        } else {
            $siteId = (int)$siteId;
        }

        $site = \Phire\Table\Sites::getSite($siteId);
        $keys = array_keys($dataAry);

        foreach ($dataAry as $key => $value) {
            if (is_string($value)) {
                $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                $value = str_replace(array('[{base_path}]', '[{content_path}]'), array($site->base_path, CONTENT_PATH), $value);
                if (strpos($value, '[{') !== false) {
                    $value = Template::parse($value);
                }
                if (strpos($value, '[{categor') !== false) {
                    $catAry = Template::parseCategories($value);
                    $view = new \Pop\Mvc\View($value, $catAry);
                    $value = $view->render(true);
                }

                foreach ($keys as $k) {
                    if ((strpos($value, '[{' . $k . '}]') !== false) && ($dataAry[$k])) {
                        $value = str_replace('[{' . $k . '}]', $dataAry[$k], $value);
                    }
                }
                $dataAry[$key] = $value;
            } else if (is_array($value)) {
                $dataAry[$key] = $this->filterContent($value, $siteId);
            }
        }

        if (null === $data) {
            $this->data = $dataAry;
        } else {
            $this->data = array_merge($this->data, $dataAry);
        }

        return $dataAry;
    }
}

