<?php
/**
 * User: Timkin Dmitriy
 * Date: 14.03.18
 * Time: 14:48
 */

namespace Walma\Aeromar\Ekam;


class EkamCategory extends Api\Helper
{
    public $commonUrl = "/external/v2/categories";

    public function getRootCategoryId()
    {
        $this->setParams([
            'from_id=0',
            'limit=1',
            'with_archived=false',
        ]);
        $url = $this->getUrl() . '&'. $this->getParams();
        parent::get($url);

        if (empty($this->error)) {
            $rootItem = reset($this->response['items']);
            return $rootItem['id'];
        }
        throw new \ErrorException("Can`t get root category. Error: " . $this->error);
    }

    public function createCategory($name, $parentCategoryId = null)
    {
        $this->clearPostParams();
        if (empty($parentCategoryId)) {
            $parentCategoryId = $this->getRootCategoryId();
        }
        $this->setPostParams(['title' => $name, 'parent_id_or_uuid' => (string) $parentCategoryId]);
        parent::get($this->getUrl());
    }
}