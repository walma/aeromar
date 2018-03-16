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
    public $rootCategoryId = 0;

    public function deleteAllCategories()
    {
        $rootCatId = $this->getRootCategoryId();
        parent::get($this->getUrl());
        if (empty($this->error)) {
            $listCategories = $this->response['items'];
            foreach ($listCategories as $category) {
                if ($rootCatId === $category['id']) {
                    continue;
                }
                $this->deleteCategory($category['id']);
            }
        }
    }

    public function getRootCategoryId()
    {
        if (!empty($this->rootCategoryId)) {
            return $this->rootCategoryId;
        }
        $this->setParams([
            'from_id=0',
            'limit=1',
            'with_archived=false',
        ]);
        $url = $this->getUrl() . '&'. $this->getParams();
        parent::get($url);

        if (empty($this->error)) {
            $rootItem = reset($this->response['items']);
            $this->rootCategoryId = $rootItem['id'];
            return $this->rootCategoryId;
        }
        throw new \ErrorException("Can`t get root category ($url). Error: " . $this->error);
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

    public function deleteCategory($categoryId)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        parent::get($this->commonUrl . "/$categoryId?access_token=" . $this->getToken());
    }
}