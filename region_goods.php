<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

define("IN_ECS", true);
define("INIT_NO_USERS", true);
define("INIT_NO_SMARTY", true);
require (dirname(__FILE__) . "/includes/init.php");
require (ROOT_PATH . "includes/cls_json.php");
header("Content-type: text/html; charset=" . EC_CHARSET);
$type = (!empty($_REQUEST["type"]) ? intval($_REQUEST["type"]) : 0);
$parent = (!empty($_REQUEST["parent"]) ? intval($_REQUEST["parent"]) : 0);
$user_id = (!empty($_REQUEST["user_id"]) ? intval($_REQUEST["user_id"]) : 0);
$arr["regions"] = get_regions($type, $parent);
$arr["type"] = $type;
$arr["target"] = (!empty($_REQUEST["target"]) ? stripslashes(trim($_REQUEST["target"])) : "");
$arr["target"] = htmlspecialchars($arr["target"]);
$arr["user_id"] = $user_id;
$user_address = get_user_address_region($user_id);
$user_address = explode(",", $user_address["region_address"]);

if (in_array($parent, $user_address)) {
	$arr["isRegion"] = 1;
}
else {
	$arr["isRegion"] = 88;
	$arr["message"] = "您尚未拥有此配送地区，请您填写配送地址";
	$arr["province"] = $_COOKIE["province"];
	$arr["city"] = $_COOKIE["city"];
}

if (empty($arr["regions"])) {
	$arr["empty_type"] = 1;
}

$json = new JSON();
echo $json->encode($arr);

?>
