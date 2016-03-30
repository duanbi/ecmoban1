<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function is_not_null($value)
{
	if (is_array($value)) {
		return !empty($value["from"]) || !empty($value["to"]);
	}
	else {
		return !empty($value);
	}
}

function get_seachable_attributes($cat_id = 0)
{
	$attributes = array(
		"cate" => array(),
		"attr" => array()
		);
	$sql = "SELECT t.cat_id, cat_name FROM " . $GLOBALS["ecs"]->table("goods_type") . " AS t, " . $GLOBALS["ecs"]->table("attribute") . " AS a WHERE t.cat_id = a.cat_id AND t.enabled = 1 AND a.attr_index > 0 ";
	$cat = $GLOBALS["db"]->getAll($sql);

	if (!empty($cat)) {
		foreach ($cat as $val ) {
			$attributes["cate"][$val["cat_id"]] = $val["cat_name"];
		}

		$where = (0 < $cat_id ? " AND a.cat_id = " . $cat_id : " AND a.cat_id = " . $cat[0]["cat_id"]);
		$sql = "SELECT attr_id, attr_name, attr_input_type, attr_type, attr_values, attr_index, sort_order  FROM " . $GLOBALS["ecs"]->table("attribute") . " AS a  WHERE a.attr_index > 0 " . $where . " ORDER BY cat_id, sort_order ASC";
		$res = $GLOBALS["db"]->query($sql);

		while ($row = $GLOBALS["db"]->FetchRow($res)) {
			if (($row["attr_index"] == 1) && ($row["attr_input_type"] == 1)) {
				$row["attr_values"] = str_replace("\r", "", $row["attr_values"]);
				$options = explode("\n", $row["attr_values"]);
				$attr_value = array();

				foreach ($options as $opt ) {
					$attr_value[$opt] = $opt;
				}

				$attributes["attr"][] = array("id" => $row["attr_id"], "attr" => $row["attr_name"], "options" => $attr_value, "type" => 3);
			}
			else {
				$attributes["attr"][] = array("id" => $row["attr_id"], "attr" => $row["attr_name"], "type" => $row["attr_index"]);
			}
		}
	}

	return $attributes;
}

define("IN_ECS", true);

if (!function_exists("htmlspecialchars_decode")) {
	function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT)
	{
		return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
	}
}

if (empty($_GET["encode"])) {
	$string = array_merge($_GET, $_POST);

	if (get_magic_quotes_gpc()) {
		require (dirname(__FILE__) . "/includes/lib_base.php");
		$string = stripslashes_deep($string);
	}

	$string["search_encode_time"] = time();
	$string = str_replace("+", "%2b", base64_encode(serialize($string)));
	header("Location:search.php?encode={$string}\n");
	exit();
}
else {
	$string = base64_decode(trim($_GET["encode"]));

	if ($string !== false) {
		$string = unserialize($string);

		if ($string !== false) {
			if (!empty($string["search_encode_time"])) {
				if (($string["search_encode_time"] + 2) < time()) {
					define("INGORE_VISIT_STATS", true);
				}
			}
			else {
				define("INGORE_VISIT_STATS", true);
			}
		}
		else {
			$string = array();
		}
	}
	else {
		$string = array();
	}
}

require (dirname(__FILE__) . "/includes/init.php");
require (ROOT_PATH . "/includes/lib_area.php");
$area_info = get_area_info($province_id);
$area_id = $area_info["region_id"];
$where = "regionId = '$province_id'";
$date = array("parent_id");
$region_id = get_table_date("region_warehouse", $where, $date, 2);
$_REQUEST = array_merge($_REQUEST, addslashes_deep($string));
$_REQUEST["act"] = (!empty($_REQUEST["act"]) ? trim($_REQUEST["act"]) : "");
$search_type = (!empty($_REQUEST["store_search_cmt"]) ? intval($_REQUEST["store_search_cmt"]) : 0);
$_REQUEST["keywords"] = (!empty($_REQUEST["keywords"]) ? htmlspecialchars(trim($_REQUEST["keywords"])) : "");
$_REQUEST["brand"] = (!empty($_REQUEST["brand"]) ? intval($_REQUEST["brand"]) : 0);
$_REQUEST["category"] = (!empty($_REQUEST["category"]) ? intval($_REQUEST["category"]) : 0);
$_REQUEST["price_min"] = (!empty($_REQUEST["price_min"]) ? intval($_REQUEST["price_min"]) : 0);
$_REQUEST["price_max"] = (!empty($_REQUEST["price_max"]) ? intval($_REQUEST["price_max"]) : 0);
$_REQUEST["goods_type"] = (!empty($_REQUEST["goods_type"]) ? intval($_REQUEST["goods_type"]) : 0);
$_REQUEST["sc_ds"] = (!empty($_REQUEST["sc_ds"]) ? intval($_REQUEST["sc_ds"]) : 0);
$_REQUEST["outstock"] = (!empty($_REQUEST["outstock"]) ? 1 : 0);
$smarty->assign("search_type", $search_type);
$smarty->assign("search_keywords", stripslashes(htmlspecialchars_decode($_REQUEST["keywords"])));
$default_sort_order_method = ($_CFG["sort_order_method"] == "0" ? "DESC" : "ASC");
$order = (isset($_REQUEST["order"]) && in_array(trim(strtoupper($_REQUEST["order"])), array("ASC", "DESC")) ? trim($_REQUEST["order"]) : $default_sort_order_method);
$display = (isset($_REQUEST["display"]) && in_array(trim(strtolower($_REQUEST["display"])), array("list", "grid", "text")) ? trim($_REQUEST["display"]) : (isset($_SESSION["display_search"]) ? $_SESSION["display_search"] : "list"));
$_SESSION["display_search"] = $display;

if ($search_type == 1) {
	if ($display == "list") {
		$default_sort_order_type = "shop_id";
		$sort = (isset($_REQUEST["sort"]) && in_array(trim(strtolower($_REQUEST["sort"])), array("shop_id", "goods_number", "sales_volume")) ? trim($_REQUEST["sort"]) : $default_sort_order_type);
	}
	else {
		if (($display == "grid") || ($display == "text")) {
			$default_sort_order_type = ($_CFG["sort_order_type"] == "0" ? "goods_id" : ($_CFG["sort_order_type"] == "1" ? "shop_price" : "last_update"));
			$sort = (isset($_REQUEST["sort"]) && in_array(trim(strtolower($_REQUEST["sort"])), array("goods_id", "shop_price", "last_update", "sales_volume")) ? trim($_REQUEST["sort"]) : $default_sort_order_type);
		}
	}
}
else {
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
	$default_sort_order_type = ($_CFG["sort_order_type"] == "0" ? "goods_id" : ($_CFG["sort_order_type"] == "1" ? "shop_price" : "last_update"));
	$sort = (isset($_REQUEST["sort"]) && in_array(trim(strtolower($_REQUEST["sort"])), array("goods_id", "shop_price", "last_update", "sales_volume", "comments_number")) ? trim($_REQUEST["sort"]) : $default_sort_order_type);
	$is_ship = (isset($_REQUEST["is_ship"]) && !empty($_REQUEST["is_ship"]) ? trim($_REQUEST["is_ship"]) : "");
	$is_self = (isset($_REQUEST["is_self"]) && !empty($_REQUEST["is_self"]) ? intval($_REQUEST["is_self"]) : "");
}

$page = (!empty($_REQUEST["page"]) && (0 < intval($_REQUEST["page"])) ? intval($_REQUEST["page"]) : 1);
$size = (!empty($_CFG["page_size"]) && (0 < intval($_CFG["page_size"])) ? intval($_CFG["page_size"]) : 10);

if ($_REQUEST["act"] == "advanced_search") {
	$goods_type = (!empty($_REQUEST["goods_type"]) ? intval($_REQUEST["goods_type"]) : 0);
	$attributes = get_seachable_attributes($goods_type);
	$smarty->assign("goods_type_selected", $goods_type);
	$smarty->assign("goods_type_list", $attributes["cate"]);
	$smarty->assign("goods_attributes", $attributes["attr"]);
	assign_template();
	assign_dynamic("search");
	$position = assign_ur_here(0, $_LANG["advanced_search"]);
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);
	$categories_pro = get_category_tree_leve_one();
	$smarty->assign("categories_pro", $categories_pro);
	$smarty->assign("helps", get_shop_help());
	$smarty->assign("top_goods", get_top10());
	$smarty->assign("promotion_info", get_promotion_info());
	$smarty->assign("cat_list", cat_list(0, 0, true, 2, false));
	$smarty->assign("brand_list", get_brand_list());
	$smarty->assign("action", "form");
	$smarty->assign("use_storage", $_CFG["use_storage"]);

	if ($search_type == 0) {
		$smarty->assign("best_goods", get_recommend_goods("best", "", $region_id, $area_info["region_id"], $goods["user_id"], 1));
		$smarty->display("search.dwt");
	}
	else if ($search_type == 1) {
		$smarty->display("merchants_shop_list.dwt");
	}

	exit();
}
else {
	if ($search_type == 0) {
		$ur_here = "搜索商品";
	}
	else if ($search_type == 1) {
		$ur_here = "搜索店铺";
	}

	assign_template();
	assign_dynamic("search");
	$position = assign_ur_here(0, $ur_here . ($_REQUEST["keywords"] ? "_" . $_REQUEST["keywords"] : ""));
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);
	$categories_pro = get_category_tree_leve_one();
	$smarty->assign("categories_pro", $categories_pro);
	$smarty->assign("intromode", $intromode);
	$smarty->assign("helps", get_shop_help());
	$smarty->assign("top_goods", get_top10());
	$smarty->assign("promotion_info", get_promotion_info());
	$smarty->assign("region_id", $region_id);
	$smarty->assign("area_id", $area_id);

	if ($search_type == 0) {
		$action = "";
		if (isset($_REQUEST["action"]) && ($_REQUEST["action"] == "form")) {
			$adv_value["keywords"] = htmlspecialchars(stripcslashes($_REQUEST["keywords"]));
			$adv_value["brand"] = $_REQUEST["brand"];
			$adv_value["price_min"] = $_REQUEST["price_min"];
			$adv_value["price_max"] = $_REQUEST["price_max"];
			$adv_value["category"] = $_REQUEST["category"];
			$attributes = get_seachable_attributes($_REQUEST["goods_type"]);

			foreach ($attributes["attr"] as $key => $val ) {
				if (!empty($_REQUEST["attr"][$val["id"]])) {
					if ($val["type"] == 2) {
						$attributes["attr"][$key]["value"]["from"] = (!empty($_REQUEST["attr"][$val["id"]]["from"]) ? htmlspecialchars(stripcslashes(trim($_REQUEST["attr"][$val["id"]]["from"]))) : "");
						$attributes["attr"][$key]["value"]["to"] = (!empty($_REQUEST["attr"][$val["id"]]["to"]) ? htmlspecialchars(stripcslashes(trim($_REQUEST["attr"][$val["id"]]["to"]))) : "");
					}
					else {
						$attributes["attr"][$key]["value"] = (!empty($_REQUEST["attr"][$val["id"]]) ? htmlspecialchars(stripcslashes(trim($_REQUEST["attr"][$val["id"]]))) : "");
					}
				}
			}

			if ($_REQUEST["sc_ds"]) {
				$smarty->assign("scck", "checked");
			}

			$smarty->assign("adv_val", $adv_value);
			$smarty->assign("goods_type_list", $attributes["cate"]);
			$smarty->assign("goods_attributes", $attributes["attr"]);
			$smarty->assign("goods_type_selected", $_REQUEST["goods_type"]);
			$smarty->assign("cat_list", cat_list(0, $adv_value["category"], true, 2, false));
			$smarty->assign("brand_list", get_brand_list());
			$smarty->assign("action", "form");
			$smarty->assign("use_storage", $_CFG["use_storage"]);
			$action = "form";
		}

		$keywords = "";
		$tag_where = "";

		if (!empty($_REQUEST["keywords"])) {
			$arr = array();
			$insert_keyword = trim($_REQUEST["keywords"]);
			$pin = new pin();
			$pinyin = $pin->Pinyin($insert_keyword, "UTF8");
			$addtime = local_date("Y-m-d", gmtime());
			$sql = "INSERT INTO " . $ecs->table("search_keyword") . "(keyword, pinyin, is_on, count, addtime, pinyin_keyword)VALUES('$insert_keyword', '', '0', '1', '$addtime', '$pinyin')";
			$db->query($sql);
			$scws_res = scws($_REQUEST["keywords"]);
			$arr = explode(",", $scws_res);
			$arr_keyword = $arr;
			$operator = " AND ";

			if (empty($arr[0])) {
				$arr[0] = $insert_keyword;
			}

			$keywords = "AND (";
			$goods_ids = array();

			foreach ($arr as $key => $val ) {
				if ((0 < $key) && ($key < count($arr)) && (1 < count($arr))) {
					$keywords .= $operator;
				}

				$val = mysql_like_quote(trim($val));
				$sc_dsad = ($_REQUEST["sc_ds"] ? " OR goods_desc LIKE '%$val%'" : "");
				$keywords .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%' $sc_dsad)";
				$sql = "SELECT DISTINCT goods_id FROM " . $ecs->table("tag") . " WHERE tag_words LIKE '%$val%' ";
				$res = $db->query($sql);

				while ($row = $db->FetchRow($res)) {
					$goods_ids[] = $row["goods_id"];
				}

				$db->autoReplace($ecs->table("keywords"), array("date" => local_date("Y-m-d"), "searchengine" => "DSC_B2B2C", "keyword" => addslashes(str_replace("%", "", $val)), "count" => 1), array("count" => 1));
			}

			$keywords .= ")";
			$goods_ids = array_unique($goods_ids);
			$tag_where = implode(",", $goods_ids);

			if (!empty($tag_where)) {
				$tag_where = "OR g.goods_id " . db_create_in($tag_where);
			}
		}

		$children = get_category_parentchild_tree1($category, 1, 0, 1);
		$children = arr_foreach($children);

		if ($children) {
			$children = implode(",", $children) . "," . $category;
			$children = get_children($children, 0, 1);
		}
		else {
			$children = "g.cat_id IN ($category)";
		}

		$category = (!empty($_REQUEST["category"]) ? intval($_REQUEST["category"]) : 0);
		$categories = (0 < $category ? " AND " . $children : "");
		$brand = ($_REQUEST["brand"] ? " AND brand_id = '{$_REQUEST["brand"]}'" : "");
		$outstock = (!empty($_REQUEST["outstock"]) ? " AND g.goods_number > 0 " : "");
		$price_min = ($_REQUEST["price_min"] != 0 ? " AND g.shop_price >= '{$_REQUEST["price_min"]}'" : "");
		$price_max = (($_REQUEST["price_max"] != 0) || ($_REQUEST["price_min"] < 0) ? " AND g.shop_price <= '{$_REQUEST["price_max"]}'" : "");
		$intromode = "";

		if (!empty($_REQUEST["intro"])) {
			switch ($_REQUEST["intro"]) {
			case "best":
				$intro = " AND g.is_best = 1";
				$intromode = "best";
				$ur_here = $_LANG["best_goods"];
				break;

			case "new":
				$intro = " AND g.is_new = 1";
				$intromode = "new";
				$ur_here = $_LANG["new_goods"];
				break;

			case "hot":
				$intro = " AND g.is_hot = 1";
				$intromode = "hot";
				$ur_here = $_LANG["hot_goods"];
				break;

			case "promotion":
				$time = gmtime();
				$intro = " AND g.promote_price > 0 AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time'";
				$intromode = "promotion";
				$ur_here = $_LANG["promotion_goods"];
				break;

			default:
				$intro = "";
			}
		}
		else {
			$intro = "";
		}

		if (empty($ur_here)) {
			$ur_here = $_LANG["search_goods"];
		}

		$attr_in = "";
		$attr_num = 0;
		$attr_url = "";
		$attr_arg = array();

		if (!empty($_REQUEST["attr"])) {
			$sql = "SELECT goods_id, COUNT(*) AS num FROM " . $ecs->table("goods_attr") . " WHERE 0 ";

			foreach ($_REQUEST["attr"] as $key => $val ) {
				if (is_not_null($val) && is_numeric($key)) {
					$attr_num++;
					$sql .= " OR (1 ";

					if (is_array($val)) {
						$sql .= " AND attr_id = '$key'";

						if (!empty($val["from"])) {
							$sql .= (is_numeric($val["from"]) ? " AND attr_value >= " . floatval($val["from"]) : " AND attr_value >= '{$val["from"]}'");
							$attr_arg["attr[$key][from]"] = $val["from"];
							$attr_url .= "&amp;attr[$key][from]={$val["from"]}";
						}

						if (!empty($val["to"])) {
							$sql .= (is_numeric($val["to"]) ? " AND attr_value <= " . floatval($val["to"]) : " AND attr_value <= '{$val["to"]}'");
							$attr_arg["attr[$key][to]"] = $val["to"];
							$attr_url .= "&amp;attr[$key][to]={$val["to"]}";
						}
					}
					else {
						$sql .= (isset($_REQUEST["pickout"]) ? " AND attr_id = '$key' AND attr_value = '" . $val . "' " : " AND attr_id = '$key' AND attr_value LIKE '%" . mysql_like_quote($val) . "%' ");
						$attr_url .= "&amp;attr[$key]=$val";
						$attr_arg["attr[$key]"] = $val;
					}

					$sql .= ")";
				}
			}

			if (0 < $attr_num) {
				$sql .= " GROUP BY goods_id HAVING num = '$attr_num'";
				$row = $db->getCol($sql);

				if (count($row)) {
					$attr_in = " AND " . db_create_in($row, "g.goods_id");
				}
				else {
					$attr_in = " AND 0 ";
				}
			}
		}
		else if (isset($_REQUEST["pickout"])) {
			$sql = "SELECT DISTINCT(goods_id) FROM " . $ecs->table("goods_attr");
			$col = $db->getCol($sql);

			if (!empty($col)) {
				$attr_in = " AND " . db_create_in($col, "g.goods_id");
			}
		}

		$leftJoin = "";
		$leftJoin .= "LEFT JOIN " . $GLOBALS["ecs"]->table("brand") . " AS b ON b.brand_id = g.brand_id ";
		$leftJoin .= "LEFT JOIN " . $GLOBALS["ecs"]->table("link_brand") . " AS lb ON lb.bid = g.brand_id ";
		$leftJoin .= "LEFT JOIN " . $GLOBALS["ecs"]->table("merchants_shop_brand") . " AS msb ON msb.bid = lb.bid ";
		$tag_where .= "AND (b.audit_status = 1 OR msb.audit_status = 1) ";
		$shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_goods") . " as wg on g.goods_id = wg.goods_id and wg.region_id = '$region_id' ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_area_goods") . " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";
		$area_where = "";

		if ($GLOBALS["_CFG"]["open_area_goods"] == 1) {
			$leftJoin .= " left join " . $GLOBALS["ecs"]->table("link_area_goods") . " as lag on g.goods_id = lag.goods_id ";
			$area_where = " and lag.region_id = '$area_id' ";
		}

		if ($GLOBALS["_CFG"]["review_goods"] == 1) {
			$tag_where .= " AND g.review_status > 2 ";
		}

		if ($is_ship == "is_shipping") {
			$tag_where .= " AND g.is_shipping = 1 ";
		}

		if ($is_self == 1) {
			$tag_where .= " AND g.user_id = 0 ";
		}

		$sql = "SELECT COUNT(*) FROM " . $ecs->table("goods") . " AS g " . $leftJoin . "WHERE g.is_delete = 0 AND g.is_on_sale = 1 " . $area_where . " AND g.is_alone_sale = 1 $attr_in AND (( 1 " . $categories . $keywords . $brand . $price_min . $price_max . $intro . $outstock . " ) " . $tag_where . " )";
		$count = $db->getOne($sql);
		$max_page = (0 < $count ? ceil($count / $size) : 1);

		if ($max_page < $page) {
			$page = $max_page;
		}

		$sel_msb = "(g.brand_id IN(SELECT msb.bid FROM " . $GLOBALS["ecs"]->table("brand") . " AS b, " . $GLOBALS["ecs"]->table("link_brand") . " AS lb, " . $GLOBALS["ecs"]->table("merchants_shop_brand") . " AS msb WHERE b.is_show = 1 AND b.brand_id = lb.brand_id AND lb.bid = msb.bid AND msb.is_show = 1 AND msb.audit_status = 1) AND g.user_id > 0)";
		$sel_brand = "(g.brand_id IN(SELECT b.brand_id FROM " . $GLOBALS["ecs"]->table("brand") . " AS b WHERE b.is_show = 1) AND g.user_id = 0)";
		$tag_where .= "AND ( " . $sel_brand . " OR " . $sel_msb . ")";
		$sql = "SELECT g.goods_id, g.user_id, g.goods_name, g.market_price, g.is_new, g.comments_number, g.sales_volume, g.is_best, g.is_hot,g.store_new, g.store_best, g.store_hot, " . $shop_price . "IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) AS org_price, IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '{$_SESSION["discount"]}') AS shop_price, IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, g.promote_start_date, g.promote_end_date, g.is_promote, g.goods_thumb, g.goods_img, g.goods_brief, g.goods_type FROM " . $ecs->table("goods") . " AS g " . $leftJoin . "LEFT JOIN " . $GLOBALS["ecs"]->table("member_price") . " AS mp ON mp.goods_id = g.goods_id AND mp.user_rank = '{$_SESSION["user_rank"]}' WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 $attr_in AND (( 1 " . $categories . $keywords . $brand . $price_min . $price_max . $intro . $outstock . " ) " . $tag_where . " ) ORDER BY g.$sort $order";
		$res = $db->SelectLimit($sql, $size, ($page - 1) * $size);
		$arr = array();

		while ($row = $db->FetchRow($res)) {
			if (0 < $row["promote_price"]) {
				$promote_price = bargain_price($row["promote_price"], $row["promote_start_date"], $row["promote_end_date"]);
			}
			else {
				$promote_price = 0;
			}

			$watermark_img = "";

			if ($promote_price != 0) {
				$watermark_img = "watermark_promote_small";
			}
			else if ($row["is_new"] != 0) {
				$watermark_img = "watermark_new_small";
			}
			else if ($row["is_best"] != 0) {
				$watermark_img = "watermark_best_small";
			}
			else if ($row["is_hot"] != 0) {
				$watermark_img = "watermark_hot_small";
			}

			if ($watermark_img != "") {
				$arr[$row["goods_id"]]["watermark_img"] = $watermark_img;
			}

			$arr[$row["goods_id"]]["goods_id"] = $row["goods_id"];

			if ($display == "grid") {
				$goods_name_keyword = sub_str($row["goods_name"], $GLOBALS["_CFG"]["goods_name_length"]);
				$goods_name_keyword = "<text>" . $goods_name . "</text>";

				foreach ($arr_keyword as $key => $val_keyword ) {
					$goods_name_keyword = preg_replace("/(>.*)($val_keyword)(.*<)/Ui", "\$1<font style='color:#ec5151;'>$val_keyword</font>\$3", $goods_name);
				}

				$arr[$row["goods_id"]]["goods_name_keyword"] = (0 < $GLOBALS["_CFG"]["goods_name_length"] ? $goods_name_keyword : $goods_name_keyword);
				$arr[$row["goods_id"]]["goods_name"] = (0 < $GLOBALS["_CFG"]["goods_name_length"] ? $row["goods_name"] : $row["goods_name"]);
			}
			else {
				$goods_name_keyword = "<text>" . $row["goods_name"] . "</text>";

				foreach ($arr_keyword as $key => $val_keyword ) {
					$goods_name_keyword = preg_replace("/(>.*)($val_keyword)(.*<)/Ui", "\$1<font style='color:#ec5151;'>$val_keyword</font>\$3", $goods_name_keyword);
				}

				$arr[$row["goods_id"]]["goods_name_keyword"] = $goods_name_keyword;
				$arr[$row["goods_id"]]["goods_name"] = $row["goods_name"];
			}

			if (0 < $row["market_price"]) {
				$discount_arr = get_discount($row["goods_id"]);
			}

			$arr[$row["goods_id"]]["zhekou"] = $discount_arr["discount"];
			$arr[$row["goods_id"]]["jiesheng"] = $discount_arr["jiesheng"];
			$arr[$row["goods_id"]]["type"] = $row["goods_type"];
			$arr[$row["goods_id"]]["is_promote"] = $row["is_promote"];
			$arr[$row["goods_id"]]["comments_number"] = $row["comments_number"];
			$arr[$row["goods_id"]]["sales_volume"] = $row["sales_volume"];
			$arr[$row["goods_id"]]["market_price"] = price_format($row["market_price"]);
			$arr[$row["goods_id"]]["shop_price"] = price_format($row["shop_price"]);
			$arr[$row["goods_id"]]["promote_price"] = (0 < $promote_price ? price_format($promote_price) : "");
			$arr[$row["goods_id"]]["goods_brief"] = $row["goods_brief"];
			$arr[$row["goods_id"]]["goods_thumb"] = get_image_path($row["goods_id"], $row["goods_thumb"], true);
			$arr[$row["goods_id"]]["goods_img"] = get_image_path($row["goods_id"], $row["goods_img"]);
			$arr[$row["goods_id"]]["url"] = build_uri("goods", array("gid" => $row["goods_id"]), $row["goods_name"]);
			$arr[$row["goods_id"]]["count"] = selled_count($row["goods_id"]);
			$mc_all = ments_count_all($row["goods_id"]);
			$mc_one = ments_count_rank_num($row["goods_id"], 1);
			$mc_two = ments_count_rank_num($row["goods_id"], 2);
			$mc_three = ments_count_rank_num($row["goods_id"], 3);
			$mc_four = ments_count_rank_num($row["goods_id"], 4);
			$mc_five = ments_count_rank_num($row["goods_id"], 5);
			$arr[$row["goods_id"]]["zconments"] = get_conments_stars($mc_all, $mc_one, $mc_two, $mc_three, $mc_four, $mc_five);
			$goods_id = $row["goods_id"];
			$countt = $GLOBALS["db"]->getOne("SELECT COUNT(*) FROM " . $GLOBALS["ecs"]->table("comment") . " where comment_type=0 and id_value ='$goods_id'");
			$arr[$row["goods_id"]]["review_count"] = $countt;
			$arr[$row["goods_id"]]["rz_shopName"] = get_shop_name($row["user_id"], 1);
			$arr[$row["goods_id"]]["is_new"] = $row["is_new"];
			$arr[$row["goods_id"]]["is_best"] = $row["is_best"];
			$arr[$row["goods_id"]]["is_hot"] = $row["is_hot"];
			$sql = "select * from " . $GLOBALS["ecs"]->table("seller_shopinfo") . " where ru_id='" . $row["user_id"] . "'";
			$basic_info = $GLOBALS["db"]->getRow($sql);
			$arr[$row["goods_id"]]["kf_type"] = $basic_info["kf_type"];
			$arr[$row["goods_id"]]["kf_ww"] = $basic_info["kf_ww"];
			$arr[$row["goods_id"]]["kf_qq"] = $basic_info["kf_qq"];
			$arr[$row["goods_id"]]["is_collect"] = get_collect_user_goods($row["goods_id"]);
		}

		if ($display == "grid") {
			if ((count($arr) % 2) != 0) {
				$arr[] = array();
			}
		}

		if (is_array($arr)) {
			foreach ($arr as $key => $vo ) {
				$arr[$key]["pictures"] = get_goods_gallery($key);
			}
		}

		$smarty->assign("goods_list", $arr);
		$smarty->assign("category", $category);
		$smarty->assign("keywords", htmlspecialchars(stripslashes($_REQUEST["keywords"])));
		$smarty->assign("brand", $_REQUEST["brand"]);
		$smarty->assign("price_min", $price_min);
		$smarty->assign("price_max", $price_max);
		$smarty->assign("outstock", $_REQUEST["outstock"]);
		$url_format = "search.php?category=$category&amp;keywords=" . urlencode(stripslashes($_REQUEST["keywords"])) . "&amp;brand=" . $_REQUEST["brand"] . "&amp;action=" . $action . "&amp;goods_type=" . $_REQUEST["goods_type"] . "&amp;sc_ds=" . $_REQUEST["sc_ds"];

		if (!empty($intromode)) {
			$url_format .= "&amp;intro=" . $intromode;
		}

		if (isset($_REQUEST["pickout"])) {
			$url_format .= "&amp;pickout=1";
		}

		$url_format .= "&amp;price_min=" . $_REQUEST["price_min"] . "&amp;price_max=" . $_REQUEST["price_max"] . "&amp;sort=$sort";
		$url_format .= "$attr_url&amp;order=$order&amp;page=";
		$pager["search"] = array("keywords" => stripslashes(trim($_REQUEST["keywords"])), "category" => $category, "store_search_cmt" => intval($_REQUEST["store_search_cmt"]), "brand" => $_REQUEST["brand"], "sort" => $sort, "order" => $order, "price_min" => $_REQUEST["price_min"], "price_max" => $_REQUEST["price_max"], "action" => $action, "intro" => empty($intromode) ? "" : trim($intromode), "goods_type" => $_REQUEST["goods_type"], "sc_ds" => $_REQUEST["sc_ds"], "outstock" => $_REQUEST["outstock"], "is_ship" => $is_ship, "self_support" => $is_self, "is_in_stock" => $is_in_stock);
		$pager["search"] = array_merge($pager["search"], $attr_arg);
		$pager = get_pager("search.php", $pager["search"], $count, $page, $size);
		$pager["display"] = $display;
		$smarty->assign("url_format", $url_format);
		$smarty->assign("pager", $pager);

		for ($i = 1; $i <= $_CFG["auction_ad"]; $i++) {
			$search_left_ad .= "'search_left_ad" . $i . ",";
			$search_right_ad .= "'search_right_ad" . $i . ",";
		}

		$smarty->assign("search_left_ad", $search_left_ad);
		$smarty->assign("search_right_ad", $search_right_ad);
		$smarty->assign("best_goods", get_recommend_goods("best", "", $region_id, $area_info["region_id"], $goods["user_id"], 1));
		$cur_url = get_return_self_url();
		$smarty->assign("cur_url", $cur_url);
		$smarty->assign("script_name", "search");
		$smarty->display("search.dwt");
	}
	else if ($search_type == 1) {
		$keywords = htmlspecialchars(stripcslashes($_REQUEST["keywords"]));

		if ($display == "list") {
			$size = 10;
			$count = get_store_shop_count($keywords, $sort);
			$store_shop_list = get_store_shop_list(1, $keywords, $count, $size, $page, $sort, $order, $region_id, $area_id);
			$smarty->assign("store_shop_list", $store_shop_list["shop_list"]);
			$smarty->assign("pager", $store_shop_list["pager"]);
		}
		else {
			if (($display == "grid") || ($display == "text")) {
				if ($display == "text") {
					$size = 21;
				}
				else {
					$size = 20;
				}

				$shop_goods_list = get_store_shop_goods_list($keywords, $size, $page, $sort, $order, $region_id, $area_id);
				$smarty->assign("shop_goods_list", $shop_goods_list);
				$count = get_store_shop_goods_count($keywords, $sort);
			}
		}

		if (($display == "grid") || ($display == "text")) {
			$url_format = "search.php?category=0&amp;keywords=" . urlencode(stripslashes($_REQUEST["keywords"]));
			$url_format .= "&amp;sort=$sort";
			$url_format .= "&amp;order=$order&amp;page=";
			$pager["search"] = array("keywords" => stripslashes(trim($_REQUEST["keywords"])), "category" => 0, "store_search_cmt" => intval($_REQUEST["store_search_cmt"]), "sort" => $sort, "order" => $order);
			$pager = get_pager("search.php", $pager["search"], $count, $page, $size);
			$pager["display"] = $display;
			$smarty->assign("url_format", $url_format);
			$smarty->assign("count", $count);
			$smarty->assign("page", $page);
			$smarty->assign("pager", $pager);
		}

		$smarty->assign("size", $size);
		$smarty->assign("count", $count);
		$smarty->assign("display", $display);
		$smarty->assign("sort", $sort);
		$store_best_list = get_shop_goods_count_list(0, $region_id, $area_id, 1, "store_best", 1);
		$smarty->assign("store_best_list", $store_best_list);
		$cur_url = get_return_self_url();
		$smarty->assign("cur_url", $cur_url);
		$smarty->assign("script_name", "merchants_shop");
		$smarty->display("merchants_shop_list.dwt");
	}
}

?>
