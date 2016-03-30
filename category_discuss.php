<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");
require (ROOT_PATH . "/includes/lib_area.php");
$user_id = (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0);
$preg = "/<script[\s\S]*?<\/script>/i";
$id = (isset($_REQUEST["id"]) ? strtolower($_REQUEST["id"]) : 0);
$id = (!empty($id) ? preg_replace($preg, "", stripslashes($id)) : 0);
$goods_id = intval($id);
$cache_id = sprintf("%X", crc32($goods_id . "-" . $_SESSION["user_rank"] . "-" . $_CFG["lang"]));
$area_info = get_area_info($province_id);
$area_id = $area_info["region_id"];
$where = "regionId = '$province_id'";
$date = array("parent_id");
$region_id = get_table_date("region_warehouse", $where, $date, 2);
$history_goods = get_history_goods($goods_id, $region_id, $area_id);
$smarty->assign("history_goods", $history_goods);
$goodsInfo = get_goods_info($goods_id, $region_id, $area_id);
$goodsInfo["goods_price"] = price_format($goodsInfo["goods_price"]);
$smarty->assign("goodsInfo", $goodsInfo);
$mc_all = ments_count_all($goods_id);
$mc_one = ments_count_rank_num($goods_id, 1);
$mc_two = ments_count_rank_num($goods_id, 2);
$mc_three = ments_count_rank_num($goods_id, 3);
$mc_four = ments_count_rank_num($goods_id, 4);
$mc_five = ments_count_rank_num($goods_id, 5);
$comment_all = get_conments_stars($mc_all, $mc_one, $mc_two, $mc_three, $mc_four, $mc_five);
$smarty->assign("comment_all", $comment_all);

if (!$smarty->is_cached("category_discuss.dwt", $cache_id)) {
	$smarty->assign("goods_id", $goods_id);
	assign_template();
	$position = assign_ur_here();
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);
	$categories_pro = get_category_tree_leve_one();
	$smarty->assign("categories_pro", $categories_pro);
	$smarty->assign("keywords", htmlspecialchars($_CFG["shop_keywords"]));
	$smarty->assign("description", htmlspecialchars($_CFG["shop_desc"]));
	$smarty->assign("flash_theme", $_CFG["flash_theme"]);
	$smarty->assign("feed_url", $_CFG["rewrite"] == 1 ? "feed.xml" : "feed.php");
	$smarty->assign("helps", get_shop_help());
	$smarty->assign("top_goods", get_top10());
	if ((intval($_CFG["captcha"]) & CAPTCHA_REGISTER) && (0 < gd_version())) {
		$smarty->assign("enabled_captcha", 1);
		$smarty->assign("rand", mt_rand());
	}

	$smarty->assign("shop_notice", $_CFG["shop_notice"]);
}

$discuss_list = get_discuss_all_list($goods_id);
$smarty->assign("discuss_list", $discuss_list);
$all_count = get_discuss_type_count($goods_id);
$t_count = get_discuss_type_count($goods_id, 1);
$w_count = get_discuss_type_count($goods_id, 2);
$q_count = get_discuss_type_count($goods_id, 3);
$s_count = get_commentimg_count($goods_id);
$smarty->assign("all_count", $all_count);
$smarty->assign("t_count", $t_count);
$smarty->assign("w_count", $w_count);
$smarty->assign("q_count", $q_count);
$smarty->assign("s_count", $s_count);
$discuss_hot = get_discuss_all_list($goods_id, 0, 1, 10, 0, "dis_browse_num");
$smarty->assign("hot_list", $discuss_hot);
$smarty->assign("user_id", $user_id);
$smarty->display("category_discuss.dwt", $cache_id);

?>
