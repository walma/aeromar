<?php
namespace Walma\Aeromar\Ekam\Api;
IncludeModuleLangFile(__FILE__);
use Bitrix\Main\Localization\Loc;

class Helper
{
    static $url = 'https://app.ekam.ru/api';
    protected $response = false;
    protected $log = true;
    protected $error = '';
    protected $ch = '';
    protected $httpcode;
    private $_params;
    private $_postParams;
    private $_token;
    public $commonUrl = "";

    static public $requestsCounter = 0;

    function __construct()
    {
//        self::$requestsCounter = 0;
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_POST, 0);

    }

    static public function encode_items($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::encode_items($value);
            } else {
                $array[$key] = mb_convert_encoding($value, 'UTF-8', mb_detect_encoding($value));
            }
        }

        return $array;
    }

    /**
     * @return mixed
     */
    public function getPostParams()
    {
        return $this->_postParams;
    }

    /**
     * @param mixed $postParams
     */
    public function setPostParams($postParams)
    {
        $this->_postParams = $postParams;
    }

    /**
     * @param mixed $postParams
     */
    public function clearPostParams()
    {
        $this->_postParams = null;
    }

    public function isTokenExist($url)
    {
        return strpos($url, 'access_token') !== false ? true : false;
    }

    public function getUrl()
    {
        return $this->commonUrl . '?access_token=' . $this->getToken();
    }

    public function get($method)
    {
        if (!$this->isTokenExist($method)) {
            $this->error = "Токен не установлен";
            return false;
        }
        $url = self::$url . $method;
        curl_setopt($this->ch, CURLOPT_URL, $url);
        $params = $this->getPostParams();
        if (!empty($params)) {
            curl_setopt($this->ch, CURLOPT_POST, 1);
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json',
            ));
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        self::$requestsCounter++;
        $response = curl_exec($this->ch);

        $httpcode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        $this->httpcode = $httpcode;
        if ($this->log) {
            file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/logs/Log.txt", "Last request:\nParams:\n" . print_r($params, 1) . "\n\n" . print_r(json_decode($response, 1), 1) . "\n\nURL: $url");
        }

        $arResponse = json_decode($response, 1);

        if ($httpcode == 200 || $httpcode == 201) {
            $this->response = $arResponse;
            return true;
        } else {
            $this->clearPostParams();
            if ($httpcode == 403) {
                $this->error = "[403] Токен не найден или недействителен";
            }

            if ($httpcode == 404) {
                $this->error = "[404] Элемент не найден";
            }

            if ($httpcode == 422) {
                $this->error = "[422] " . $arResponse["error"];
            }

            if ($httpcode == 500) {
                $this->error = "[500] Сервис временно не отвечает / " . $arResponse["error"];
            }

            return false;
        }
    }

    public function getError()
    {
        return $this->error;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getHttpcode()
    {
        return $this->httpcode;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        if (count($this->_params) > 1) {
            return implode('&', $this->_params);
        } elseif (count($this->_params) === 1) {
            return reset($this->_params);
        }
        return '';
    }

    /**
     * @param array $param
     * один параметр, string: key=value
     */
    public function addParam($param)
    {
        if (is_array($param) && !empty($this->_params)) {
            $this->_params = array_merge($this->_params, $param);
        } else {
            $this->_params = $param;
        }
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    public function getToken()
    {
        return $this->_token;
    }

    public function setToken($token)
    {
        $this->_token = $token;
    }

}