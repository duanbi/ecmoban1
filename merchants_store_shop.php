<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");

if ((DEBUG_MODE & 2) != 2) {
	$smarty->caching = true;
}

require (ROOT_PATH . "/includes/lib_area.php");
$area_info = get_area_info($province_id);
$area_id = $area_info["region_id"];
$where = "regionId = '$province_id'";
$date = array("parent_id");
$region_id = get_table_date("region_warehouse", $where, $date, 2);
$brand = (isset($_REQUEST["brand"]) && (0 < intval($_REQUEST["brand"])) ? intval($_REQUEST["brand"]) : 0);
$price_max = (isset($_REQUEST["price_max"]) && (0 < intval($_REQUEST["price_max"])) ? intval($_REQUEST["price_max"]) : 0);
$price_min = (isset($_REQUEST["price_min"]) && (0 < intval($_REQUEST["price_min"])) ? intval($_REQUEST["price_min"]) : 0);
$filter_attr_str = (isset($_REQUEST["filter_attr"]) ? htmlspecialchars(trim($_REQUEST["filter_attr"])) : "0");
$filter_attr_str = trim(urldecode($filter_attr_str));
$filter_attr_str = (preg_match("/^[\d\.]+$/", $filter_attr_str) ? $filter_attr_str : "");
$filter_attr = (empty($filter_attr_str) ? "" : explode(".", $filter_attr_str));
$default_sort_order_method = ($_CFG["sort_order_method"] == "0" ? "DESC" : "ASC");
$default_sort_order_type = ($_CFG["sort_order_type"] == "0" ? "goods_id" : ($_CFG["sort_order_type"] == "1" ? "shop_price" : "last_update"));
$sort = (isset($_REQUEST["sort"]) && in_array(trim(strtolower($_REQUEST["sort"])), array("goods_id", "shop_price", "last_update")) ? trim($_REQUEST["sort"]) : $default_sort_order_type);
$order = (isset($_REQUEST["order"]) && in_array(trim(strtoupper($_REQUEST["order"])), array("ASC", "DESC")) ? trim($_REQUEST["order"]) : $default_sort_order_method);
$page = (!empty($_REQUEST["page"]) && (0 < intval($_REQUEST["page"])) ? intval($_REQUEST["page"]) : 1);
$size = (!empty($_CFG["page_size"]) && (0 < intval($_CFG["page_size"])) ? intval($_CFG["page_size"]) : 10);
$merchant_id = (isset($_REQUEST["merchant_id"]) ? intval($_REQUEST["merchant_id"]) : 0);
$user_id = (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0);
$shop_date = array("shop_id");
$shop_where = "user_id = '$merchant_id'";
$shop_id = get_table_date("merchants_shop_information", $shop_where, $shop_date);
if (($merchant_id == 0) || ($shop_id < 1)) {
	header("Location: index.php\n");
	exit();
}

$cache_id = sprintf("%X", crc32($_SESSION["user_rank"] . "-" . $_CFG["lang"]));

if (!$smarty->is_cached("merchants_shop.dwt")) {
	assign_template();
	$position = assign_ur_here();
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);
	$categories_pro = get_category_tree_leve_one();
	$smarty->assign("categories_pro", $categories_pro);
	$goods_list = get_shop_goods_cmt_list($merchant_id, $region_id, $area_id, $price_min, $price_max, $page, $size, $sort, $order);
	$smarty->assign("goods_list", $goods_list);
	$count = get_shop_goods_cmt_count($merchant_id, $price_min, $price_max, $sort);
	$smarty->assign("count", $count);
	assign_pager("merchants_store_shop", 0, $count, $size, $sort, $order, $page, "", $brand, $price_min, $price_max, "list", $filter_attr_str, "", "", $merchant_id);
	assign_dynamic("merchants_store_shop");
}

if (0 < $merchant_id) {
	$merchants_goods_comment = get_merchants_goods_comment($merchant_id);
}

$smarty->assign("merch_cmt", $merchants_goods_comment);
$shop_info = get_shop_name($merchant_id, 3);
$smarty->assign("shop_info", $shop_info);
$brand_list = get_shop_brand_list($merchant_id);
$smarty->assign("helps", get_shop_help());
$address = get_shop_address_info($merchant_id);
$smarty->assign("brand_list", $brand_list);
$smarty->assign("address", $address);
$sql = "select * from " . $ecs->table("seller_shopinfo") . " where ru_id='$merchant_id'";
$basic_info = $db->getRow($sql);
$basic_info["logo_thumb"] = str_replace("../", "", $basic_info["logo_thumb"]);

if ($GLOBALS["_CFG"]["open_oss"] == 1) {
	$bucket_info = get_bucket_info();
	$basic_info["logo_thumb"] = $bucket_info["endpoint"] . $basic_info["logo_thumb"];
}

$smarty->assign("basic_info", $basic_info);
$smarty->assign("merchant_id", $merchant_id);
$smarty->assign("script_name", "merchants_store_shop");
$smarty->display("merchants_shop.dwt");

?>
