<?php
/**
 * User: Timkin Dmitriy
 * Date: 11.03.18
 * Time: 22:51
 */

const TOKEN = 'AQAAAAAFY-xdAATcbWobDAXuKkguvxm5fcb_3QM';

set_time_limit(0);
ini_set('mbstring.func_overload', '2');
ini_set("memory_limit","1024M");
//ini_set(‘mbstring.internal_encoding’, "UTF-8");

$_SERVER["DOCUMENT_ROOT"] = "/home/t/timkindl/r.walma.ru/public_html/";
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
define("LANG", "s1");

define("BX_UTF", true);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_BUFFER_USED", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
while (ob_get_level())
    ob_end_flush();

require_once dirname(__FILE__) . '/../../../autoload.php';
require_once dirname(__FILE__) . '/ImportFileData.php';

print_r(new \Walma\Aeromar\ImportFileData(TOKEN), true);

require($_SERVER["DOCUMENT_ROOT"]. "/bitrix/modules/main/include/epilog_after.php");