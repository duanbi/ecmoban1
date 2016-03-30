<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function get_article_info($article_id)
{
	$sql = "SELECT a.*, IFNULL(AVG(r.comment_rank), 0) AS comment_rank FROM " . $GLOBALS["ecs"]->table("article") . " AS a LEFT JOIN " . $GLOBALS["ecs"]->table("comment") . " AS r ON r.id_value = a.article_id AND comment_type = 1 WHERE a.is_open = 1 AND a.article_id = '$article_id' GROUP BY a.article_id";
	$row = $GLOBALS["db"]->getRow($sql);

	if ($row !== false) {
		$row["comment_rank"] = ceil($row["comment_rank"]);
		$row["add_time"] = local_date($GLOBALS["_CFG"]["date_format"], $row["add_time"]);
		if (empty($row["author"]) || ($row["author"] == "_SHOPHELP")) {
			$row["author"] = $GLOBALS["_CFG"]["shop_name"];
		}
	}

	return $row;
}

function article_related_goods($id)
{
	$sql = "SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, IFNULL(mp.user_price, g.shop_price * '{$_SESSION["discount"]}') AS shop_price, g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date FROM " . $GLOBALS["ecs"]->table("goods_article") . " ga LEFT JOIN " . $GLOBALS["ecs"]->table("goods") . " AS g ON g.goods_id = ga.goods_id LEFT JOIN " . $GLOBALS["ecs"]->table("member_price") . " AS mp ON mp.goods_id = g.goods_id AND mp.user_rank = '{$_SESSION["user_rank"]}' WHERE ga.article_id = '$id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0";
	$res = $GLOBALS["db"]->query($sql);
	$arr = array();

	while ($row = $GLOBALS["db"]->fetchRow($res)) {
		$arr[$row["goods_id"]]["goods_id"] = $row["goods_id"];
		$arr[$row["goods_id"]]["goods_name"] = $row["goods_name"];
		$arr[$row["goods_id"]]["short_name"] = (0 < $GLOBALS["_CFG"]["goods_name_length"] ? sub_str($row["goods_name"], $GLOBALS["_CFG"]["goods_name_length"]) : $row["goods_name"]);
		$arr[$row["goods_id"]]["goods_thumb"] = get_image_path($row["goods_id"], $row["goods_thumb"], true);
		$arr[$row["goods_id"]]["goods_img"] = get_image_path($row["goods_id"], $row["goods_img"]);
		$arr[$row["goods_id"]]["market_price"] = price_format($row["market_price"]);
		$arr[$row["goods_id"]]["shop_price"] = price_format($row["shop_price"]);
		$arr[$row["goods_id"]]["url"] = build_uri("goods", array("gid" => $row["goods_id"]), $row["goods_name"]);

		if (0 < $row["promote_price"]) {
			$arr[$row["goods_id"]]["promote_price"] = bargain_price($row["promote_price"], $row["promote_start_date"], $row["promote_end_date"]);
			$arr[$row["goods_id"]]["formated_promote_price"] = price_format($arr[$row["goods_id"]]["promote_price"]);
		}
		else {
			$arr[$row["goods_id"]]["promote_price"] = 0;
		}
	}

	return $arr;
}

function get_cat_id_art($article_id)
{
	$sql = "select ac.cat_id,ac.cat_type,ac.cat_name,ac.parent_id from " . $GLOBALS["ecs"]->table("article") . " as a left join " . $GLOBALS["ecs"]->table("article_cat") . " as ac on a.cat_id=ac.cat_id where a.article_id = '$article_id'";
	$cat_info = $GLOBALS["db"]->getRow($sql);
	return $cat_info;
}

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");

if ((DEBUG_MODE & 2) != 2) {
	$smarty->caching = true;
}

require (ROOT_PATH . "includes/lib_area.php");
$_REQUEST["id"] = (isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : 0);
$article_id = $_REQUEST["id"];
if (isset($_REQUEST["cat_id"]) && ($_REQUEST["cat_id"] < 0)) {
	$article_id = $db->getOne("SELECT article_id FROM " . $ecs->table("article") . " WHERE cat_id = '" . intval($_REQUEST["cat_id"]) . "' ");
}

$cache_id = sprintf("%X", crc32($_REQUEST["id"] . "-" . $_CFG["lang"]));

if (!$smarty->is_cached("article.dwt", $cache_id)) {
	$article = get_article_info($article_id);

	if (empty($article)) {
		ecs_header("Location: ./\n");
		exit();
	}

	if (!empty($article["link"]) && ($article["link"] != "http://") && ($article["link"] != "https://")) {
		ecs_header("location:{$article["link"]}\n");
		exit();
	}

	$smarty->assign("helps", get_shop_help());
	$smarty->assign("sys_categories", article_categories_tree(0, 2));
	$smarty->assign("custom_categories", article_categories_tree(0, 1));
	$categories_pro = get_category_tree_leve_one();
	$smarty->assign("categories_pro", $categories_pro);
	$smarty->assign("new_article", get_new_article(5));
	$smarty->assign("top_goods", get_top10());
	$smarty->assign("best_goods", get_recommend_goods("best"));
	$smarty->assign("new_goods", get_recommend_goods("new"));
	$smarty->assign("hot_goods", get_recommend_goods("hot"));
	$smarty->assign("promotion_goods", get_promote_goods());
	$smarty->assign("related_goods", article_related_goods($_REQUEST["id"]));
	$smarty->assign("id", $article_id);
	$smarty->assign("username", $_SESSION["user_name"]);
	$smarty->assign("email", $_SESSION["email"]);
	$smarty->assign("type", "1");
	$smarty->assign("promotion_info", get_promotion_info());
	$cat_info = get_cat_id_art($article_id);
	$smarty->assign("cat_info", $cat_info);
	if ((intval($_CFG["captcha"]) & CAPTCHA_COMMENT) && (0 < gd_version())) {
		$smarty->assign("enabled_captcha", 1);
		$smarty->assign("rand", mt_rand());
	}

	$smarty->assign("article", $article);
	$smarty->assign("keywords", htmlspecialchars($article["keywords"]));
	$smarty->assign("description", htmlspecialchars($article["description"]));
	$catlist = array();

	foreach (get_article_parent_cats($article["cat_id"]) as $k => $v ) {
		$catlist[] = $v["cat_id"];
	}

	assign_template("a", $catlist);
	$position = assign_ur_here($article["cat_id"], $article["title"]);
	$smarty->assign("page_title", $position["title"]);
	$smarty->assign("ur_here", $position["ur_here"]);
	$smarty->assign("comment_type", 1);
	$sql = "SELECT a.goods_id, g.goods_name FROM " . $ecs->table("goods_article") . " AS a, " . $ecs->table("goods") . " AS g WHERE a.goods_id = g.goods_id AND a.article_id = '{$_REQUEST["id"]}' ";
	$smarty->assign("goods_list", $db->getAll($sql));
	$next_article = $db->getRow("SELECT article_id, title FROM " . $ecs->table("article") . " WHERE article_id > $article_id AND cat_id={$article["cat_id"]} AND is_open=1 ORDER BY article_id ASC LIMIT 1");

	if (!empty($next_article)) {
		$next_article["url"] = build_uri("article", array("aid" => $next_article["article_id"]), $next_article["title"]);
		$smarty->assign("next_article", $next_article);
	}

	$prev_aid = $db->getOne("SELECT max(article_id) FROM " . $ecs->table("article") . " WHERE article_id < $article_id AND cat_id={$article["cat_id"]} AND is_open=1 ORDER BY article_id ASC");

	if (!empty($prev_aid)) {
		$prev_article = $db->getRow("SELECT article_id, title FROM " . $ecs->table("article") . " WHERE article_id = $prev_aid");
		$prev_article["url"] = build_uri("article", array("aid" => $prev_article["article_id"]), $prev_article["title"]);
		$smarty->assign("prev_article", $prev_article);
	}

	assign_dynamic("article");
}

if (isset($article) && (2 < $article["cat_id"])) {
	$smarty->display("article.dwt", $cache_id);
}
else {
	$smarty->display("article_pro.dwt", $cache_id);
}

?>
