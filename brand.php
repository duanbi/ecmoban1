<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function brand_recommend_goods($type, $brand, $cat = 0, $warehouse_id = 0, $area_id = 0, $act = "")
{
	static $result;
	$time = gmtime();

	if ($result === NULL) {
		if (0 < $cat) {
			$cat_where = "AND " . get_children($cat);
		}
		else {
			$cat_where = "";
		}

		$leftJoin = "";

		if ($GLOBALS["_CFG"]["open_area_goods"] == 1) {
			$leftJoin .= " left join " . $GLOBALS["ecs"]->table("link_area_goods") . " as lag on g.goods_id = lag.goods_id ";
			$cat_where .= " and lag.region_id = '$area_id' ";
		}

		$shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_goods") . " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_area_goods") . " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";

		if ($GLOBALS["_CFG"]["review_goods"] == 1) {
			$cate_where .= " AND g.review_status > 2 ";
		}

		$leftJoin .= "LEFT JOIN " . $GLOBALS["ecs"]->table("link_brand") . " AS lb ON lb.bid = g.brand_id ";
		$sql = "SELECT g.goods_id, g.goods_name, g.market_price, g.comments_number,g.sales_volume, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) AS org_price, IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '{$_SESSION["discount"]}') AS shop_price, promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, g.is_best, g.is_new, g.is_hot, g.is_promote FROM " . $GLOBALS["ecs"]->table("goods") . " AS g " . $leftJoin . "LEFT JOIN " . $GLOBALS["ecs"]->table("member_price") . " AS mp ON mp.goods_id = g.goods_id AND mp.user_rank = '{$_SESSION["user_rank"]}' WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.brand_id = '$brand' AND (g.is_best = 1 OR (g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time')) {$cat_where}ORDER BY g.sort_order, g.last_update DESC";
		$result = $GLOBALS["db"]->getAll($sql);
	}

	$num = 0;
	$type2lib = array("best" => "recommend_best", "new" => "recommend_new", "hot" => "recommend_hot", "promote" => "recommend_promotion");
	$num = get_library_number($type2lib[$type]);
	$idx = 0;
	$goods = array();

	foreach ($result as $row ) {
		if ($num <= $idx) {
			break;
		}

		if ((($type == "best") && ($row["is_best"] == 1)) || (($type == "promote") && ($row["is_promote"] == 1) && ($row["promote_start_date"] <= $time) && ($time <= $row["promote_end_date"]))) {
			if (0 < $row["promote_price"]) {
				$promote_price = bargain_price($row["promote_price"], $row["promote_start_date"], $row["promote_end_date"]);
				$goods[$idx]["promote_price"] = (0 < $promote_price ? price_format($promote_price) : "");
			}
			else {
				$goods[$idx]["promote_price"] = "";
			}

			$goods[$idx]["id"] = $row["goods_id"];
			$goods[$idx]["name"] = $row["goods_name"];
			$goods[$idx]["sales_volume"] = $row["sales_volume"];
			$goods[$idx]["comments_number"] = $row["comments_number"];

			if (0 < $row["market_price"]) {
				$discount_arr = get_discount($row["goods_id"]);
			}

			$goods[$idx]["zhekou"] = $discount_arr["discount"];
			$goods[$idx]["jiesheng"] = $discount_arr["jiesheng"];
			$goods[$idx]["brief"] = $row["goods_brief"];
			$goods[$idx]["brand_name"] = $row["brand_name"];
			$goods[$idx]["short_style_name"] = (0 < $GLOBALS["_CFG"]["goods_name_length"] ? sub_str($row["goods_name"], $GLOBALS["_CFG"]["goods_name_length"]) : $row["goods_name"]);
			$goods[$idx]["market_price"] = price_format($row["market_price"]);
			$goods[$idx]["shop_price"] = price_format($row["shop_price"]);
			$goods[$idx]["thumb"] = get_image_path($row["goods_id"], $row["goods_thumb"], true);
			$goods[$idx]["goods_img"] = get_image_path($row["goods_id"], $row["goods_img"]);
			$goods[$idx]["url"] = build_uri("goods", array("gid" => $row["goods_id"]), $row["goods_name"]);
			$idx++;
		}
	}

	return $goods;
}

function goods_count_by_brand($brand_id, $cate = 0, $act = "", $is_ship = "", $price_min = "", $price_max = "", $warehouse_id = 0, $area_id = 0, $is_self)
{
	$cate_where = (0 < $cate ? "AND " . get_children($cate) : "");
	$leftJoin = "";

	if ($GLOBALS["_CFG"]["open_area_goods"] == 1) {
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("link_area_goods") . " as lag on g.goods_id = lag.goods_id ";
		$cate_where .= " and lag.region_id = '$area_id' ";
	}

	$shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
	$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_goods") . " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' ";
	$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_area_goods") . " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";

	if ($GLOBALS["_CFG"]["review_goods"] == 1) {
		$cate_where .= " AND g.review_status > 2 ";
	}

	$tag_where = "";

	if ($is_ship == "is_shipping") {
		$tag_where .= " AND g.is_shipping = 1 ";
	}

	if ($is_self == 1) {
		$tag_where .= " AND g.user_id = 0 ";
	}

	if ($price_min) {
		$tag_where .= " AND g.shop_price >= $price_min ";
	}

	if ($price_max) {
		$tag_where .= " AND g.shop_price <= $price_max ";
	}

	if ($sort == "last_update") {
		$sort = "g.last_update";
	}

	$sql = "SELECT count(g.goods_id) FROM " . $GLOBALS["ecs"]->table("goods") . " AS g " . $leftJoin . "LEFT JOIN " . $GLOBALS["ecs"]->table("member_price") . " AS mp ON mp.goods_id = g.goods_id AND mp.user_rank = '{$_SESSION["user_rank"]}' LEFT JOIN " . $GLOBALS["ecs"]->table("link_brand") . " AS lb ON lb.bid = g.brand_id LEFT JOIN " . $GLOBALS["ecs"]->table("merchants_shop_brand") . " AS msb ON msb.bid = lb.bid WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND ((g.brand_id = '$brand_id' AND g.user_id = 0) OR (lb.brand_id = '$brand_id' AND g.brand_id = lb.bid AND msb.audit_status = 1)) $cate_where $tag_where ";
	return $GLOBALS["db"]->getOne($sql);
}

function brand_get_goods($brand_id, $cate, $size, $page, $sort, $order, $warehouse_id = 0, $area_id = 0, $act = "", $is_ship = "", $price_min, $price_max, $is_self)
{
	$cate_where = (0 < $cate ? "AND " . get_children($cate) : "");
	$leftJoin = "";

	if ($GLOBALS["_CFG"]["open_area_goods"] == 1) {
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("link_area_goods") . " as lag on g.goods_id = lag.goods_id ";
		$cate_where .= " and lag.region_id = '$area_id' ";
	}

	$shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
	$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_goods") . " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' ";
	$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_area_goods") . " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";

	if ($GLOBALS["_CFG"]["review_goods"] == 1) {
		$cate_where .= " AND g.review_status > 2 ";
	}

	$tag_where = "";

	if ($is_ship == "is_shipping") {
		$tag_where .= " AND g.is_shipping = 1 ";
	}

	if ($is_self == 1) {
		$tag_where .= " AND g.user_id = 0 ";
	}

	if ($price_min) {
		$tag_where .= " AND g.shop_price >= $price_min ";
	}

	if ($price_max) {
		$tag_where .= " AND g.shop_price <= $price_max ";
	}

	if ($sort == "last_update") {
		$sort = "g.last_update";
	}

	$sql = "SELECT g.goods_id, g.user_id, g.goods_name, g.market_price, g.shop_price AS org_price,g.sales_volume, " . $shop_price . "IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '{$_SESSION["discount"]}') AS shop_price, IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, g.promote_start_date, g.promote_end_date, g.is_promote, g.goods_brief, g.goods_thumb , g.goods_img FROM " . $GLOBALS["ecs"]->table("goods") . " AS g " . $leftJoin . "LEFT JOIN " . $GLOBALS["ecs"]->table("member_price") . " AS mp ON mp.goods_id = g.goods_id AND mp.user_rank = '{$_SESSION["user_rank"]}' LEFT JOIN " . $GLOBALS["ecs"]->table("link_brand") . " AS lb ON lb.bid = g.brand_id LEFT JOIN " . $GLOBALS["ecs"]->table("merchants_shop_brand") . " AS msb ON msb.bid = lb.bid WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND ((g.brand_id = '$brand_id' AND g.user_id = 0) OR (lb.brand_id = '$brand_id' AND g.brand_id = lb.bid AND msb.audit_status = 1)) $cate_where $tag_where ORDER BY $sort $order";
	$res = $GLOBALS["db"]->selectLimit($sql, $size, ($page - 1) * $size);
	$arr = array();

	while ($row = $GLOBALS["db"]->fetchRow($res)) {
		if (0 < $row["promote_price"]) {
			$promote_price = bargain_price($row["promote_price"], $row["promote_start_date"], $row["promote_end_date"]);
		}
		else {
			$promote_price = 0;
		}

		$arr[$row["goods_id"]]["goods_id"] = $row["goods_id"];

		if ($GLOBALS["display"] == "grid") {
			$arr[$row["goods_id"]]["goods_name"] = (0 < $GLOBALS["_CFG"]["goods_name_length"] ? sub_str($row["goods_name"], $GLOBALS["_CFG"]["goods_name_length"]) : $row["goods_name"]);
		}
		else {
			$arr[$row["goods_id"]]["goods_name"] = $row["goods_name"];
		}

		$arr[$row["goods_id"]]["sales_volume"] = $row["sales_volume"];
		$arr[$row["goods_id"]]["is_promote"] = $row["is_promote"];
		$arr[$row["goods_id"]]["market_price"] = price_format($row["market_price"]);
		$arr[$row["goods_id"]]["shop_price"] = price_format($row["shop_price"]);
		$arr[$row["goods_id"]]["promote_price"] = (0 < $promote_price ? price_format($promote_price) : "");
		$arr[$row["goods_id"]]["goods_brief"] = $row["goods_brief"];
		$arr[$row["goods_id"]]["goods_thumb"] = get_image_path($row["goods_id"], $row["goods_thumb"], true);
		$arr[$row["goods_id"]]["goods_img"] = get_image_path($row["goods_id"], $row["goods_img"]);
		$arr[$row["goods_id"]]["url"] = build_uri("goods", array("gid" => $row["goods_id"]), $row["goods_name"]);
		$arr[$row["goods_id"]]["count"] = selled_count($row["goods_id"]);
		$sql = "select * from " . $GLOBALS["ecs"]->table("seller_shopinfo") . " where ru_id='" . $row["user_id"] . "'";
		$basic_info = $GLOBALS["db"]->getRow($sql);
		$arr[$row["goods_id"]]["kf_type"] = $basic_info["kf_type"];
		$arr[$row["goods_id"]]["kf_ww"] = $basic_info["kf_ww"];
		$arr[$row["goods_id"]]["kf_qq"] = $basic_info["kf_qq"];
		$arr[$row["goods_id"]]["rz_shopName"] = get_shop_name($row["user_id"], 1);
		$goods_id = $row["goods_id"];
		$count = $GLOBALS["db"]->getOne("SELECT COUNT(*) FROM " . $GLOBALS["ecs"]->table("comment") . " where id_value ='$goods_id' AND status = 1 AND parent_id = 0");
		$arr[$row["goods_id"]]["review_count"] = $count;
		$mc_all = ments_count_all($row["goods_id"]);
		$mc_one = ments_count_rank_num($row["goods_id"], 1);
		$mc_two = ments_count_rank_num($row["goods_id"], 2);
		$mc_three = ments_count_rank_num($row["goods_id"], 3);
		$mc_four = ments_count_rank_num($row["goods_id"], 4);
		$mc_five = ments_count_rank_num($row["goods_id"], 5);
		$arr[$row["goods_id"]]["zconments"] = get_conments_stars($mc_all, $mc_one, $mc_two, $mc_three, $mc_four, $mc_five);
	}

	return $arr;
}

function brand_related_cat($brand)
{
	$arr[] = array("cat_id" => 0, "cat_name" => $GLOBALS["_LANG"]["all_category"], "url" => build_uri("brand", array("bid" => $brand), $GLOBALS["_LANG"]["all_category"]));
	$sql = "SELECT c.cat_id, c.cat_name, COUNT(g.goods_id) AS goods_count FROM " . $GLOBALS["ecs"]->table("category") . " AS c, " . $GLOBALS["ecs"]->table("goods") . " AS g WHERE g.brand_id = '$brand' AND c.cat_id = g.cat_id GROUP BY g.cat_id";
	$res = $GLOBALS["db"]->query($sql);

	while ($row = $GLOBALS["db"]->fetchRow($res)) {
		$row["url"] = build_uri("brand", array("cid" => $row["cat_id"], "bid" => $brand), $row["cat_name"]);
		$arr[] = $row;
	}

	return $arr;
}

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");

if ((DEBUG_MODE & 2) != 2) {
	$smarty->caching = true;
}

require (ROOT_PATH . "/includes/lib_area.php");
$area_info = get_area_info($province_id);
$where = "regionId = '$province_id'";
$date = array("parent_id");
$region_id = get_table_date("region_warehouse", $where, $date, 2);
$smarty->assign("province_row", get_region_name($province_id));
$smarty->assign("city_row", get_region_name($city_id));
$smarty->assign("district_row", get_region_name($district_id));
$province_list = get_warehouse_province();
$smarty->assign("province_list", $province_list);
$city_list = get_region_city_county($province_id);
$smarty->assign("city_list", $city_list);
$district_list = get_region_city_county($city_id);
$smarty->assign("district_list", $district_list);
$smarty->assign("open_area_goods", $GLOBALS["_CFG"]["open_area_goods"]);

if (!empty($_REQUEST["id"])) {
	$brand_id = intval($_REQUEST["id"]);
}

if (empty($brand_id)) {
	if (($_REQUEST["step"] == "load_brands") && !empty($_REQUEST["cat_key"])) {
		include_once ("includes/cls_json.php");
		$json = new JSON();
		$result = array("error" => 0, "content" => "");
		$cat_key = intval($_REQUEST["cat_key"]);
		$rome_key = intval($_REQUEST["rome_key"]) + 1;
		$brand_cat = read_static_cache("cat_brand_cache");
		if (!empty($brand_cat) && is_array($brand_cat)) {
			foreach ($brand_cat[$cat_key]["cat_id"] as $k => $v ) {
				$brands = get_brands($v["id"]);

				if ($brands) {
					$brand_list[$k] = $brands;
				}
				else {
					unset($brand_cat[$cat_key]["cat_id"][$k]);
				}
			}

			$smarty->assign("one_brand_cat", $brand_cat[$cat_key]);
			$smarty->assign("cat_key", $cat_key);
			$smarty->assign("brand_list", $brand_list);

			if (0 < count($brand_cat[$cat_key]["cat_id"])) {
				$brand_cat_ad = "";

				for ($i = 1; $i <= $_CFG["auction_ad"]; $i++) {
					$brand_cat_ad .= "'brand_cat_ad" . $i . ",";
				}

				$rome_number = array(1 => "Ⅰ", 2 => "Ⅱ", 3 => "Ⅲ", 4 => "Ⅳ", 5 => "Ⅴ", 6 => "Ⅵ", 7 => "Ⅶ", 8 => "Ⅷ", 9 => "Ⅸ", 10 => "Ⅹ", 11 => "Ⅺ", 12 => "Ⅻ", 13 => "XIII", 14 => "XIV", 15 => "XV", 16 => "XVI", 17 => "XVII", 18 => "XVIII", 19 => "XIX", 20 => "XX");
				$smarty->assign("rome_number", $rome_number[$rome_key]);
				$arr = array("ad_arr" => $brand_cat_ad, "id" => $cat_key);
				$brand_cat_ad = insert_get_adv_child($arr);
				$smarty->assign("brand_cat_ad", $brand_cat_ad);
				$result["content"] = html_entity_decode($smarty->fetch("library/load_brands.lbi"));
			}
		}

		exit($json->encode($result));
	}

	$cache_id = sprintf("%X", crc32($_CFG["lang"]));

	if (!$smarty->is_cached("brand.dwt", $cache_id)) {
		assign_template();
		$position = assign_ur_here("", $_LANG["all_brand"]);
		$smarty->assign("page_title", $position["title"]);
		$smarty->assign("ur_here", $position["ur_here"]);
		$categories_pro = get_category_tree_leve_one();
		$smarty->assign("categories_pro", $categories_pro);
		$smarty->assign("helps", get_shop_help());
		$brand_cat = read_static_cache("cat_brand_cache");

		if ($brand_cat === false) {
			$brand_cat = get_categories_tree();

			foreach ($brand_cat as $key => $val ) {
				foreach ($val["cat_id"] as $k => $v ) {
					$brands = cat_brand_count($v["id"]);

					if (!$brands) {
						unset($brand_cat[$key]["cat_id"][$k]);
					}
				}

				if (count($brand_cat[$key]["cat_id"]) == 0) {
					unset($brand_cat[$key]);
				}
			}

			write_static_cache("cat_brand_cache", $brand_cat);
		}

		$smarty->assign("brand_cat", $brand_cat);
	}

	$smarty->display("brand.dwt", $cache_id);
	exit();
}

$page = (!empty($_REQUEST["page"]) && (0 < intval($_REQUEST["page"])) ? intval($_REQUEST["page"]) : 1);
$size = (!empty($_CFG["page_size"]) && (0 < intval($_CFG["page_size"])) ? intval($_CFG["page_size"]) : 10);
$cate = (!empty($_REQUEST["cat"]) && (0 < intval($_REQUEST["cat"])) ? intval($_REQUEST["cat"]) : 0);
$is_ship = (isset($_REQUEST["is_ship"]) && !empty($_REQUEST["is_ship"]) ? trim($_REQUEST["is_ship"]) : "");
$is_self = (isset($_REQUEST["is_self"]) && !empty($_REQUEST["is_self"]) ? intval($_REQUEST["is_self"]) : "");
$price_min = (!empty($_REQUEST["price_min"]) && (0 < floatval($_REQUEST["price_min"])) ? floatval($_REQUEST["price_min"]) : "");
$price_max = (!empty($_REQUEST["price_max"]) && (0 < floatval($_REQUEST["price_max"])) ? floatval($_REQUEST["price_max"]) : "");
$default_display_type = ($_CFG["show_order_type"] == "0" ? "list" : ($_CFG["show_order_type"] == "1" ? "grid" : "text"));
$default_sort_order_method = ($_CFG["sort_order_method"] == "0" ? "DESC" : "ASC");
$default_sort_order_type = ($_CFG["sort_order_type"] == "0" ? "goods_id" : ($_CFG["sort_order_type"] == "1" ? "shop_price" : "last_update"));
$sort = (isset($_REQUEST["sort"]) && in_array(trim(strtolower($_REQUEST["sort"])), array("goods_id", "shop_price", "last_update", "sales_volume", "comments_number")) ? trim($_REQUEST["sort"]) : $default_sort_order_type);
$order = (isset($_REQUEST["order"]) && in_array(trim(strtoupper($_REQUEST["order"])), array("ASC", "DESC")) ? trim($_REQUEST["order"]) : $default_sort_order_method);
$display = (isset($_REQUEST["display"]) && in_array(trim(strtolower($_REQUEST["display"])), array("list", "grid", "text")) ? trim($_REQUEST["display"]) : (isset($_COOKIE["ECS"]["display"]) ? $_COOKIE["ECS"]["display"] : $default_display_type));
$display = (in_array($display, array("list", "grid", "text")) ? $display : "text");
setcookie("ECS[display]", $display, gmtime() + (86400 * 7));
$smarty->assign("sort", $sort);
$smarty->assign("order", $order);
$smarty->assign("price_min", $price_min);
$smarty->assign("price_max", $price_max);
$smarty->assign("is_ship", $is_ship);
$smarty->assign("self_support", $is_self);
$cache_id = sprintf("%X", crc32($brand_id . "-" . $display . "-" . $price_min . "-" . $price_max . "-" . $sort . "-" . $order . "-" . $page . "-" . $size . "-" . $_SESSION["user_rank"] . "-" . $_CFG["lang"] . "-" . $cate . "-" . $is_ship . "-" . $is_self));
$act = (isset($_REQUEST["act"]) ? $_REQUEST["act"] : "");

if (!$smarty->is_cached("brand_list.dwt", $cache_id)) {
	$brand_info = get_brand_info($brand_id, $act);

	if (empty($brand_info)) {
		ecs_header("Location: ./\n");
		exit();
	}

	$smarty->assign("data_dir", DATA_DIR);
	$smarty->assign("keywords", htmlspecialchars($brand_info["brand_desc"]));
	$smarty->assign("description", htmlspecialchars($brand_info["brand_desc"]));
	assign_template();
	$position = assign_ur_here($cate, $brand_info["brand_name"]);
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);
	$smarty->assign("brand_id", $brand_id);
	$smarty->assign("category", $cate);
	$categories_pro = get_category_tree_leve_one();
	$smarty->assign("categories_pro", $categories_pro);
	$smarty->assign("helps", get_shop_help());
	$smarty->assign("show_marketprice", $_CFG["show_marketprice"]);
	$smarty->assign("brand_cat_list", brand_related_cat($brand_id));
	$smarty->assign("feed_url", $_CFG["rewrite"] == 1 ? "feed-b$brand_id.xml" : "feed.php?brand=" . $brand_id);
	$vote = get_vote();

	if (!empty($vote)) {
		$smarty->assign("vote_id", $vote["id"]);
		$smarty->assign("vote", $vote["content"]);
	}

	$smarty->assign("best_goods", brand_recommend_goods("best", $brand_id, $cate, $region_id, $area_info["region_id"], $act));
	$smarty->assign("promotion_goods", brand_recommend_goods("promote", $brand_id, $cate, $region_id, $area_info["region_id"], $act));
	$smarty->assign("brand", $brand_info);
	$smarty->assign("promotion_info", get_promotion_info());
	$count = goods_count_by_brand($brand_id, $cate, $act, $is_ship, $price_min, $price_max, $region_id, $area_info["region_id"], $is_self);
	$goodslist = brand_get_goods($brand_id, $cate, $size, $page, $sort, $order, $region_id, $area_info["region_id"], $act, $is_ship, $price_min, $price_max, $is_self);

	if ($display == "grid") {
		if ((count($goodslist) % 2) != 0) {
			$goodslist[] = array();
		}
	}

	if (is_array($goodslist)) {
		foreach ($goodslist as $key => $vo ) {
			$goodslist[$key]["pictures"] = get_goods_gallery($key);
		}
	}

	$smarty->assign("goods_list", $goodslist);
	$smarty->assign("script_name", "brand");

	for ($i = 1; $i <= $_CFG["auction_ad"]; $i++) {
		$brand_list_left_ad .= "'brand_list_left_ad" . $i . ",";
		$brand_list_right_ad .= "'brand_list_right_ad" . $i . ",";
	}

	$smarty->assign("best_goods", get_recommend_goods("best", "", $region_id, $area_info["region_id"], $goods["user_id"], 1));
	$smarty->assign("brand_list_left_ad", $brand_list_left_ad);
	$smarty->assign("brand_list_right_ad", $brand_list_right_ad);
	assign_pager("brand", $cate, $count, $size, $sort, $order, $page, "", $brand_id, $price_min, $price_max, $display, "", "", "", 0, "", "", $act, $is_ship, $is_self);
	assign_dynamic("brand");
}

$smarty->display("brand_list.dwt", $cache_id);

?>
