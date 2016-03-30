<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function index_get_invoice_query()
{
	$sql = "SELECT o.order_sn, o.invoice_no, s.shipping_code FROM " . $GLOBALS["ecs"]->table("order_info") . " AS o LEFT JOIN " . $GLOBALS["ecs"]->table("shipping") . " AS s ON s.shipping_id = o.shipping_id WHERE invoice_no > '' AND shipping_status = " . SS_SHIPPED . " ORDER BY shipping_time DESC LIMIT 10";
	$all = $GLOBALS["db"]->getAll($sql);

	foreach ($all as $key => $row ) {
		$plugin = ROOT_PATH . "includes/modules/shipping/" . $row["shipping_code"] . ".php";

		if (file_exists($plugin)) {
			include_once ($plugin);
			$shipping = new $row["shipping_code"]();
			$all[$key]["invoice_no"] = $shipping->query((string) $row["invoice_no"]);
		}
	}

	clearstatcache();
	return $all;
}

function index_get_new_articles()
{
	$sql = "SELECT a.article_id, a.title, ac.cat_name, a.add_time, a.file_url, a.open_type, ac.cat_id, ac.cat_name  FROM " . $GLOBALS["ecs"]->table("article") . " AS a, " . $GLOBALS["ecs"]->table("article_cat") . " AS ac WHERE a.is_open = 1 AND a.cat_id = ac.cat_id AND ac.cat_type = 1 ORDER BY a.article_type DESC, a.add_time DESC LIMIT " . $GLOBALS["_CFG"]["article_number"];
	$res = $GLOBALS["db"]->getAll($sql);
	$arr = array();

	foreach ($res as $idx => $row ) {
		$arr[$idx]["id"] = $row["article_id"];
		$arr[$idx]["title"] = $row["title"];
		$arr[$idx]["short_title"] = (0 < $GLOBALS["_CFG"]["article_title_length"] ? sub_str($row["title"], $GLOBALS["_CFG"]["article_title_length"]) : $row["title"]);
		$arr[$idx]["cat_name"] = $row["cat_name"];
		$arr[$idx]["add_time"] = local_date($GLOBALS["_CFG"]["date_format"], $row["add_time"]);
		$arr[$idx]["url"] = ($row["open_type"] != 1 ? build_uri("article", array("aid" => $row["article_id"]), $row["title"]) : trim($row["file_url"]));
		$arr[$idx]["cat_url"] = build_uri("article_cat", array("acid" => $row["cat_id"]), $row["cat_name"]);
	}

	return $arr;
}

function index_get_group_buy()
{
	$time = gmtime();
	$limit = get_library_number("group_buy", "index");
	$group_buy_list = array();

	if (0 < $limit) {
		$sql = "SELECT gb.act_id AS group_buy_id, gb.goods_id, gb.ext_info, gb.goods_name, gb.start_time, gb.end_time, g.goods_thumb, g.goods_img, g.market_price FROM " . $GLOBALS["ecs"]->table("goods_activity") . " AS gb, " . $GLOBALS["ecs"]->table("goods") . " AS g WHERE gb.act_type = '" . GAT_GROUP_BUY . "' AND g.goods_id = gb.goods_id AND gb.start_time <= '" . $time . "' AND gb.end_time >= '" . $time . "' AND g.is_delete = 0 ORDER BY gb.act_id DESC LIMIT $limit";
		$res = $GLOBALS["db"]->query($sql);

		while ($row = $GLOBALS["db"]->fetchRow($res)) {
			$row["goods_img"] = get_image_path($row["goods_id"], $row["goods_img"]);
			$row["thumb"] = get_image_path($row["goods_id"], $row["goods_thumb"], true);
			$ext_info = unserialize($row["ext_info"]);
			$price_ladder = $ext_info["price_ladder"];
			if (!is_array($price_ladder) || empty($price_ladder)) {
				$row["last_price"] = price_format(0);
			}
			else {
				foreach ($price_ladder as $amount_price ) {
					$price_ladder[$amount_price["amount"]] = $amount_price["price"];
				}
			}

			ksort($price_ladderp);
			$row["last_price"] = price_format(end($price_ladder));
			$price = $row["market_price"];
			$nowprice = $row["last_price"];
			$row["jiesheng"] = $price - $nowprice;

			if (0 < $nowprice) {
				$row["zhekou"] = round(10 / $price / $nowprice, 1);
			}
			else {
				$row["zhekou"] = 0;
			}

			$activity_row = $GLOBALS["db"]->getRow($sql);
			$stat = group_buy_stat($row["group_buy_id"], $ext_info["deposit"]);
			$row["cur_amount"] = $stat["valid_goods"];
			$row["start_time"] = $row["start_time"];
			$row["end_time"] = $row["end_time"];
			$row["url"] = build_uri("group_buy", array("gbid" => $row["group_buy_id"]));
			$row["short_name"] = (0 < $GLOBALS["_CFG"]["goods_name_length"] ? sub_str($row["goods_name"], $GLOBALS["_CFG"]["goods_name_length"]) : $row["goods_name"]);
			$row["short_style_name"] = add_style($row["short_name"], "");
			$group_buy_list[] = $row;
		}
	}

	return $group_buy_list;
}

function index_get_auction()
{
	$now = gmtime();
	$limit = get_library_number("auction", "index");
	$sql = "SELECT a.act_id, a.goods_id, a.goods_name, a.ext_info, g.goods_thumb FROM " . $GLOBALS["ecs"]->table("goods_activity") . " AS a," . $GLOBALS["ecs"]->table("goods") . " AS g WHERE a.goods_id = g.goods_id AND a.act_type = '" . GAT_AUCTION . "' AND a.is_finished = 0 AND a.start_time <= '$now' AND a.end_time >= '$now' AND g.is_delete = 0 ORDER BY a.start_time DESC LIMIT $limit";
	$res = $GLOBALS["db"]->query($sql);
	$list = array();

	while ($row = $GLOBALS["db"]->fetchRow($res)) {
		$ext_info = unserialize($row["ext_info"]);
		$arr = array_merge($row, $ext_info);
		$arr["formated_start_price"] = price_format($arr["start_price"]);
		$arr["formated_end_price"] = price_format($arr["end_price"]);
		$arr["thumb"] = get_image_path($row["goods_id"], $row["goods_thumb"], true);
		$arr["url"] = build_uri("auction", array("auid" => $arr["act_id"]));
		$arr["short_name"] = (0 < $GLOBALS["_CFG"]["goods_name_length"] ? sub_str($arr["goods_name"], $GLOBALS["_CFG"]["goods_name_length"]) : $arr["goods_name"]);
		$arr["short_style_name"] = add_style($arr["short_name"], "");
		$list[] = $arr;
	}

	return $list;
}

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");
if (isset($_GET["code"]) && !empty($_GET["code"])) {
	$redirect_url = $ecs->url() . "user.php?act=oath_login&type=qq&callblock=" . $ecs->url() . "&code=" . $_GET["code"];
	header("location:" . $redirect_url);
	exit();
}

if ((DEBUG_MODE & 2) != 2) {
	$smarty->caching = true;
}

require (ROOT_PATH . "/includes/lib_area.php");
$area_info = get_area_info($province_id);
$area_id = $area_info["region_id"];
$where = "regionId = '$province_id'";
$date = array("parent_id");
$region_id = get_table_date("region_warehouse", $where, $date, 2);
$ua = strtolower($_SERVER["HTTP_USER_AGENT"]);
$uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";
if ((($ua == "") || preg_match($uachar, $ua)) && !strpos(strtolower($_SERVER["REQUEST_URI"]), "wap")) {
	$Loaction = "mobile/";

	if (!empty($Loaction)) {
		ecs_header("Location: {$Loaction}\n");
		exit();
	}
}

if (!empty($_GET["gOo"])) {
	if (!empty($_GET["gcat"])) {
		$Loaction = "category.php?id=" . $_GET["gcat"];
	}
	else if (!empty($_GET["acat"])) {
		$Loaction = "article_cat.php?id=" . $_GET["acat"];
	}
	else if (!empty($_GET["goodsid"])) {
		$Loaction = "goods.php?id=" . $_GET["goodsid"];
	}
	else if (!empty($_GET["articleid"])) {
		$Loaction = "article.php?id=" . $_GET["articleid"];
	}

	if (!empty($Loaction)) {
		ecs_header("Location: {$Loaction}\n");
		exit();
	}
}

$cache_id = sprintf("%X", crc32($_SESSION["user_rank"] . "-" . $_CFG["lang"]));

if (!$smarty->is_cached("index.dwt", $cache_id)) {
	assign_template();
	$position = assign_ur_here();
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);
	$smarty->assign("keywords", htmlspecialchars($_CFG["shop_keywords"]));
	$smarty->assign("description", htmlspecialchars($_CFG["shop_desc"]));
	$smarty->assign("flash_theme", $_CFG["flash_theme"]);
	$smarty->assign("feed_url", $_CFG["rewrite"] == 1 ? "feed.xml" : "feed.php");

	for ($i = 1; $i <= $_CFG["auction_ad"]; $i++) {
		$ad_arr .= "'c" . $i . ",";
		$index_ad .= "'index_ad" . $i . ",";
		$cat_goods_banner .= "'cat_goods_banner" . $i . ",";
		$cat_goods_hot .= "'cat_goods_hot" . $i . ",";
		$index_brand_banner .= "'index_brand_banner" . $i . ",";
		$index_brand_street .= "'index_brand_street" . $i . ",";
		$index_group_banner .= "'index_group_banner" . $i . ",";
		$index_banner_group .= "'index_banner_group" . $i . ",";
	}

	$smarty->assign("adarr", $ad_arr);
	$smarty->assign("index_ad", $index_ad);
	$smarty->assign("cat_goods_banner", $cat_goods_banner);
	$smarty->assign("cat_goods_hot", $cat_goods_hot);
	$smarty->assign("index_brand_banner", $index_brand_banner);
	$smarty->assign("index_brand_street", $index_brand_street);
	$smarty->assign("index_group_banner", $index_group_banner);
	$smarty->assign("index_banner_group", $index_banner_group);
	$smarty->assign("top_banner", "top_banner");
	$smarty->assign("warehouse_id", $region_id);
	$smarty->assign("area_id", $area_id);
	$smarty->assign("helps", get_shop_help());
	$categories_pro = get_category_tree_leve_one();
	$smarty->assign("categories_pro", $categories_pro);
	$smarty->assign("new_goods", get_recommend_goods("new", "", $region_id, $area_id));
	$smarty->assign("best_goods", get_recommend_goods("best", "", $region_id, $area_id));
	$smarty->assign("hot_goods", get_recommend_goods("hot", "", $region_id, $area_id));
	$smarty->assign("promotion_goods", get_promote_goods("", $region_id, $area_id));
	$smarty->assign("guess_goods", get_guess_goods($_SESSION["user_id"], 1, 1, 9));
	$smarty->assign("guess_store", get_guess_store($_SESSION["user_id"], 2));
	$smarty->assign("data_dir", DATA_DIR);
	assign_dynamic("index", $region_id, $area_id);
}

$smarty->display("index.dwt", $cache_id);

?>
