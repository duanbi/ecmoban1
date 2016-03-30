<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function get_pre_goods($cid, $min = 0, $max = 0, $start_time = 0, $end_time = 0, $sort, $status = 0, $order)
{
	$now = gmtime();
	$where = "";

	if (0 < $cid) {
		$where = "AND a.cid = '$cid' ";
	}

	if ($status == 1) {
		$where .= " AND a.start_time > $now ";
	}
	else if ($status == 2) {
		$where .= " AND a.start_time < $now AND $now < a.end_time ";
	}
	else if ($status == 3) {
		$where .= " AND $now > a.end_time ";
	}

	if ($sort == "shop_price") {
		$sort = "g.$sort";
	}
	else {
		$sort = "a.$sort";
	}

	$sql = "SELECT a.*, g.goods_thumb, g.goods_img, g.goods_name, g.shop_price, g.market_price, g.sales_volume FROM " . $GLOBALS["ecs"]->table("presale_activity") . " AS a  LEFT JOIN " . $GLOBALS["ecs"]->table("goods") . " AS g ON a.goods_id = g.goods_id  WHERE g.goods_id > 0 $where ORDER BY $sort $order";
	$res = $GLOBALS["db"]->getAll($sql);

	foreach ($res as $key => $row ) {
		$res[$key]["thumb"] = get_image_path($row["goods_id"], $row["goods_thumb"], true);
		$res[$key]["goods_img"] = get_image_path($row["goods_id"], $row["goods_img"]);
		$res[$key]["url"] = build_uri("presale", array("presaleid" => $row["act_id"]));
		$res[$key]["end_time_date"] = local_date("Y-m-d H:i:s", $row["end_time"]);
		$res[$key]["start_time_date"] = local_date("Y-m-d H:i:s", $row["start_time"]);

		if ($now <= $row["start_time"]) {
			$res[$key]["no_start"] = 1;
		}
	}

	return $res;
}

function get_pre_cat()
{
	$sql = "SELECT * FROM " . $GLOBALS["ecs"]->table("presale_cat") . " ORDER BY sort_order ASC ";
	$cat_res = $GLOBALS["db"]->getAll($sql);

	foreach ($cat_res as $key => $row ) {
		$cat_res[$key]["goods"] = get_cat_goods($row["cid"]);
		$cat_res[$key]["count_goods"] = count(get_cat_goods($row["cid"]));
	}

	return $cat_res;
}

function get_cat_goods($cat_id)
{
	$now = gmtime();
	$sql = "SELECT a.*, g.goods_thumb, g.goods_img, g.goods_name, g.shop_price, g.market_price, g.sales_volume FROM " . $GLOBALS["ecs"]->table("presale_activity") . " AS a  LEFT JOIN " . $GLOBALS["ecs"]->table("goods") . " AS g ON a.goods_id = g.goods_id WHERE a.cid = '$cat_id' ";
	$res = $GLOBALS["db"]->getAll($sql);

	foreach ($res as $key => $row ) {
		$res[$key]["thumb"] = get_image_path($row["goods_id"], $row["goods_thumb"], true);
		$res[$key]["goods_img"] = get_image_path($row["goods_id"], $row["goods_img"]);
		$res[$key]["url"] = build_uri("presale", array("gid" => $row["goods_id"]));
		$res[$key]["end_time_date"] = local_date("Y-m-d H:i:s", $row["end_time"]);
		$res[$key]["start_time_date"] = local_date("Y-m-d H:i:s", $row["start_time"]);

		if ($now <= $row["start_time"]) {
			$res[$key]["no_start"] = 1;
		}
	}

	return $res;
}

function get_pre_nav()
{
	$sql = "SELECT * FROM " . $GLOBALS["ecs"]->table("presale_cat") . " WHERE parent_cid = 0 ORDER BY sort_order ASC LIMIT 7 ";
	$res = $GLOBALS["db"]->getAll($sql);
	return $res;
}

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");

if ((DEBUG_MODE & 2) != 2) {
	$smarty->caching = true;
}

require (ROOT_PATH . "/includes/lib_area.php");
$pid = (isset($_REQUEST["pid"]) ? intval($_REQUEST["pid"]) : 0);
$user_id = (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0);
$smarty->assign("pre_nav_list", get_pre_nav());

if (empty($_REQUEST["act"])) {
	$_REQUEST["act"] = "index";
}

if (!isset($_COOKIE["province"])) {
	$area_array = get_ip_area_name();

	if ($area_array["county_level"] == 2) {
		$date = array("region_id", "parent_id", "region_name");
		$where = "region_name = '" . $area_array["area_name"] . "' AND region_type = 2";
		$city_info = get_table_date("region", $where, $date, 1);
		$date = array("region_id", "region_name");
		$where = "region_id = '" . $city_info[0]["parent_id"] . "'";
		$province_info = get_table_date("region", $where, $date);
		$where = "parent_id = '" . $city_info[0]["region_id"] . "' order by region_id asc limit 0, 1";
		$district_info = get_table_date("region", $where, $date, 1);
	}
	else if ($area_array["county_level"] == 1) {
		$area_name = $area_array["area_name"];
		$date = array("region_id", "region_name");
		$where = "region_name = '$area_name'";
		$province_info = get_table_date("region", $where, $date);
		$where = "parent_id = '" . $province_info["region_id"] . "' order by region_id asc limit 0, 1";
		$city_info = get_table_date("region", $where, $date, 1);
		$where = "parent_id = '" . $city_info[0]["region_id"] . "' order by region_id asc limit 0, 1";
		$district_info = get_table_date("region", $where, $date, 1);
	}
}

$order_area = get_user_order_area($user_id);
$user_area = get_user_area_reg($user_id);
if ($order_area["province"] && (0 < $user_id)) {
	$province_id = $order_area["province"];
}
else if (0 < $user_area["province"]) {
	$province_id = $user_area["province"];
	setcookie("province", $user_area["province"], gmtime() + (3600 * 24 * 30));
	$region_id = get_province_id_warehouse($province_id);
}
else {
	$sql = "select region_name from " . $ecs->table("region_warehouse") . " where regionId = '" . $province_info["region_id"] . "'";
	$warehouse_name = $db->getOne($sql);
	$province_id = $province_info["region_id"];
	$cangku_name = $warehouse_name;
	$region_id = get_warehouse_name_id(0, $cangku_name);
}

if ($order_area["province"] && (0 < $user_id)) {
	$city_id = $order_area["city"];
}
else if (0 < $user_area["city"]) {
	$city_id = $user_area["city"];
	setcookie("city", $user_area["city"], gmtime() + (3600 * 24 * 30));
}
else {
	$city_id = $city_info[0]["region_id"];
}

if ($order_area["province"] && (0 < $user_id)) {
	$district_id = $order_area["district"];
}
else if (0 < $user_area["district"]) {
	$district_id = $user_area["district"];
	setcookie("district", $user_area["district"], gmtime() + (3600 * 24 * 30));
}
else {
	$district_id = $district_info[0]["region_id"];
}

$province_id = (isset($_COOKIE["province"]) ? $_COOKIE["province"] : $province_id);
$child_num = get_region_child_num($province_id);

if (0 < $child_num) {
	$city_id = (isset($_COOKIE["city"]) ? $_COOKIE["city"] : $city_id);
}
else {
	$city_id = "";
}

$child_num = get_region_child_num($city_id);

if (0 < $child_num) {
	$district_id = (isset($_COOKIE["district"]) ? $_COOKIE["district"] : $district_id);
}
else {
	$district_id = "";
}

$region_id = (!isset($_COOKIE["region_id"]) ? $region_id : $_COOKIE["region_id"]);
$goods_warehouse = get_warehouse_goods_region($province_id);

if ($goods_warehouse) {
	$regionId = $goods_warehouse["region_id"];
	if ($_COOKIE["region_id"] && $_COOKIE["regionId"]) {
		$gw = 0;
	}
	else {
		$gw = 1;
	}
}

if ($gw) {
	$region_id = $regionId;
	setcookie("area_region", $region_id, gmtime() + (3600 * 24 * 30));
}

setcookie("goodsId", $goods_id, gmtime() + (3600 * 24 * 30));
$sellerInfo = get_seller_info_area();

if (empty($province_id)) {
	$province_id = $sellerInfo["province"];
	$city_id = $sellerInfo["city"];
	$district_id = 0;
	setcookie("province", $province_id, gmtime() + (3600 * 24 * 30));
	setcookie("city", $city_id, gmtime() + (3600 * 24 * 30));
	setcookie("district", $district_id, gmtime() + (3600 * 24 * 30));
	$region_id = get_warehouse_goods_region($province_id);
}

$area_info = get_area_info($province_id);
if (!empty($_REQUEST["act"]) && ($_REQUEST["act"] == "price")) {
	$goods_id = (isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : 0);
	include ("includes/cls_json.php");
	$json = new JSON();
	$res = array("err_msg" => "", "err_no" => 0, "result" => "", "qty" => 1);
	$attr_id = (isset($_REQUEST["attr"]) ? explode(",", $_REQUEST["attr"]) : array());
	$number = (isset($_REQUEST["number"]) ? intval($_REQUEST["number"]) : 1);
	$warehouse_id = (isset($_REQUEST["warehouse_id"]) ? intval($_REQUEST["warehouse_id"]) : 0);
	$area_id = (isset($_REQUEST["area_id"]) ? intval($_REQUEST["area_id"]) : 0);
	$onload = (isset($_REQUEST["onload"]) ? trim($_REQUEST["onload"]) : "");
	$goods = get_goods_info($goods_id, $warehouse_id, $area_id);

	if ($goods_id == 0) {
		$res["err_msg"] = $_LANG["err_change_attr"];
		$res["err_no"] = 1;
	}
	else {
		if ($number == 0) {
			$res["qty"] = $number = 1;
		}
		else {
			$res["qty"] = $number;
		}

		$products = get_warehouse_id_attr_number($goods_id, $_REQUEST["attr"], $goods["user_id"], $warehouse_id, $area_id);
		$attr_number = $products["product_number"];

		if ($goods["model_attr"] == 1) {
			$table_products = "products_warehouse";
			$type_files = " and warehouse_id = '$warehouse_id'";
		}
		else if ($goods["model_attr"] == 2) {
			$table_products = "products_area";
			$type_files = " and area_id = '$area_id'";
		}
		else {
			$table_products = "products";
			$type_files = "";
		}

		$sql = "SELECT * FROM " . $GLOBALS["ecs"]->table($table_products) . " WHERE goods_id = '$goods_id'" . $type_files . " LIMIT 0, 1";
		$prod = $GLOBALS["db"]->getRow($sql);

		if (empty($prod)) {
			$attr_number = $goods["goods_number"];
		}

		$attr_number = (!empty($attr_number) ? $attr_number : 0);
		$res["attr_number"] = $attr_number;
		$shop_price = get_final_price($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, 0, 1);
		$res["shop_price"] = price_format($shop_price);
		$res["market_price"] = $goods["market_price"];
		$spec_price = get_final_price($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, 1, 1);
		$res["marketPrice_amount"] = price_format($spec_price + $goods["marketPrice"]);
		$res["result"] = price_format($shop_price * $number);
	}

	$goods_fittings = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, "", 1);
	$fittings_list = get_goods_fittings(array($goods_id), $warehouse_id, $area_id);

	if ($fittings_list) {
		if (is_array($fittings_list)) {
			foreach ($fittings_list as $vo ) {
				$fittings_index[$vo["group_id"]] = $vo["group_id"];
			}
		}

		ksort($fittings_index);
		$merge_fittings = get_merge_fittings_array($fittings_index, $fittings_list);
		$fitts = get_fittings_array_list($merge_fittings, $goods_fittings);

		for ($i = 0; $i < count($fitts); $i++) {
			$fittings_interval = $fitts[$i]["fittings_interval"];
			$res["fittings_interval"][$i]["fittings_minMax"] = price_format($fittings_interval["fittings_min"]) . "-" . number_format($fittings_interval["fittings_max"], 2, ".", "");
			$res["fittings_interval"][$i]["market_minMax"] = price_format($fittings_interval["market_min"]) . "-" . number_format($fittings_interval["market_max"], 2, ".", "");

			if ($fittings_interval["save_minPrice"] == $fittings_interval["save_maxPrice"]) {
				$res["fittings_interval"][$i]["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]);
			}
			else {
				$res["fittings_interval"][$i]["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]) . "-" . number_format($fittings_interval["save_maxPrice"], 2, ".", "");
			}

			$res["fittings_interval"][$i]["groupId"] = $fittings_interval["groupId"];
		}
	}

	if ($GLOBALS["_CFG"]["open_area_goods"] == 1) {
		$area_list = get_goods_link_area_list($goods_id, $goods["user_id"]);

		if ($area_list["goods_area"]) {
			if (!in_array($area_id, $area_list["goods_area"])) {
				$res["err_no"] = 2;
			}
		}
		else {
			$res["err_no"] = 2;
		}
	}

	exit($json->encode($res));
}
else if ($_REQUEST["act"] == "in_stock") {
	include ("includes/cls_json.php");
	$json = new JSON();
	$res = array("err_msg" => "", "result" => "", "qty" => 1);
	clear_cache_files();
	$act_id = (empty($_GET["act_id"]) ? 0 : intval($_GET["act_id"]));
	$goods_id = (empty($_GET["id"]) ? 0 : intval($_GET["id"]));
	$province = (empty($_GET["province"]) ? 1 : intval($_GET["province"]));
	$city = (empty($_GET["city"]) ? 52 : intval($_GET["city"]));
	$district = (empty($_GET["district"]) ? 500 : intval($_GET["district"]));
	$d_null = (empty($_GET["d_null"]) ? 0 : intval($_GET["d_null"]));
	$user_id = (empty($_GET["user_id"]) ? 0 : $_GET["user_id"]);
	$user_address = get_user_address_region($user_id);
	$user_address = explode(",", $user_address["region_address"]);
	setcookie("province", $province, gmtime() + (3600 * 24 * 30));
	setcookie("city", $city, gmtime() + (3600 * 24 * 30));
	setcookie("district", $district, gmtime() + (3600 * 24 * 30));
	$regionId = 0;
	setcookie("regionId", $regionId, gmtime() + (3600 * 24 * 30));
	setcookie("type_province", 0, gmtime() + (3600 * 24 * 30));
	setcookie("type_city", 0, gmtime() + (3600 * 24 * 30));
	setcookie("type_district", 0, gmtime() + (3600 * 24 * 30));
	$res["d_null"] = $d_null;

	if ($d_null == 0) {
		if (in_array($district, $user_address)) {
			$res["isRegion"] = 1;
		}
		else {
			$res["message"] = "您尚未拥有此配送地区，请您填写配送地址";
			$res["isRegion"] = 88;
		}
	}
	else {
		setcookie("district", "", gmtime() + (3600 * 24 * 30));
	}

	$res["goods_id"] = $goods_id;
	$res["act_id"] = $act_id;
	exit($json->encode($res));
}

if ($_REQUEST["act"] == "index") {
	$pre_goods = get_pre_cat();
	$smarty->assign("pre_cat_goods", $pre_goods);
	assign_template();
	$smarty->assign("helps", get_shop_help());
	$position = assign_ur_here();
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);

	for ($i = 1; $i <= $_CFG["auction_ad"]; $i++) {
		$presale_banner .= "'presale_banner" . $i . ",";
		$presale_banner_small .= "'presale_banner_small" . $i . ",";
		$presale_banner_small_left .= "'presale_banner_small_left" . $i . ",";
		$presale_banner_small_right .= "'presale_banner_small_right" . $i . ",";
	}

	$smarty->assign("pager", array("act" => "index"));
	$smarty->assign("presale_banner", $presale_banner);
	$smarty->assign("presale_banner_small", $presale_banner_small);
	$smarty->assign("presale_banner_small_left", $presale_banner_small_left);
	$smarty->assign("presale_banner_small_right", $presale_banner_small_right);
	$smarty->display("presale_index.dwt");
}
else if ($_REQUEST["act"] == "area") {
	$smarty->display("presale_area.dwt", $cache_id);
}
else if ($_REQUEST["act"] == "new") {
	$where = "";
	$cid = (isset($_REQUEST["cid"]) && (0 < intval($_REQUEST["cid"])) ? intval($_REQUEST["cid"]) : 0);
	$status = (isset($_REQUEST["status"]) && (0 < intval($_REQUEST["status"])) ? intval($_REQUEST["status"]) : 0);

	if (0 < $cid) {
		$where .= " AND a.cid = '$cid' ";
	}

	$now = gmtime();

	if ($status == 1) {
		$where .= " AND a.start_time > $now ";
	}
	else if ($status == 2) {
		$where .= " AND a.start_time < $now AND $now < a.end_time ";
	}
	else if ($status == 3) {
		$where .= " AND $now > a.end_time ";
	}

	$pager = array("cid" => $cid, "act" => "new", "status" => $status);
	$smarty->assign("pager", $pager);
	$pre_category = $GLOBALS["db"]->getAll("SELECT * FROM " . $GLOBALS["ecs"]->table("presale_cat") . " ORDER BY sort_order ASC ");
	$smarty->assign("pre_category", $pre_category);
	$sql = "SELECT a.*, g.goods_thumb, g.goods_img, g.goods_name, g.shop_price, g.market_price, g.sales_volume FROM " . $GLOBALS["ecs"]->table("presale_activity") . " AS a LEFT JOIN " . $GLOBALS["ecs"]->table("goods") . " AS g ON a.goods_id = g.goods_id  WHERE g.goods_id > 0 $where ORDER BY a.end_time DESC,a.start_time DESC ";
	$res = $GLOBALS["db"]->getAll($sql);

	foreach ($res as $key => $row ) {
		$res[$key]["thumb"] = get_image_path($row["goods_id"], $row["goods_thumb"], true);
		$res[$key]["goods_img"] = get_image_path($row["goods_id"], $row["goods_img"]);
		$res[$key]["url"] = build_uri("presale", array("presaleid" => $row["act_id"]));
		$res[$key]["end_time_date"] = local_date("Y-m-d H:i:s", $row["end_time"]);
		$res[$key]["end_time_day"] = local_date("Y-m-d", $row["end_time"]);
		$res[$key]["start_time_date"] = local_date("Y-m-d H:i:s", $row["start_time"]);
		$res[$key]["start_time_day"] = local_date("Y-m-d", $row["start_time"]);

		if ($now <= $row["start_time"]) {
			$res[$key]["no_start"] = 1;
		}
	}

	$date_array = array();

	foreach ($res as $key => $row ) {
		$date_array[$row["end_time_day"]][] = $row;
	}

	$date_result = array();

	foreach ($date_array as $key => $value ) {
		$date_result[]["goods"] = $value;
	}

	foreach ($date_result as $key => $value ) {
		$date_result[$key]["end_time_day"] = $value["goods"][0]["end_time_day"];
		$date_result[$key]["end_time_y"] = local_date("Y", gmstr2time($value["goods"][0]["end_time_day"]));
		$date_result[$key]["end_time_m"] = local_date("m", gmstr2time($value["goods"][0]["end_time_day"]));
		$date_result[$key]["end_time_d"] = local_date("d", gmstr2time($value["goods"][0]["end_time_day"]));
		$date_result[$key]["count_goods"] = count($value["goods"]);
	}

	$smarty->assign("date_result", $date_result);
	assign_template();
	$smarty->assign("helps", get_shop_help());
	$position = assign_ur_here();
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);

	for ($i = 1; $i <= $_CFG["auction_ad"]; $i++) {
		$presale_banner_new .= "'presale_banner_new" . $i . ",";
	}

	$smarty->assign("presale_banner_new", $presale_banner_new);
	$smarty->display("presale_new.dwt");
}
else if ($_REQUEST["act"] == "advance") {
	$price_min = (isset($_REQUEST["price_min"]) && (0 < intval($_REQUEST["price_min"])) ? intval($_REQUEST["price_min"]) : 0);
	$price_max = (isset($_REQUEST["price_max"]) && (0 < intval($_REQUEST["price_max"])) ? intval($_REQUEST["price_max"]) : 0);
	$default_sort_order_method = ($_CFG["sort_order_method"] == "0" ? "DESC" : "ASC");
	$default_sort_order_type = ($_CFG["sort_order_type"] == "0" ? "act_id" : ($_CFG["sort_order_type"] == "1" ? "shop_price" : "start_time"));
	$sort = (isset($_REQUEST["sort"]) && in_array(trim(strtolower($_REQUEST["sort"])), array("shop_price", "start_time", "act_id")) ? trim($_REQUEST["sort"]) : $default_sort_order_type);
	$order = (isset($_REQUEST["order"]) && in_array(trim(strtoupper($_REQUEST["order"])), array("ASC", "DESC")) ? trim($_REQUEST["order"]) : $default_sort_order_method);
	$cid = (isset($_REQUEST["cid"]) && (0 < intval($_REQUEST["cid"])) ? intval($_REQUEST["cid"]) : 0);
	$status = (isset($_REQUEST["status"]) && (0 < intval($_REQUEST["status"])) ? intval($_REQUEST["status"]) : 0);
	$goods = get_pre_goods($cid, $min = 0, $max = 0, $start_time, $end_time, $sort, $status, $order);
	$pre_category = $GLOBALS["db"]->getAll("SELECT * FROM " . $GLOBALS["ecs"]->table("presale_cat") . " ORDER BY sort_order ASC ");
	$smarty->assign("pre_category", $pre_category);
	$pager = array("cid" => $cid, "act" => "advance", "price_min" => $price_min, "price_max" => $price_max, "sort" => $sort, "order" => $order, "status" => $status);
	$smarty->assign("pager", $pager);
	$smarty->assign("goods", $goods);
	assign_template();
	$smarty->assign("helps", get_shop_help());
	$position = assign_ur_here();
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);

	for ($i = 1; $i <= $_CFG["auction_ad"]; $i++) {
		$presale_banner_advance .= "'presale_banner_advance" . $i . ",";
	}

	$smarty->assign("presale_banner_advance", $presale_banner_advance);
	$smarty->display("presale_advance.dwt", $cache_id);
}
else if ($_REQUEST["act"] == "category") {
	$price_min = (isset($_REQUEST["price_min"]) && (0 < intval($_REQUEST["price_min"])) ? intval($_REQUEST["price_min"]) : 0);
	$price_max = (isset($_REQUEST["price_max"]) && (0 < intval($_REQUEST["price_max"])) ? intval($_REQUEST["price_max"]) : 0);
	$default_sort_order_method = ($_CFG["sort_order_method"] == "0" ? "DESC" : "ASC");
	$default_sort_order_type = ($_CFG["sort_order_type"] == "0" ? "act_id" : ($_CFG["sort_order_type"] == "1" ? "shop_price" : "start_time"));
	$sort = (isset($_REQUEST["sort"]) && in_array(trim(strtolower($_REQUEST["sort"])), array("shop_price", "start_time", "act_id")) ? trim($_REQUEST["sort"]) : $default_sort_order_type);
	$order = (isset($_REQUEST["order"]) && in_array(trim(strtoupper($_REQUEST["order"])), array("ASC", "DESC")) ? trim($_REQUEST["order"]) : $default_sort_order_method);
	$cid = (isset($_REQUEST["cid"]) && (0 < intval($_REQUEST["cid"])) ? intval($_REQUEST["cid"]) : 0);
	$status = (isset($_REQUEST["status"]) && (0 < intval($_REQUEST["status"])) ? intval($_REQUEST["status"]) : 0);
	$goods = get_pre_goods($cid, $min = 0, $max = 0, $start_time, $end_time, $sort, $status, $order);
	$pre_category = $GLOBALS["db"]->getAll("SELECT * FROM " . $GLOBALS["ecs"]->table("presale_cat") . " ORDER BY sort_order ASC ");
	$smarty->assign("pre_category", $pre_category);
	$pager = array("cid" => $cid, "act" => "category", "price_min" => $price_min, "price_max" => $price_max, "sort" => $sort, "order" => $order, "status" => $status);
	$smarty->assign("pager", $pager);
	$smarty->assign("goods", $goods);
	assign_template();
	$smarty->assign("helps", get_shop_help());
	$position = assign_ur_here();
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);

	for ($i = 1; $i <= $_CFG["auction_ad"]; $i++) {
		$presale_banner_category .= "'presale_banner_category" . $i . ",";
	}

	$smarty->assign("presale_banner_category", $presale_banner_category);
	$smarty->display("presale_category.dwt", $cache_id);
}
else {
	if (!empty($_REQUEST["act"]) && ($_REQUEST["act"] == "guess_goods")) {
		include ("includes/cls_json.php");
		$json = new JSON();
		$res = array("err_msg" => "", "result" => "");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);

		if (3 < $page) {
			$page = 1;
		}

		$need_cache = $GLOBALS["smarty"]->caching;
		$need_compile = $GLOBALS["smarty"]->force_compile;
		$GLOBALS["smarty"]->caching = false;
		$GLOBALS["smarty"]->force_compile = true;
		$guess_goods = get_guess_goods($user_id, 1, $page, 7);
		$smarty->assign("guess_goods", $guess_goods);
		$smarty->assign("pager", $pager);
		$res["page"] = $page;
		$res["result"] = $GLOBALS["smarty"]->fetch("library/guess_goods_love.lbi");
		$GLOBALS["smarty"]->caching = $need_cache;
		$GLOBALS["smarty"]->force_compile = $need_compile;
		exit($json->encode($res));
	}
	else if ($_REQUEST["act"] == "view") {
		$categories_pro = get_category_tree_leve_one();
		$smarty->assign("categories_pro", $categories_pro);
		$presale_id = (isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : 0);

		if ($presale_id <= 0) {
			ecs_header("Location: ./\n");
			exit();
		}

		$presale = presale_info($presale_id);

		if (empty($presale)) {
			ecs_header("Location: ./\n");
			exit();
		}

		$cache_id = $_CFG["lang"] . "-presale-" . $presale_id . "-" . $presale["status"] . time();

		if ($presale["status"] == GBS_UNDER_WAY) {
			$cache_id = $cache_id . "-" . $presale["valid_goods"] . "-" . intval(0 < $_SESSION["user_id"]);
		}

		$cache_id = sprintf("%X", crc32($cache_id));

		if (!$smarty->is_cached("presale_goods.dwt", $cache_id)) {
			$now = gmtime();
			$presale["gmt_end_date"] = local_strtotime($presale["end_time"]);
			$presale["gmt_start_date"] = local_strtotime($presale["start_time"]);

			if ($now <= $presale["gmt_start_date"]) {
				$presale["no_start"] = 1;
			}

			$smarty->assign("presale", $presale);
			$goods_id = $presale["goods_id"];
			$goods = get_goods_info($goods_id, $region_id, $area_id);

			if (empty($goods)) {
				ecs_header("Location: ./\n");
				exit();
			}

			$smarty->assign("goods", $goods);
			$smarty->assign("id", $goods_id);
			$smarty->assign("type", 0);
			$shop_info = get_merchants_shop_info("merchants_steps_fields", $goods["user_id"]);
			$adress = get_license_comp_adress($shop_info["license_comp_adress"]);
			$smarty->assign("shop_info", $shop_info);
			$smarty->assign("adress", $adress);
			$province_list = get_warehouse_province();
			$smarty->assign("province_list", $province_list);
			$city_list = get_region_city_county($province_id);
			$smarty->assign("city_list", $city_list);
			$district_list = get_region_city_county($city_id);
			$smarty->assign("district_list", $district_list);
			$smarty->assign("goods_id", $goods_id);
			$warehouse_list = get_warehouse_list_goods();
			$smarty->assign("warehouse_list", $warehouse_list);
			$warehouse_name = get_warehouse_name_id($region_id);
			$smarty->assign("warehouse_name", $warehouse_name);
			$smarty->assign("region_id", $region_id);
			$smarty->assign("user_id", $_SESSION["user_id"]);
			$smarty->assign("shop_price_type", $goods["model_price"]);
			$smarty->assign("area_id", $area_info["region_id"]);
			$properties = get_goods_properties($goods_id);
			$smarty->assign("properties", $properties["pro"]);
			$smarty->assign("specification", $properties["spe"]);
			$smarty->assign("area_htmlType", "presale");
			$smarty->assign("province_row", get_region_name($province_id));
			$smarty->assign("city_row", get_region_name($city_id));
			$smarty->assign("district_row", get_region_name($district_id));
			$smarty->assign("cfg", $_CFG);
			assign_template();
			$position = assign_ur_here(0, $presale["goods_name"]);
			$smarty->assign("page_title", $position["title"]);
			$smarty->assign("ur_here", $position["ur_here"]);
			$smarty->assign("categories", get_categories_tree());
			$smarty->assign("helps", get_shop_help());
			$smarty->assign("top_goods", get_top10("", "presale"));
			$smarty->assign("guess_goods", get_guess_goods($user_id, 1, $page = 1, 7));
			$smarty->assign("best_goods", get_recommend_goods("best", "", $region_id, $area_info["region_id"], $goods["user_id"], 1, "presale"));
			$smarty->assign("new_goods", get_recommend_goods("new", "", $region_id, $area_info["region_id"], $goods["user_id"], 1, "presale"));
			$smarty->assign("hot_goods", get_recommend_goods("hot", "", $region_id, $area_info["region_id"], $goods["user_id"], 1, "presale"));
			$smarty->assign("pictures", get_goods_gallery($goods_id));
			$smarty->assign("promotion_info", get_promotion_info());
		}

		$comment_all = get_comments_percent($goods_id);

		if (0 < $goods["user_id"]) {
			$merchants_goods_comment = get_merchants_goods_comment($goods["user_id"]);
		}

		$smarty->assign("comment_all", $comment_all);
		$cat_info = cat_list(0, 0, false, 0, true, "", 0, $goods["user_id"]);
		$goods_store_cat = goods_admin_store_cat_list($cat_info);
		$smarty->assign("goods_store_cat", $goods_store_cat);
		$discuss_list = get_discuss_all_list($goods_id, 0, 1, 10);
		$smarty->assign("discuss_list", $discuss_list);
		$sql = "UPDATE " . $ecs->table("goods") . " SET click_count = click_count + 1 WHERE goods_id = '" . $group_buy["goods_id"] . "'";
		$db->query($sql);
		$smarty->assign("act_id", $presale_id);
		$smarty->assign("now_time", gmtime());
		$smarty->assign("area_htmlType", "presale");
		$sql = "select province, city, kf_type, kf_ww, kf_qq, shop_name from " . $ecs->table("seller_shopinfo") . " where ru_id='" . $goods["user_id"] . "'";
		$basic_info = $db->getRow($sql);
		$basic_date = array("region_name");
		$basic_info["province"] = get_table_date("region", "region_id = '" . $basic_info["province"] . "'", $basic_date, 2);
		$basic_info["city"] = get_table_date("region", "region_id= '" . $basic_info["city"] . "'", $basic_date, 2) . "市";
		$smarty->assign("basic_info", $basic_info);
		$area = array("region_id" => $region_id, "province_id" => $province_id, "city_id" => $city_id, "district_id" => $district_id, "goods_id" => $goods_id, "user_id" => $user_id, "area_id" => $area_info["region_id"], "merchant_id" => $goods["user_id"]);
		$smarty->assign("area", $area);
		$smarty->display("presale_goods.dwt", $cache_id);
	}
	else if ($_REQUEST["act"] == "buy") {
		if ($_SESSION["user_id"] <= 0) {
			show_message($_LANG["gb_error_login"], "", "", "error");
		}

		$warehouse_id = (isset($_REQUEST["warehouse_id"]) ? intval($_REQUEST["warehouse_id"]) : 0);
		$area_id = (isset($_REQUEST["area_id"]) ? intval($_REQUEST["area_id"]) : 0);
		$presale_id = (isset($_POST["presale_id"]) ? intval($_POST["presale_id"]) : 0);

		if ($presale_id <= 0) {
			ecs_header("Location: ./\n");
			exit();
		}

		$number = (isset($_POST["number"]) ? intval($_POST["number"]) : 1);
		$number = ($number < 1 ? 1 : $number);
		$presale = presale_info($presale_id, $number);

		if (empty($presale)) {
			ecs_header("Location: ./\n");
			exit();
		}

		if ($presale["status"] != GBS_UNDER_WAY) {
			show_message($_LANG["presale_error_status"], "", "", "error");
		}

		$goods = goods_info($presale["goods_id"], $warehouse_id, $area_id);

		if (empty($goods)) {
			ecs_header("Location: ./\n");
			exit();
		}

		if ((0 < $goods["goods_number"]) && (($goods["goods_number"] - $presale["valid_goods"]) < $number)) {
			show_message($_LANG["gb_error_goods_lacking"], "", "", "error");
		}

		$specs = (isset($_POST["goods_spec"]) ? htmlspecialchars(trim($_POST["goods_spec"])) : "");

		if ($specs) {
			$_specs = explode(",", $specs);
			$product_info = get_products_info($goods["goods_id"], $_specs, $warehouse_id, $area_id);
		}

		empty($product_info) ? $product_info = array("product_number" => 0, "product_id" => 0) : "";

		if ($goods["model_attr"] == 1) {
			$table_products = "products_warehouse";
			$type_files = " and warehouse_id = '$warehouse_id'";
		}
		else if ($goods["model_attr"] == 2) {
			$table_products = "products_area";
			$type_files = " and area_id = '$area_id'";
		}
		else {
			$table_products = "products";
			$type_files = "";
		}

		$sql = "SELECT * FROM " . $GLOBALS["ecs"]->table($table_products) . " WHERE goods_id = '" . $goods["goods_id"] . "'" . $type_files . " LIMIT 0, 1";
		$prod = $GLOBALS["db"]->getRow($sql);

		if ($GLOBALS["_CFG"]["use_storage"] == 1) {
			if ($prod && ($product_info["product_number"] < $number)) {
				show_message($_LANG["gb_error_goods_lacking"], "", "", "error");
			}
			else if ($goods["goods_number"] < $number) {
				show_message($_LANG["gb_error_goods_lacking"], "", "", "error");
			}
		}

		$attr_list = array();
		$sql = "SELECT a.attr_name, g.attr_value FROM " . $ecs->table("goods_attr") . " AS g, " . $ecs->table("attribute") . " AS a WHERE g.attr_id = a.attr_id AND g.goods_attr_id " . db_create_in($specs);
		$res = $db->query($sql);

		while ($row = $db->fetchRow($res)) {
			$attr_list[] = $row["attr_name"] . ": " . $row["attr_value"];
		}

		$goods_attr = join(chr(13) . chr(10), $attr_list);
		include_once (ROOT_PATH . "includes/lib_order.php");
		clear_cart(CART_PRESALE_GOODS);
		$area_id = $area_info["region_id"];
		$where = "regionId = '$province_id'";
		$date = array("parent_id");
		$region_id = get_table_date("region_warehouse", $where, $date, 2);

		if (!empty($_SESSION["user_id"])) {
			$sess = "";
		}
		else {
			$sess = real_cart_mac_ip();
		}

		$cart = array("user_id" => $_SESSION["user_id"], "session_id" => $sess, "goods_id" => $presale["goods_id"], "product_id" => $product_info["product_id"], "goods_sn" => addslashes($goods["goods_sn"]), "goods_name" => addslashes($goods["goods_name"]), "market_price" => $goods["market_price"], "goods_price" => $goods["shop_price"], "goods_number" => $number, "goods_attr" => addslashes($goods_attr), "goods_attr_id" => $specs, "ru_id" => $goods["user_id"], "warehouse_id" => $region_id, "area_id" => $area_id, "is_real" => $goods["is_real"], "extension_code" => "presale", "parent_id" => 0, "rec_type" => CART_PRESALE_GOODS, "is_gift" => 0);
		$db->autoExecute($ecs->table("cart"), $cart, "INSERT");
		$_SESSION["flow_type"] = CART_PRESALE_GOODS;
		$_SESSION["extension_code"] = "presale";
		$_SESSION["extension_id"] = $presale["act_id"];
		$_SESSION["browse_trace"] = "presale";
		ecs_header("Location: ./flow.php?step=checkout\n");
		exit();
	}
}

?>
