<?php
/**
 * User: Timkin Dmitriy
 * Date: 14.03.18
 * Time: 14:48
 */

namespace Walma\Aeromar\Ekam;


class EkamCashbox extends Api\Helper
{
    public $commonUrl = "/external/v2/cashboxes";
    public $baseCashboxId = 0;

    public function getBaseCashboxId()
    {
        if (!empty($this->baseCashboxId)) {
            return $this->baseCashboxId;
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
            $this->baseCashboxId = $rootItem['id'];
            return $this->baseCashboxId;
        }
        throw new \ErrorException("Can`t get base cashbox ($url). Error: " . $this->error);
    }

    public function setRegistrationCode($regCode)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->setPostParams(['authentication_code' => $regCode]);
        parent::get($this->commonUrl . "/" . $this->getBaseCashboxId() . "?access_token=" . $this->getToken());
    }
}