<?php
/**
 * User: Timkin Dmitriy
 * Date: 14.03.18
 * Time: 14:48
 */

namespace Walma\Aeromar\Ekam;


class EkamProduct extends Api\Helper
{
    public $commonUrl = "/external/v2/products";

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

    public function createProduct($arProduct)
    {
        $this->setPostParams($arProduct);
        parent::get($this->getUrl());
    }
}