<?
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC", "Y");
define("DisableEventsCheck", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if (!empty($siteId) && is_string($siteId)) {
    define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/api/helper.php');
$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

$authCode = $request->get("auth_code");
$state = $request->get("state");
$request = new \Bitrix\CmskassaEkam\Api\Helper();
$userLogin = $USER->GetLogin();
$clientId = 'multiekam';           // взять из полей пользователя
$clientSecret = 'bd222f38358291b39bcf1d019da2e713c7057cced197c1f24f58a64b9b08';  // взять из полей пользователя

$request->oauth(array(
    "auth_code" => $authCode,
    "client_id" => $clientId,
    "client_secret" => $clientSecret,

));
$arResponse = $request->getResponse();
$accessToken = $arResponse["access_token"];
if ($accessToken != "") {
    $handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/logs/log3.txt', 'a+');
    fwrite($handle, "\n$accessToken\n");
    fclose($handle);

    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/Cashbox.php');
    if (empty($_REQUEST['cashboxId'])) {
        $cashboxObj = new Cashbox(array(
            'login' => $USER->GetLogin(),
            'state' => $state,
            'active' => true,
            'auth_code' => $authCode,
            'token' => $accessToken,
            'name' => 'Касса ' . sprintf("%03d", rand(0, 999)),
        ));
    } else {
        $cashboxId = $_REQUEST['cashboxId'];
        $params = array(
            'UF_TOKEN' => $accessToken,
            'UF_STATE' => $state,
            'UF_AUTH_CODE' => $authCode,
        );
        $cashbox = new Cashbox();
        $entity = $cashbox->getEntityClass();
        $entity::update($cashboxId, $params);
    }

    $APPLICATION->set_cookie("EKAM_AUTH_CODE", $accessToken, 60 * 60 * 24 * 5);
    $APPLICATION->set_cookie("EKAM_STATE", $state, 60 * 60 * 24 * 5);
    $_SESSION["EKAM_MODULE_SUCCESS_AUTH"] = "Y";

//    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/api/checklist.php');
//    Bitrix\CmskassaEkam\Api\CheckListTable::loadUpdates($userLogin, $accessToken);
} else {
    $_SESSION["EKAM_MODULE_AOUTH_ERROR"] = Bitrix\Main\Localization\Loc::getMessage("cmskassa.ekam_AOUTH_ERROR");
}
?>
    <script type="text/javascript">
        if (window.opener) {
            window.opener.location = window.opener.location;
        }
        window.close();
    </script>
<?
die();