<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function get_str_array1($order)
{
	$arr = array();

	foreach ($order as $key => $row ) {
		$row = explode("@", $row);
		$arr[$row[0]] = $row[1];
	}

	$arr = json_encode($arr);
	$arr = json_decode($arr);
	return $arr;
}

function get_str_array2($id)
{
	$arr = array();

	foreach ($id as $key => $row ) {
		$row = explode("-", $row);
		$arr[$row[0]] = $row[1];
	}

	return $arr;
}

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");
require (ROOT_PATH . "includes/cls_json.php");
require (ROOT_PATH . "includes/lib_order.php");

if (!empty($_SESSION["user_id"])) {
	$sess = $_SESSION["user_id"];
}
else {
	$sess = real_cart_mac_ip();
}

$json = new JSON();
$result = array("error" => 0, "message" => "", "content" => "");

if ($_REQUEST["act"] == "shipping_type") {
	include_once ("includes/lib_order.php");
	include_once ("includes/cls_json.php");
	$json = new JSON();
	$result = array("error" => 0, "massage" => "", "content" => "", "need_insure" => 0, "payment" => 1);
	$ru_id = (isset($_POST["ru_id"]) ? intval($_POST["ru_id"]) : 0);
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$shipping_type = (isset($_POST["type"]) ? intval($_POST["type"]) : 0);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		if (empty($cart_goods)) {
			$result["error"] = 1;
			$result["massage"] = $_LANG["no_goods_in_cart"];
		}
		else if (!check_consignee_info($consignee, $flow_type)) {
			$result["error"] = 2;
			$result["massage"] = $_LANG["au_buy_after_login"];
		}
	}
	else {
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();
		$_SESSION["flow_order"] = $order;
		$_SESSION["merchants_shipping"][$ru_id]["shipping_type"] = $shipping_type;
		$cart_goods_number = get_buy_cart_goods_number($flow_type, $_SESSION["cart_value"]);
		$smarty->assign("cart_goods_number", $cart_goods_number);
		$consignee["province_name"] = get_goods_region_name($consignee["province"]);
		$consignee["city_name"] = get_goods_region_name($consignee["city"]);
		$consignee["district_name"] = get_goods_region_name($consignee["district"]);
		$consignee["consignee_address"] = $consignee["province_name"] . $consignee["city_name"] . $consignee["district_name"] . $consignee["address"];
		$smarty->assign("consignee", $consignee);
		$cart_goods_list = cart_goods($flow_type, $_SESSION["cart_value"], 1);
		$smarty->assign("goods_list", $cart_goods_list);
		$total = order_fee($order, $cart_goods, $consignee, 0, $_SESSION["cart_value"], 0, $cart_goods_list);
		$smarty->assign("total", $total);

		if ($flow_type == CART_GROUP_BUY_GOODS) {
			$smarty->assign("is_group_buy", 1);
		}

		$result["content"] = $smarty->fetch("library/order_total.lbi");
	}

	echo $json->encode($result);
	exit();
}
else if ($_REQUEST["act"] == "edit_invoice") {
	include_once ("includes/cls_json.php");
	$result = array("error" => 0, "content" => "");
	$json = new JSON();
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		$result["error"] = 1;
		exit($json->encode($result));
	}
	else {
		$smarty->assign("config", $_CFG);
		if ((!isset($_CFG["can_invoice"]) || ($_CFG["can_invoice"] == "1")) && isset($_CFG["invoice_content"]) && (trim($_CFG["invoice_content"]) != "") && ($flow_type != CART_EXCHANGE_GOODS)) {
			$inv_content_list = explode("\n", str_replace("\r", "", $_CFG["invoice_content"]));
			$smarty->assign("inv_content_list", $inv_content_list);
			$inv_type_list = array();

			foreach ($_CFG["invoice_type"]["type"] as $key => $type ) {
				if (!empty($type)) {
					$inv_type_list[$type] = $type . " [" . floatval($_CFG["invoice_type"]["rate"][$key]) . "%]";
				}
			}

			$sql = "SELECT * FROM " . $ecs->table("order_invoice") . " WHERE user_id='{$_SESSION["user_id"]}' LIMIT 3";
			$order_invoice = $db->getAll($sql);
			$smarty->assign("order_invoice", $order_invoice);
			$smarty->assign("inv_type_list", $inv_type_list);
		}

		$result["content"] = $smarty->fetch("library/invoice.lbi");
		echo $json->encode($result);
		exit();
	}
}
else if ($_REQUEST["act"] == "update_invoicename") {
	include_once ("includes/cls_json.php");
	$result = array("error" => "", "msg" => "", "content" => "");
	$json = new JSON();
	$user_id = $_SESSION["user_id"];
	$inv_payee = (!empty($_POST["inv_payee"]) ? json_str_iconv(urldecode($_POST["inv_payee"])) : "");
	$invoice_id = (!empty($_POST["invoice_id"]) ? $_POST["invoice_id"] : 0);
	if (empty($user_id) || empty($inv_payee)) {
		$result["error"] = 1;
		$result["msg"] = "参数错误";
	}
	else if ($invoice_id == 0) {
		$sql = "INSERT INTO " . $ecs->table("order_invoice") . " SET user_id='$user_id', inv_payee='$inv_payee'";
		$db->query($sql);
		$result["invoice_id"] = $db->insert_id();
	}
	else {
		$sql = "UPDATE " . $ecs->table("order_invoice") . " SET inv_payee='$inv_payee' WHERE invoice_id='$invoice_id'";
		$db->query($sql);
		$result["invoice_id"] = $invoice_id;
	}

	echo $json->encode($result);
	exit();
}
else if ($_REQUEST["act"] == "del_invoicename") {
	include_once ("includes/cls_json.php");
	$result = array("error" => "", "msg" => "", "content" => "");
	$json = new JSON();
	$user_id = $_SESSION["user_id"];
	$invoice_id = (!empty($_POST["invoice_id"]) ? $_POST["invoice_id"] : 0);

	if (empty($user_id)) {
		$result["error"] = 1;
		$result["msg"] = "参数错误";
	}
	else {
		$sql = "DELETE FROM " . $ecs->table("order_invoice") . " WHERE invoice_id='$invoice_id'";
		$db->query($sql);
	}

	echo $json->encode($result);
	exit();
}
else if ($_REQUEST["act"] == "gotoInvoice") {
	include_once ("includes/cls_json.php");
	$result = array("error" => "", "content" => "");
	$json = new JSON();
	$invoice_id = (!empty($_POST["invoice_id"]) ? json_str_iconv(urldecode($_POST["invoice_id"])) : 0);
	$inv_content = (!empty($_POST["inv_content"]) ? json_str_iconv(urldecode($_POST["inv_content"])) : "");
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		$result["error"] = $_LANG["no_goods_in_cart"];
		exit($json->encode($result));
	}
	else {
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();

		if ($inv_content) {
			if (0 < $invoice_id) {
				$sql = "SELECT inv_payee FROM " . $ecs->table("order_invoice") . " WHERE invoice_id='$invoice_id'";
				$inv_payee = $db->getOne($sql);
			}
			else {
				$inv_payee = "个人";
			}

			$order["need_inv"] = 1;
			$order["inv_type"] = $_CFG["invoice_type"]["type"][0];
			$order["inv_payee"] = $inv_payee;
			$order["inv_content"] = $inv_content;
		}
		else {
			$order["need_inv"] = 0;
			$order["inv_type"] = "";
			$order["inv_payee"] = "";
			$order["inv_content"] = "";
		}

		$cart_goods_number = get_buy_cart_goods_number($flow_type, $_SESSION["cart_value"]);
		$smarty->assign("cart_goods_number", $cart_goods_number);
		$consignee["province_name"] = get_goods_region_name($consignee["province"]);
		$consignee["city_name"] = get_goods_region_name($consignee["city"]);
		$consignee["district_name"] = get_goods_region_name($consignee["district"]);
		$consignee["consignee_address"] = $consignee["province_name"] . $consignee["city_name"] . $consignee["district_name"] . $consignee["address"];
		$smarty->assign("consignee", $consignee);
		$cart_goods_list = cart_goods($flow_type, $_SESSION["cart_value"], 1);
		$smarty->assign("goods_list", $cart_goods_list);
		$total = order_fee($order, $cart_goods, $consignee, 0, $_SESSION["cart_value"], 0, $cart_goods_list);
		$smarty->assign("total", $total);

		if ($flow_type == CART_GROUP_BUY_GOODS) {
			$smarty->assign("is_group_buy", 1);
		}

		$result["inv_payee"] = $order["inv_payee"];
		$result["inv_content"] = $order["inv_content"];
		$result["content"] = $smarty->fetch("library/order_total.lbi");
	}
}
else if ($_REQUEST["act"] == "delete_cart_goods") {
	$cart_value = (isset($_REQUEST["cart_value"]) ? json_str_iconv($_REQUEST["cart_value"]) : "");

	if ($cart_value) {
		$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE rec_id IN($cart_value)";
		$GLOBALS["db"]->query($sql);
	}

	$result["cart_value"] = $cart_value;
}
else if ($_REQUEST["act"] == "drop_to_collect") {
	if (0 < $_SESSION["user_id"]) {
		$cart_value = (isset($_REQUEST["cart_value"]) ? json_str_iconv($_REQUEST["cart_value"]) : "");
		$goods_list = $db->getAll("SELECT goods_id, rec_id FROM " . $ecs->table("cart") . " WHERE rec_id IN($cart_value)");

		foreach ($goods_list as $row ) {
			$count = $db->getOne("SELECT goods_id FROM " . $ecs->table("collect_goods") . " WHERE user_id = '$sess' AND goods_id = '" . $row["goods_id"] . "'");

			if (empty($count)) {
				$time = gmtime();
				$sql = "INSERT INTO " . $GLOBALS["ecs"]->table("collect_goods") . " (user_id, goods_id, add_time)VALUES ('$sess', '" . $row["goods_id"] . "', '$time')";
				$db->query($sql);
			}

			flow_drop_cart_goods($row["rec_id"]);
		}
	}
}
else if ($_REQUEST["act"] == "user_order_gotopage") {
	require_once (ROOT_PATH . "languages/" . $_CFG["lang"] . "/user.php");
	include_once (ROOT_PATH . "includes/lib_transaction.php");
	$id = (!empty($_GET["id"]) ? json_str_iconv($_GET["id"]) : array());
	$page = (isset($_GET["page"]) && (0 < intval($_GET["page"])) ? intval($_GET["page"]) : 1);
	$type = 0;

	if ($id) {
		$id = explode("=", $id);
	}

	$where = "";
	$order = "";

	if (1 < count($id)) {
		$user_id = $id[0];
		$id = explode("|", $id[1]);
		$order = get_str_array1($id);
		$where = get_order_search_keyword($order);
		$record_count = $db->getAll("SELECT oi.order_id FROM " . $ecs->table("order_info") . " as oi left join " . $ecs->table("order_goods") . " as og on oi.order_id = og.order_id WHERE oi.user_id = '$user_id' and oi.is_delete = '$show_type'  and (select count(*) from " . $GLOBALS["ecs"]->table("order_info") . " as oi_2 where oi_2.main_order_id = oi.order_id) = 0 " . $where . " group by oi.order_id");
		$record_count = count($record_count);
	}
	else {
		$user_id = $id[0];
		$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table("order_info") . " as oi_1 WHERE oi_1.user_id = '$user_id' and oi_1.is_delete = '$type'  and (select count(*) from " . $GLOBALS["ecs"]->table("order_info") . " as oi_2 where oi_2.main_order_id = oi_1.order_id) = 0 ");
	}

	$order->action = "order_list";
	$orders = get_user_orders($user_id, $record_count, $page, $type, $where, $order);
	$smarty->assign("orders", $orders);
	$smarty->assign("action", $order->action);
	$result["content"] = $smarty->fetch("library/user_order_list.lbi");
}
else if ($_REQUEST["act"] == "store_shop_gotoPage") {
	$id = (!empty($_GET["id"]) ? json_str_iconv($_GET["id"]) : array());
	$page = (isset($_GET["page"]) && (0 < intval($_GET["page"])) ? intval($_GET["page"]) : 1);
	$type = (isset($_GET["type"]) && (0 < intval($_GET["type"])) ? intval($_GET["type"]) : 0);
	$libType = (isset($_GET["libType"]) && (0 < intval($_GET["libType"])) ? intval($_GET["libType"]) : 0);

	if ($libType == 1) {
		$size = 10;
	}
	else {
		$size = 15;
	}

	$sort = "";
	$order = "";
	$keywords = "";
	$region_id = "";
	$area_id = "";
	$store_province = "";
	$store_city = "";
	$store_district = "";

	if ($id) {
		$id = explode("|", $id);
		$id = get_str_array2($id);
		$sort = (isset($id["sort"]) ? $id["sort"] : "shop_id");
		$order = (isset($id["order"]) ? $id["order"] : "DESC");
		$keywords = (isset($id["keywords"]) ? $id["keywords"] : "");
		$region_id = (isset($id["region_id"]) ? $id["region_id"] : "");
		$area_id = (isset($id["area_id"]) ? $id["area_id"] : "");
		$store_province = (isset($id["store_province"]) ? $id["store_province"] : "");
		$store_city = (isset($id["store_city"]) ? $id["store_city"] : "");
		$store_district = (isset($id["store_district"]) ? $id["store_district"] : "");
		$store_user = (isset($id["store_user"]) ? $id["store_user"] : "");
	}

	$count = get_store_shop_count($keywords, $sort, $store_province, $store_city, $store_district);
	$store_shop_list = get_store_shop_list($libType, $keywords, $count, $size, $page, $sort, $order, $region_id, $area_id, $store_province, $store_city, $store_district, $store_user);
	$shop_list = $store_shop_list["shop_list"];
	$smarty->assign("store_shop_list", $shop_list);
	$smarty->assign("pager", $store_shop_list["pager"]);
	$smarty->assign("count", $count);
	$smarty->assign("size", $size);

	if ($libType == 1) {
		$result["content"] = $smarty->fetch("library/search_store_shop_list.lbi");
	}
	else {
		$result["content"] = $smarty->fetch("library/store_shop_list.lbi");
	}
}
else if ($_REQUEST["act"] == "getCategoryCallback") {
	$cat_id = (isset($_REQUEST["cat_id"]) ? intval($_REQUEST["cat_id"]) : 0);
	$cat_topic_file = "category_topic" . $cat_id;
	$category_topic = read_static_cache($cat_topic_file);

	if ($category_topic === false) {
		$category_topic = get_category_topic($cat_id);

		if ($category_topic) {
			write_static_cache($cat_topic_file, $category_topic);
		}
	}

	$smarty->assign("category_topic", $category_topic);
	$cat_file = "category_tree_child" . $cat_id;
	$child_tree = read_static_cache($cat_file);

	if ($child_tree === false) {
		$child_tree = get_category_parentchild_tree1($cat_id);
		write_static_cache($cat_file, $child_tree);
	}

	$smarty->assign("child_tree", $child_tree);
	$brands_file = "category_tree_brands" . $cat_id;
	$brands_ad = read_static_cache($brands_file);

	if ($brands_ad === false) {
		$brands_ad = get_category_brands_ad($cat_id);
		write_static_cache($brands_file, $brands_ad);
	}

	$smarty->assign("brands_ad", $brands_ad);
	$result["cat_id"] = $cat_id;
	$result["topic_content"] = $smarty->fetch("library/index_cat_topic.lbi");
	$result["cat_content"] = $smarty->fetch("library/index_cat_tree.lbi");
	$result["brands_ad_content"] = $smarty->fetch("library/index_cat_brand_ad.lbi");
}
else if ($_REQUEST["act"] == "goods_stock_exhausted") {
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$rec_number = (isset($_REQUEST["rec_number"]) ? htmlspecialchars($_REQUEST["rec_number"]) : "");
	$warehouse_id = (!empty($_REQUEST["warehouse_id"]) ? intval($_REQUEST["warehouse_id"]) : 0);
	$area_id = (!empty($_REQUEST["area_id"]) ? intval($_REQUEST["area_id"]) : 0);

	if (!empty($rec_number)) {
		$cart_value = get_sc_str_replace($_SESSION["cart_value"], $rec_number);
	}

	$cart_goods_list = cart_goods($flow_type, $_SESSION["cart_value"], 1, $warehouse_id, $area_id);
	$cart_goods_list_new = cart_by_favourable($cart_goods_list);
	$GLOBALS["smarty"]->assign("goods_list", $cart_goods_list_new);
	$GLOBALS["smarty"]->assign("cart_value", $cart_value);
	$result["cart_value"] = $cart_value;
	$result["content"] = $GLOBALS["smarty"]->fetch("library/goods_stock_exhausted.lbi");
}
else if ($_REQUEST["act"] == "goods_delivery_area") {
	include_once ("includes/lib_transaction.php");
	$_POST["area"] = strip_tags(urldecode($_POST["area"]));
	$_POST["area"] = json_str_iconv($_POST["area"]);
	$result = array("error" => 0, "message" => "", "content" => "");
	$json = new JSON();

	if (empty($_POST["area"])) {
		$result["error"] = 1;
		exit($json->encode($result));
	}

	$area = $json->decode($_POST["area"]);
	$province_id = $area->province_id;
	$city_id = $area->city_id;
	$district_id = $area->district_id;
	$goods_id = $area->goods_id;
	$user_id = $area->user_id;
	$region_id = $area->region_id;
	$area_id = $area->area_id;
	$merchant_id = $area->merchant_id;
	$province_list = get_warehouse_province();
	$city_list = get_region_city_county($province_id);
	$district_list = get_region_city_county($city_id);
	$warehouse_list = get_warehouse_list_goods();
	$warehouse_name = get_warehouse_name_id($region_id);
	$GLOBALS["smarty"]->assign("province_list", $province_list);
	$GLOBALS["smarty"]->assign("city_list", $city_list);
	$GLOBALS["smarty"]->assign("district_list", $district_list);
	$GLOBALS["smarty"]->assign("goods_id", $goods_id);
	$GLOBALS["smarty"]->assign("warehouse_list", $warehouse_list);
	$GLOBALS["smarty"]->assign("warehouse_name", $warehouse_name);
	$GLOBALS["smarty"]->assign("region_id", $region_id);
	$GLOBALS["smarty"]->assign("user_id", $user_id);
	$GLOBALS["smarty"]->assign("area_id", $area_id);
	$GLOBALS["smarty"]->assign("merchant_id", $merchant_id);
	$consignee_list = get_new_consignee_list($_SESSION["user_id"]);
	$GLOBALS["smarty"]->assign("consignee_list", $consignee_list);
	$address_id = $db->getOne("SELECT address_id FROM " . $ecs->table("users") . " WHERE user_id='" . $_SESSION["user_id"] . "'");
	$GLOBALS["smarty"]->assign("address_id", $address_id);
	$province_row = get_region_name($province_id);
	$city_row = get_region_name($city_id);
	$district_row = get_region_name($district_id);
	$GLOBALS["smarty"]->assign("province_row", $province_row);
	$GLOBALS["smarty"]->assign("city_row", $city_row);
	$GLOBALS["smarty"]->assign("district_row", $district_row);
	$GLOBALS["smarty"]->assign("show_warehouse", $GLOBALS["_CFG"]["show_warehouse"]);
	$result["content"] = $GLOBALS["smarty"]->fetch("library/goods_delivery_area.lbi");
	$result["warehouse_content"] = $GLOBALS["smarty"]->fetch("library/goods_warehouse.lbi");
}

echo $json->encode($result);

?>
