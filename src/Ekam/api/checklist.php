<?php

namespace Bitrix\CmskassaEkam;

use Bitrix\Main, Bitrix\Main\Localization\Loc;
use Bitrix\CmskassaEkam\Api\Helper;

Loc::loadMessages(__FILE__);

/**
 * Class CheckListTable
 *
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> order_id int mandatory
 * <li> type string(50) mandatory
 * <li> status string(50) mandatory
 * <li> amount string(255) mandatory
 * <li> created_at datetime mandatory
 * <li> fd_number int mandatory
 * <li> factory_kkt_number string(255) mandatory
 * <li> should_print int mandatory
 * <li> electronics_check string(50) mandatory
 * </ul>
 *
 * @package Bitrix\Ekam
 **/
class CheckListTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_ekam_check_list';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'id' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_ID_FIELD'),
            ),
            'login' => array(
                'data_type' => 'string',
                'required' => true,
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_TYPE_FIELD'),
            ),
            'cashbox_id' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_ORDER_ID_FIELD'),
            ),
            'order_id' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_ORDER_ID_FIELD'),
            ),
            'type' => array(
                'data_type' => 'string',
                'required' => true,
                'validation' => array(
                    __CLASS__,
                    'validateType'
                ),
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_TYPE_FIELD'),
            ),
            'status' => array(
                'data_type' => 'string',
                'required' => true,
                'validation' => array(
                    __CLASS__,
                    'validateStatus'
                ),
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_STATUS_FIELD'),
            ),
            'amount' => array(
                'data_type' => 'string',
                'validation' => array(
                    __CLASS__,
                    'validateAmount'
                ),
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_AMOUNT_FIELD'),
            ),
            'cashier' => array(
                'data_type' => 'string',
                'validation' => array(
                    __CLASS__,
                    'validateAmount'
                ),
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_CASHIER_FIELD'),
            ),
            'created_at' => array(
                'data_type' => 'datetime',
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_CREATED_AT_FIELD'),
            ),
            'fd_number' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_FD_NUMBER_FIELD'),
            ),
            'factory_kkt_number' => array(
                'data_type' => 'string',
                'validation' => array(
                    __CLASS__,
                    'validateFactoryKktNumber'
                ),
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_FACTORY_KKT_NUMBER_FIELD'),
            ),
            'should_print' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_SHOULD_PRINT_FIELD'),
            ),
            'electronics_check' => array(
                'data_type' => 'string',
                'validation' => array(
                    __CLASS__,
                    'validateElectronicsCheck'
                ),
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_ELECTRONICS_CHECK_FIELD'),
            ),
            'receipt_url' => array(
                'data_type' => 'string',
                'validation' => array(
                    __CLASS__,
                    'validateFactoryKktNumber'
                ),
                'title' => Loc::getMessage('CHECK_LIST_ENTITY_RECEPIENT_URL_CHECK_FIELD'),
            ),
        );
    }

    /**
     * Returns validators for type field.
     *
     * @return array
     */
    public static function validateType()
    {
        return array(
            new Main\Entity\Validator\Length(null, 50),
        );
    }

    /**
     * Returns validators for status field.
     *
     * @return array
     */
    public static function validateStatus()
    {
        return array(
            new Main\Entity\Validator\Length(null, 50),
        );
    }

    /**
     * Returns validators for amount field.
     *
     * @return array
     */
    public static function validateAmount()
    {
        return array(
            new Main\Entity\Validator\Length(null, 255),
        );
    }

    /**
     * Returns validators for factory_kkt_number field.
     *
     * @return array
     */
    public static function validateFactoryKktNumber()
    {
        return array(
            new Main\Entity\Validator\Length(null, 255),
        );
    }

    /**
     * Returns validators for electronics_check field.
     *
     * @return array
     */
    public static function validateElectronicsCheck()
    {
        return array(
            new Main\Entity\Validator\Length(null, 50),
        );
    }

    public static function getCheckByOrder($orderId)
    {
        $arCheks = array();
        $checkResult = self::getList(array('filter' => array("order_id" => $orderId)));

        while ($arItem = $checkResult->fetch()) {
            if ($arItem["status"] != "error")
                $arCheks[$arItem['type']] = $arItem["order_id"];
        }
        return $arCheks;
    }

    public static function loadUpdates($userLogin)
    {
        global $DB;
        require_once ($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/Cashbox.php');
        $cashboxObj = new \Cashbox();
        $cashboxes = $cashboxObj->getAllCashboxes($userLogin);
        foreach ($cashboxes AS $cashbox) {
            $accessToken = $cashbox['UF_TOKEN'];
            $request = new Helper();
            $request->receiptRequestsGet(array(), $accessToken);
            $response = $request->getResponse();
            $arCurrentCheck = array();
            $result = CheckListTable::getList(array('filter' => array('login' => $userLogin, 'cashbox_id' => $cashbox['ID'])));
            while ($arItem = $result->fetch()) {
                CheckListTable::delete($arItem["id"]);
            }
            foreach ($response["items"] as $arItem) {
                $electronics_check = "";

                if ($arItem["email"]) {
                    $electronics_check = $arItem["email"];
                }

                if ($electronics_check == "" && $arItem["phone_number"]) {
                    $electronics_check = $arItem["phone_number"];
                }

                $objDateTime = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($arItem["created_at"]));

                $arFields = array(
                    "id" => $arItem["id"],
                    "login" => $userLogin,
                    "cashbox_id" => $cashbox['ID'],
                    "order_id" => $arItem["order_id"],
                    "type" => $arItem["type"],
                    "status" => $arItem["status"],
                    "amount" => $arItem["amount"],
                    "cashier" => $arItem["cashier_name"] ? $arItem["cashier_name"] : " ",
                    "created_at" => $objDateTime,
                    "fd_number" => $arItem["fiscal_data"]["fd_number"],
                    "factory_kkt_number" => $arItem["fiscal_data"]["factory_kkt_number"],
                    "receipt_url" => $arItem["receipt_url"],
                    "should_print" => $arItem["should_print"],
                    "electronics_check" => $electronics_check,
                );
                if ($arCurrentCheck[$arItem["id"]]) {
                    unset($arFields["id"]);
                    $result = CheckListTable::update($arItem["id"], $arFields);
                } else {
                    $result = CheckListTable::add($arFields);
                }
            }
        }

        return "\\Bitrix\\CmskassaEkam\\CheckListTable::loadUpdates();";
    }
}