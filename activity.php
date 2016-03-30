<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function get_range_gift_list($act_id, $type)
{
	$sql = "SELECT act_range_ext, gift FROM " . $GLOBALS["ecs"]->table("favourable_activity") . " WHERE act_id = '$act_id'";
	$row = $GLOBALS["db"]->getRow($sql);
	$arr = array();

	if ($type == 1) {
		$row["gift"] = unserialize($row["gift"]);

		if (is_array($row["gift"])) {
			foreach ($row["gift"] as $k => $v ) {
				$goods = $GLOBALS["db"]->getRow("SELECT goods_thumb, market_price FROM " . $GLOBALS["ecs"]->table("goods") . " WHERE goods_id = '" . $v["id"] . "'");
				$arr[$k]["thumb"] = get_image_path($v["id"], $goods["goods_thumb"], true);
				$arr[$k]["url"] = build_uri("goods", array("gid" => $v["id"]), $v["name"]);
				$arr[$k]["market_price"] = price_format($goods["market_price"]);
				$arr[$k]["price"] = price_format($v["price"]);
				$arr[$k]["name"] = $v["name"];
			}
		}
	}
	else if ($type == 0) {
		$sql = "SELECT goods_id AS id, goods_name AS name, goods_thumb FROM " . $GLOBALS["ecs"]->table("goods") . " WHERE goods_id " . db_create_in($row["act_range_ext"]);
		$res = $GLOBALS["db"]->getAll($sql);

		foreach ($res as $k => $v ) {
			$arr[$k] = $v;
			$arr[$k]["thumb"] = get_image_path($v["id"], $GLOBALS["db"]->getOne("SELECT goods_thumb FROM " . $GLOBALS["ecs"]->table("goods") . " WHERE goods_id = '" . $v["id"] . "'"), true);
			$arr[$k]["url"] = build_uri("goods", array("gid" => $v["id"]), $v["name"]);
		}
	}

	return $arr;
}

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");
require_once (ROOT_PATH . "includes/lib_order.php");
include_once (ROOT_PATH . "includes/lib_transaction.php");
require (ROOT_PATH . "/includes/lib_area.php");
require_once (ROOT_PATH . "languages/" . $_CFG["lang"] . "/shopping_flow.php");
require_once (ROOT_PATH . "languages/" . $_CFG["lang"] . "/user.php");
include ("includes/cls_json.php");
$json = new JSON();
$result = array("err_msg" => "", "err_no" => 0, "content" => "");

if (isset($_REQUEST["act"])) {
	if ($_REQUEST["act"] == "rang_gift_list") {
		$act_id = (!empty($_REQUEST["act_id"]) ? intval($_REQUEST["act_id"]) : 0);
		$type = (isset($_REQUEST["type"]) ? intval($_REQUEST["type"]) : 0);
		$idname = (isset($_REQUEST["idname"]) ? htmlspecialchars(trim($_REQUEST["idname"])) : 0);
		$range_gift_list = get_range_gift_list($act_id, $type);
		$smarty->assign("type", $type);
		$smarty->assign("range_gift_list", $range_gift_list);
		$result["content"] = $smarty->fetch("library/range_gift_list.lbi");
		$result["act_id"] = $act_id;
		$result["idname"] = $idname;
		$result["type"] = $type;

		if ($type == 1) {
			$result["name"] = "range";
		}
		else {
			$result["name"] = "gift";
		}

		exit($json->encode($result));
	}
}

assign_template();
assign_dynamic("activity");
$position = assign_ur_here();
$smarty->assign("page_title", $position["title"]);
$smarty->assign("ur_here", $position["ur_here"]);
$categories_pro = get_category_tree_leve_one();
$smarty->assign("categories_pro", $categories_pro);
$user_rank_list = array();
$user_rank_list[0] = $_LANG["not_user"];
$sql = "SELECT rank_id, rank_name FROM " . $ecs->table("user_rank");
$res = $db->query($sql);

while ($row = $db->fetchRow($res)) {
	$user_rank_list[$row["rank_id"]] = $row["rank_name"];
}

$sql = "SELECT * FROM " . $ecs->table("favourable_activity") . " ORDER BY `sort_order` ASC,`end_time` DESC";
$res = $db->query($sql);
$list = array();

while ($row = $db->fetchRow($res)) {
	$row["start_time"] = local_date("Y-m-d H:i", $row["start_time"]);
	$row["end_time"] = local_date("Y-m-d H:i", $row["end_time"]);
	if (($GLOBALS["_CFG"]["open_oss"] == 1) && $row["activity_thumb"]) {
		$bucket_info = get_bucket_info();
		$row["activity_thumb"] = $bucket_info["endpoint"] . $row["activity_thumb"];
	}

	$user_rank = explode(",", $row["user_rank"]);
	$row["user_rank"] = array();

	foreach ($user_rank as $val ) {
		if (isset($user_rank_list[$val])) {
			$row["user_rank"][] = $user_rank_list[$val];
		}
	}

	if ($row["userFav_type"]) {
		$row["shop_name"] = "全场通用";
	}
	else {
		$row["shop_name"] = get_shop_name($row["user_id"], 1);
		$row["shop_url"] = build_uri("merchants_store", array("urid" => $row["user_id"]), $row["shop_name"]);
	}

	$row["act_range_type"] = $row["act_range"];
	if (($row["act_range"] != FAR_ALL) && !empty($row["act_range_ext"])) {
		if ($row["act_range"] == FAR_CATEGORY) {
			$row["act_range"] = $_LANG["far_category"];
			$row["program"] = "category.php?id=";
			$sql = "SELECT cat_id AS id, cat_name AS name FROM " . $ecs->table("category") . " WHERE cat_id " . db_create_in($row["act_range_ext"]);
		}
		else if ($row["act_range"] == FAR_BRAND) {
			$lj_brand = "";
			$lj_where = "";

			if (0 < $row["user_id"]) {
				$lj_brand = ", " . $ecs->table("merchants_shop_brand") . " AS msb, " . $ecs->table("link_brand") . " AS lb ";
				$lj_where = "b.brand_id = lb.brand_id AND msb.bid = lb.bid AND msb.user_id = '" . $row["user_id"] . "' AND msb.bid " . db_create_in($row["act_range_ext"]);
			}
			else {
				$lj_where = "b.brand_id " . db_create_in($row["act_range_ext"]);
			}

			$row["act_range"] = $_LANG["far_brand"];
			$row["program"] = "brand.php?id=";
			$sql = "SELECT b.brand_id AS id, b.brand_name AS name FROM " . $ecs->table("brand") . " AS b " . $lj_brand . " WHERE " . $lj_where;
		}
		else {
			$row["act_range"] = $_LANG["far_goods"];
			$row["program"] = "goods.php?id=";
			$sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table("goods") . " WHERE goods_id " . db_create_in($row["act_range_ext"]);
		}

		$act_range_ext = $db->getAll($sql);
		$row["act_range_ext"] = $act_range_ext;
	}
	else {
		$row["act_range"] = $_LANG["far_all"];
	}

	$row["actType"] = $row["act_type"];

	switch ($row["act_type"]) {
	case 0:
		$row["act_type"] = $_LANG["fat_goods"];
		$row["gift"] = unserialize($row["gift"]);

		if (is_array($row["gift"])) {
			foreach ($row["gift"] as $k => $v ) {
				$row["gift"][$k]["thumb"] = get_image_path($v["id"], $db->getOne("SELECT goods_thumb FROM " . $ecs->table("goods") . " WHERE goods_id = '" . $v["id"] . "'"), true);
			}
		}

		break;

	case 1:
		$row["act_type"] = $_LANG["fat_price"];
		$row["act_type_ext"] .= $_LANG["unit_yuan"];
		$row["gift"] = array();
		break;

	case 2:
		$row["act_type"] = $_LANG["fat_discount"];
		$row["act_type_ext"] .= "%";
		$row["gift"] = array();
		break;
	}

	$list[$row["actType"]]["activity_name"] = $row["act_type"];
	$list[$row["actType"]]["activity_list"][] = $row;
}

$list = get_cache_site_file("activity", $list);
ksort($list);
$smarty->assign("activity_list", $list);
$smarty->assign("helps", get_shop_help());
$smarty->assign("lang", $_LANG);
$smarty->assign("feed_url", $_CFG["rewrite"] == 1 ? "feed-typeactivity.xml" : "feed.php?type=activity");
$smarty->display("activity.dwt");

?>
