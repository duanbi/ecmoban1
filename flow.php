<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function flow_available_points($cart_value)
{
	if (!empty($_SESSION["user_id"])) {
		$c_sess = " c.user_id = '" . $_SESSION["user_id"] . "' ";
	}
	else {
		$c_sess = " c.session_id = '" . real_cart_mac_ip() . "' ";
	}

	$where = "";

	if (!empty($cart_value)) {
		$where = " AND c.rec_id " . db_create_in($cart_value);
	}

	$sql = "SELECT SUM(g.integral * c.goods_number) FROM " . $GLOBALS["ecs"]->table("cart") . " AS c, " . $GLOBALS["ecs"]->table("goods") . " AS g WHERE " . $c_sess . " AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 {$where}AND c.rec_type = '" . CART_GENERAL_GOODS . "'";
	$val = intval($GLOBALS["db"]->getOne($sql));
	return integral_of_value($val);
}

function flow_update_cart($arr)
{
	if (!empty($_SESSION["user_id"])) {
		$sess_id = " user_id = '" . $_SESSION["user_id"] . "' ";
		$a_sess = " a.user_id = '" . $_SESSION["user_id"] . "' ";
		$b_sess = " b.user_id = '" . $_SESSION["user_id"] . "' ";
		$c_sess = " c.user_id = '" . $_SESSION["user_id"] . "' ";
		$sess = "";
	}
	else {
		$sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
		$a_sess = " a.session_id = '" . real_cart_mac_ip() . "' ";
		$b_sess = " b.session_id = '" . real_cart_mac_ip() . "' ";
		$c_sess = " c.session_id = '" . real_cart_mac_ip() . "' ";
		$sess = real_cart_mac_ip();
	}

	foreach ($arr as $key => $val ) {
		$val = intval(make_semiangle($val));
		if (($val <= 0) || !is_numeric($key)) {
			continue;
		}

		$sql = "SELECT `goods_id`, `goods_attr_id`, `product_id`, `extension_code` FROM" . $GLOBALS["ecs"]->table("cart") . " WHERE rec_id='$key' AND " . $sess_id;
		$goods = $GLOBALS["db"]->getRow($sql);
		$sql = "SELECT g.goods_name, g.goods_number FROM " . $GLOBALS["ecs"]->table("goods") . " AS g, " . $GLOBALS["ecs"]->table("cart") . " AS c WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
		$row = $GLOBALS["db"]->getRow($sql);
		$xiangouInfo = get_purchasing_goods_info($goods["goods_id"]);

		if ($xiangouInfo["is_xiangou"] == 1) {
			$user_id = (!empty($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0);
			$start_date = $xiangouInfo["xiangou_start_date"];
			$end_date = $xiangouInfo["xiangou_end_date"];
			$orderGoods = get_for_purchasing_goods($start_date, $end_date, $goods["goods_id"], $user_id);
			$nowTime = gmtime();
			if (($start_date < $nowTime) && ($nowTime < $end_date)) {
				if ($xiangouInfo["xiangou_num"] <= $orderGoods["goods_number"]) {
					$max_num = $xiangouInfo["xiangou_num"] - $orderGoods["goods_number"];
					$result["message"] = "该" . $row["goods_name"] . "商品您已购买过，无法再购买";
					$sql = "UPDATE " . $GLOBALS["ecs"]->table("cart") . " SET goods_number = 0 WHERE rec_id='$key'";
					$GLOBALS["db"]->query($sql);
					show_message($result["message"], $_LANG["back_to_cart"], "flow.php");
					exit();
				}
				else if (0 < $xiangouInfo["xiangou_num"]) {
					if (($xiangouInfo["is_xiangou"] == 1) && ($xiangouInfo["xiangou_num"] < ($orderGoods["goods_number"] + $val))) {
						$max_num = $xiangouInfo["xiangou_num"] - $orderGoods["goods_number"];
						$result["message"] = "该" . $row["goods_name"] . "商品已经累计超过限购数量";
						$cart_Num = $xiangouInfo["xiangou_num"] - $orderGoods["goods_number"];
						$sql = "UPDATE " . $GLOBALS["ecs"]->table("cart") . " SET goods_number = '$cart_Num' WHERE rec_id='$key'";
						$GLOBALS["db"]->query($sql);
						show_message($result["message"], $_LANG["back_to_cart"], "flow.php");
						exit();
					}
				}
			}
		}

		if ((0 < intval($GLOBALS["_CFG"]["use_storage"])) && ($goods["extension_code"] != "package_buy")) {
			if ($row["goods_number"] < $val) {
				show_message(sprintf($GLOBALS["_LANG"]["stock_insufficiency"], $row["goods_name"], $row["goods_number"], $row["goods_number"]));
				exit();
			}

			$goods["product_id"] = trim($goods["product_id"]);

			if (!empty($goods["product_id"])) {
				$sql = "SELECT product_number FROM " . $GLOBALS["ecs"]->table("products") . " WHERE goods_id = '" . $goods["goods_id"] . "' AND product_id = '" . $goods["product_id"] . "' LIMIT 1";
				$product_number = $GLOBALS["db"]->getOne($sql);

				if ($product_number < $val) {
					show_message(sprintf($GLOBALS["_LANG"]["stock_insufficiency"], $row["goods_name"], $product_number, $product_number));
					exit();
				}
			}
		}
		else {
			if ((0 < intval($GLOBALS["_CFG"]["use_storage"])) && ($goods["extension_code"] == "package_buy")) {
				if (judge_package_stock($goods["goods_id"], $val)) {
					show_message($GLOBALS["_LANG"]["package_stock_insufficiency"]);
					exit();
				}
			}
		}

		$sql = "SELECT b.goods_number, b.rec_id\r\n                FROM " . $GLOBALS["ecs"]->table("cart") . " a, " . $GLOBALS["ecs"]->table("cart") . " b\r\n                WHERE a.rec_id = '$key'\r\n                AND " . $a_sess . "\r\n                AND a.extension_code <> 'package_buy'\r\n                AND b.parent_id = a.goods_id\r\n                AND " . $b_sess;
		$offers_accessories_res = $GLOBALS["db"]->query($sql);

		if (0 < $val) {
			$row_num = 1;

			while ($offers_accessories_row = $GLOBALS["db"]->fetchRow($offers_accessories_res)) {
				if ($val < $row_num) {
					$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE " . $sess_id . " AND rec_id = '" . $offers_accessories_row["rec_id"] . "' AND group_id='' LIMIT 1";
					$GLOBALS["db"]->query($sql);
				}

				$row_num++;
			}

			if ($goods["extension_code"] == "package_buy") {
				$sql = "UPDATE " . $GLOBALS["ecs"]->table("cart") . " SET goods_number = '$val' WHERE rec_id='$key' AND " . $sess_id . " AND group_id=''";
			}
			else {
				$attr_id = (empty($goods["goods_attr_id"]) ? array() : explode(",", $goods["goods_attr_id"]));
				$goods_price = get_final_price($goods["goods_id"], $val, true, $attr_id);
				$sql = "UPDATE " . $GLOBALS["ecs"]->table("cart") . " SET goods_number = '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND " . $sess_id . " AND group_id=''";
			}
		}
		else {
			while ($offers_accessories_row = $GLOBALS["db"]->fetchRow($offers_accessories_res)) {
				$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE " . $sess_id . " AND rec_id = '" . $offers_accessories_row["rec_id"] . "' AND group_id='' LIMIT 1";
				$GLOBALS["db"]->query($sql);
			}

			$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE rec_id='$key' AND " . $sess_id . " AND group_id=''";
		}

		$GLOBALS["db"]->query($sql);
	}

	$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE " . $sess_id . " AND is_gift <> 0";
	$GLOBALS["db"]->query($sql);
}

function flow_cart_stock($arr)
{
	if (!empty($_SESSION["user_id"])) {
		$sess_id = " user_id = '" . $_SESSION["user_id"] . "' ";
	}
	else {
		$sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
	}

	foreach ($arr as $key => $val ) {
		$val = intval(make_semiangle($val));
		if (($val <= 0) || !is_numeric($key)) {
			continue;
		}

		$sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code`, `warehouse_id` FROM" . $GLOBALS["ecs"]->table("cart") . " WHERE rec_id='$key' AND " . $sess_id;
		$goods = $GLOBALS["db"]->getRow($sql);
		$sql = "SELECT g.goods_name, g.goods_number, g.goods_id, c.product_id, g.model_attr FROM " . $GLOBALS["ecs"]->table("goods") . " AS g, " . $GLOBALS["ecs"]->table("cart") . " AS c WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
		$row = $GLOBALS["db"]->getRow($sql);
		$sql = "select IF(g.model_inventory < 1, g.goods_number, IF(g.model_inventory < 2, wg.region_number, wag.region_number)) AS goods_number  from " . $GLOBALS["ecs"]->table("goods") . " as g  left join " . $GLOBALS["ecs"]->table("warehouse_goods") . " as wg on g.goods_id = wg.goods_id left join " . $GLOBALS["ecs"]->table("warehouse_area_goods") . " as wag on g.goods_id = wag.goods_id where g.goods_id = '" . $row["goods_id"] . "'";
		$goods_number = $GLOBALS["db"]->getOne($sql);
		$row["goods_number"] = $goods_number;
		if ((0 < intval($GLOBALS["_CFG"]["use_storage"])) && ($goods["extension_code"] != "package_buy")) {
			$row["product_id"] = trim($row["product_id"]);

			if (!empty($row["product_id"])) {
				if ($row["model_attr"] == 1) {
					$table_products = "products_warehouse";
				}
				else if ($row["model_attr"] == 2) {
					$table_products = "products_area";
				}
				else {
					$table_products = "products";
				}

				$sql = "SELECT product_number FROM " . $GLOBALS["ecs"]->table($table_products) . " WHERE goods_id = '" . $row["goods_id"] . "' and product_id = '" . $row["product_id"] . "'";
				$product_number = $GLOBALS["db"]->getOne($sql);

				if ($product_number < $val) {
					show_message(sprintf($GLOBALS["_LANG"]["stock_insufficiency"], $row["goods_name"], $product_number, $product_number));
					exit();
				}
			}
			else if ($row["goods_number"] < $val) {
				show_message(sprintf($GLOBALS["_LANG"]["stock_insufficiency"], $row["goods_name"], $row["goods_number"], $row["goods_number"]));
				exit();
			}
		}
		else {
			if ((0 < intval($GLOBALS["_CFG"]["use_storage"])) && ($goods["extension_code"] == "package_buy")) {
				if (judge_package_stock($goods["goods_id"], $val)) {
					show_message($GLOBALS["_LANG"]["package_stock_insufficiency"]);
					exit();
				}
			}
		}
	}
}

function cmp_favourable($a, $b)
{
	if ($a["available"] == $b["available"]) {
		if ($a["sort_order"] == $b["sort_order"]) {
			return 0;
		}
		else {
			return $a["sort_order"] < $b["sort_order"] ? -1 : 1;
		}
	}
	else {
		return $a["available"] ? -1 : 1;
	}
}

function add_gift_to_cart($act_id, $id, $price)
{
	if (!empty($_SESSION["user_id"])) {
		$sess = "";
	}
	else {
		$sess = real_cart_mac_ip();
	}

	$sql = "INSERT INTO " . $GLOBALS["ecs"]->table("cart") . " (user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, goods_number, is_real, extension_code, parent_id, is_gift, rec_type, ru_id ) SELECT '{$_SESSION["user_id"]}', '" . $sess . "', goods_id, goods_sn, goods_name, market_price, '$price', 1, is_real, extension_code, 0, '$act_id', '" . CART_GENERAL_GOODS . "', user_id FROM " . $GLOBALS["ecs"]->table("goods") . " WHERE goods_id = '$id'";
	$GLOBALS["db"]->query($sql);
}

function add_favourable_to_cart($act_id, $act_name, $amount)
{
	if (!empty($_SESSION["user_id"])) {
		$sess = "";
	}
	else {
		$sess = real_cart_mac_ip();
	}

	$sql = "INSERT INTO " . $GLOBALS["ecs"]->table("cart") . "(user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) VALUES('{$_SESSION["user_id"]}', '" . $sess . "', 0, '', '$act_name', 0, '" . (-1 * $amount) . "', 1, 0, '', 0, '$act_id', '" . CART_GENERAL_GOODS . "')";
	$GLOBALS["db"]->query($sql);
}

function get_cart_value($flow_type = 0)
{
	if (!empty($_SESSION["user_id"])) {
		$c_sess = " c.user_id = '" . $_SESSION["user_id"] . "' ";
	}
	else {
		$c_sess = " c.session_id = '" . real_cart_mac_ip() . "' ";
	}

	$sql = "SELECT c.rec_id FROM " . $GLOBALS["ecs"]->table("cart") . " AS c LEFT JOIN " . $GLOBALS["ecs"]->table("goods") . " AS g ON c.goods_id = g.goods_id WHERE $where " . $c_sess . "AND rec_type = '$flow_type' order by c.rec_id asc";
	$goods_list = $GLOBALS["db"]->getAll($sql);
	$rec_id = "";

	if ($goods_list) {
		foreach ($goods_list as $key => $row ) {
			$rec_id .= $row["rec_id"] . ",";
		}

		$rec_id = substr($rec_id, 0, -1);
	}

	return $rec_id;
}

function cart_favourable_box($favourable_id)
{
	$fav_res = favourable_list($_SESSION["user_rank"], -1, $favourable_id);
	$favourable_activity = $fav_res[0];
	$cart_goods = get_cart_goods("", 1);
	$merchant_goods = $cart_goods["goods_list"];
	$favourable_box = array();

	foreach ($merchant_goods as $key => $row ) {
		$user_cart_goods = $row["goods_list"];

		if ($row["ru_id"] == $favourable_activity["user_id"]) {
			foreach ($user_cart_goods as $key1 => $row1 ) {
				$row1["original_price"] = $row1["goods_price"] * $row1["goods_number"];
				if (($favourable_activity["act_range"] == 0) && ($row1["extension_code"] != "package_buy")) {
					if ($row1["is_gift"] == FAR_ALL) {
						$favourable_box["act_id"] = $favourable_activity["act_id"];
						$favourable_box["act_name"] = $favourable_activity["act_name"];
						$favourable_box["act_type"] = $favourable_activity["act_type"];

						switch ($favourable_activity["act_type"]) {
						case 0:
							$favourable_box["act_type_txt"] = "满赠";
							$favourable_box["act_type_ext_format"] = intval($favourable_activity["act_type_ext"]);
							break;

						case 1:
							$favourable_box["act_type_txt"] = "满减";
							$favourable_box["act_type_ext_format"] = number_format($favourable_activity["act_type_ext"], 2);
							break;

						case 2:
							$favourable_box["act_type_txt"] = "折扣";
							$favourable_box["act_type_ext_format"] = floatval($favourable_activity["act_type_ext"] / 10);
							break;

						default:
							break;
						}

						$favourable_box["min_amount"] = $favourable_activity["min_amount"];
						$favourable_box["act_type_ext"] = intval($favourable_activity["act_type_ext"]);
						$favourable_box["cart_fav_amount"] = cart_favourable_amount($favourable_activity);
						$favourable_box["available"] = favourable_available($favourable_activity);
						$cart_favourable = cart_favourable();
						$favourable_box["cart_favourable_gift_num"] = (empty($cart_favourable[$favourable_activity["act_id"]]) ? 0 : intval($cart_favourable[$favourable_activity["act_id"]]));
						$favourable_box["favourable_used"] = favourable_used($favourable_activity, $cart_favourable);
						$favourable_box["left_gift_num"] = intval($favourable_activity["act_type_ext"]) - (empty($cart_favourable[$favourable_activity["act_id"]]) ? 0 : intval($cart_favourable[$favourable_activity["act_id"]]));

						if ($favourable_activity["gift"]) {
							$favourable_box["act_gift_list"] = $favourable_activity["gift"];
						}

						$favourable_box["act_goods_list"][$row1["rec_id"]] = $row1;
					}
					else {
						$favourable_box["act_cart_gift"][$row1["rec_id"]] = $row1;
					}

					continue;
				}

				if (($favourable_activity["act_range"] == FAR_CATEGORY) && ($row1["extension_code"] != "package_buy")) {
					$get_act_range_ext = get_act_range_ext($_SESSION["user_rank"], $row["ru_id"], 1);
					$id_list = array();

					foreach ($get_act_range_ext as $id ) {
						$id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0, false)));
					}

					$cat_id = $GLOBALS["db"]->getOne("SELECT cat_id FROM " . $GLOBALS["ecs"]->table("goods") . " WHERE goods_id = '{$row1["goods_id"]}' ");
					if ((in_array(trim($cat_id), $id_list) && ($row1["is_gift"] == 0)) || ($row1["is_gift"] == $favourable_activity["act_id"])) {
						$fav_act_range_ext = array();

						foreach (explode(",", $favourable_activity["act_range_ext"]) as $id ) {
							$fav_act_range_ext = array_merge($fav_act_range_ext, array_keys(cat_list(intval($id), 0, false)));
						}

						if (($row1["is_gift"] == 0) && in_array($cat_id, $fav_act_range_ext)) {
							$favourable_box["act_id"] = $favourable_activity["act_id"];
							$favourable_box["act_name"] = $favourable_activity["act_name"];
							$favourable_box["act_type"] = $favourable_activity["act_type"];

							switch ($favourable_activity["act_type"]) {
							case 0:
								$favourable_box["act_type_txt"] = "满赠";
								$favourable_box["act_type_ext_format"] = intval($favourable_activity["act_type_ext"]);
								break;

							case 1:
								$favourable_box["act_type_txt"] = "满减";
								$favourable_box["act_type_ext_format"] = number_format($favourable_activity["act_type_ext"], 2);
								break;

							case 2:
								$favourable_box["act_type_txt"] = "折扣";
								$favourable_box["act_type_ext_format"] = floatval($favourable_activity["act_type_ext"] / 10);
								break;

							default:
								break;
							}

							$favourable_box["min_amount"] = $favourable_activity["min_amount"];
							$favourable_box["act_type_ext"] = intval($favourable_activity["act_type_ext"]);
							$favourable_box["cart_fav_amount"] = cart_favourable_amount($favourable_activity);
							$favourable_box["available"] = favourable_available($favourable_activity);
							$cart_favourable = cart_favourable();
							$favourable_box["cart_favourable_gift_num"] = (empty($cart_favourable[$favourable_activity["act_id"]]) ? 0 : intval($cart_favourable[$favourable_activity["act_id"]]));
							$favourable_box["favourable_used"] = favourable_used($favourable_activity, $cart_favourable);
							$favourable_box["left_gift_num"] = intval($favourable_activity["act_type_ext"]) - (empty($cart_favourable[$favourable_activity["act_id"]]) ? 0 : intval($cart_favourable[$favourable_activity["act_id"]]));

							if ($favourable_activity["gift"]) {
								$favourable_box["act_gift_list"] = $favourable_activity["gift"];
							}

							$favourable_box["act_goods_list"][$row1["rec_id"]] = $row1;
						}

						if ($row1["is_gift"] == $favourable_activity["act_id"]) {
							$favourable_box["act_cart_gift"][$row1["rec_id"]] = $row1;
						}

						continue;
					}
				}

				if (($favourable_activity["act_range"] == FAR_BRAND) && ($row1["extension_code"] != "package_buy")) {
					$get_act_range_ext = get_act_range_ext($_SESSION["user_rank"], $row["ru_id"], 2);
					$brand_id = $GLOBALS["db"]->getOne("SELECT brand_id FROM " . $GLOBALS["ecs"]->table("goods") . " WHERE goods_id = '{$row1["goods_id"]}' ");
					if ((in_array(trim($brand_id), $get_act_range_ext) && ($row1["is_gift"] == 0)) || ($row1["is_gift"] == $favourable_activity["act_id"])) {
						$act_range_ext_str = "," . $favourable_activity["act_range_ext"] . ",";
						$brand_id_str = "," . $brand_id . ",";
						if (($row1["is_gift"] == 0) && strstr($act_range_ext_str, trim($brand_id_str))) {
							$favourable_box["act_id"] = $favourable_activity["act_id"];
							$favourable_box["act_name"] = $favourable_activity["act_name"];
							$favourable_box["act_type"] = $favourable_activity["act_type"];

							switch ($favourable_activity["act_type"]) {
							case 0:
								$favourable_box["act_type_txt"] = "满赠";
								$favourable_box["act_type_ext_format"] = intval($favourable_activity["act_type_ext"]);
								break;

							case 1:
								$favourable_box["act_type_txt"] = "满减";
								$favourable_box["act_type_ext_format"] = number_format($favourable_activity["act_type_ext"], 2);
								break;

							case 2:
								$favourable_box["act_type_txt"] = "折扣";
								$favourable_box["act_type_ext_format"] = floatval($favourable_activity["act_type_ext"] / 10);
								break;

							default:
								break;
							}

							$favourable_box["min_amount"] = $favourable_activity["min_amount"];
							$favourable_box["act_type_ext"] = intval($favourable_activity["act_type_ext"]);
							$favourable_box["cart_fav_amount"] = cart_favourable_amount($favourable_activity);
							$favourable_box["available"] = favourable_available($favourable_activity);
							$cart_favourable = cart_favourable();
							$favourable_box["cart_favourable_gift_num"] = (empty($cart_favourable[$favourable_activity["act_id"]]) ? 0 : intval($cart_favourable[$favourable_activity["act_id"]]));
							$favourable_box["favourable_used"] = favourable_used($favourable_activity, $cart_favourable);
							$favourable_box["left_gift_num"] = intval($favourable_activity["act_type_ext"]) - (empty($cart_favourable[$favourable_activity["act_id"]]) ? 0 : intval($cart_favourable[$favourable_activity["act_id"]]));

							if ($favourable_activity["gift"]) {
								$favourable_box["act_gift_list"] = $favourable_activity["gift"];
							}

							$favourable_box["act_goods_list"][$row1["rec_id"]] = $row1;
						}

						if ($row1["is_gift"] == $favourable_activity["act_id"]) {
							$favourable_box["act_cart_gift"][$row1["rec_id"]] = $row1;
						}

						continue;
					}
				}

				if (($favourable_activity["act_range"] == FAR_GOODS) && ($row1["extension_code"] != "package_buy")) {
					$get_act_range_ext = get_act_range_ext($_SESSION["user_rank"], $row["ru_id"], 3);
					if (in_array($row1["goods_id"], $get_act_range_ext) || ($row1["is_gift"] == $favourable_activity["act_id"])) {
						$act_range_ext_str = "," . $favourable_activity["act_range_ext"] . ",";
						$goods_id_str = "," . $row1["goods_id"] . ",";
						if (strstr($act_range_ext_str, trim($goods_id_str)) && ($row1["is_gift"] == 0)) {
							$favourable_box["act_id"] = $favourable_activity["act_id"];
							$favourable_box["act_name"] = $favourable_activity["act_name"];
							$favourable_box["act_type"] = $favourable_activity["act_type"];

							switch ($favourable_activity["act_type"]) {
							case 0:
								$favourable_box["act_type_txt"] = "满赠";
								$favourable_box["act_type_ext_format"] = intval($favourable_activity["act_type_ext"]);
								break;

							case 1:
								$favourable_box["act_type_txt"] = "满减";
								$favourable_box["act_type_ext_format"] = number_format($favourable_activity["act_type_ext"], 2);
								break;

							case 2:
								$favourable_box["act_type_txt"] = "折扣";
								$favourable_box["act_type_ext_format"] = floatval($favourable_activity["act_type_ext"] / 10);
								break;

							default:
								break;
							}

							$favourable_box["min_amount"] = $favourable_activity["min_amount"];
							$favourable_box["act_type_ext"] = intval($favourable_activity["act_type_ext"]);
							$favourable_box["cart_fav_amount"] = cart_favourable_amount($favourable_activity);
							$favourable_box["available"] = favourable_available($favourable_activity);
							$cart_favourable = cart_favourable();
							$favourable_box["cart_favourable_gift_num"] = (empty($cart_favourable[$favourable_activity["act_id"]]) ? 0 : intval($cart_favourable[$favourable_activity["act_id"]]));
							$favourable_box["favourable_used"] = favourable_used($favourable_box, $cart_favourable);
							$favourable_box["left_gift_num"] = intval($favourable_activity["act_type_ext"]) - (empty($cart_favourable[$favourable_activity["act_id"]]) ? 0 : intval($cart_favourable[$favourable_activity["act_id"]]));

							if ($favourable_activity["gift"]) {
								$favourable_box["act_gift_list"] = $favourable_activity["gift"];
							}

							$favourable_box["act_goods_list"][$row1["rec_id"]] = $row1;
						}

						if ($row1["is_gift"] == $favourable_activity["act_id"]) {
							$favourable_box["act_cart_gift"][$row1["rec_id"]] = $row1;
						}
					}
				}
				else {
					$favourable_box[$row1["rec_id"]] = $row1;
				}
			}
		}
	}

	return $favourable_box;
}

function get_regions_log($type = 0, $parent = 0)
{
	$sql = "SELECT region_id, region_name FROM " . $GLOBALS["ecs"]->table("region") . " WHERE region_type = '$type' AND parent_id = '$parent'";
	return $GLOBALS["db"]->GetAll($sql);
}

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");
require (ROOT_PATH . "includes/lib_area.php");
require (ROOT_PATH . "includes/lib_order.php");
require_once (ROOT_PATH . "languages/" . $_CFG["lang"] . "/user.php");
require_once (ROOT_PATH . "languages/" . $_CFG["lang"] . "/shopping_flow.php");
$area_info = get_area_info($province_id);
$area_id = $area_info["region_id"];
$where = "regionId = '$province_id'";
$date = array("parent_id");
$region_id = get_table_date("region_warehouse", $where, $date, 2);
if (isset($_COOKIE["region_id"]) && !empty($_COOKIE["region_id"])) {
	$region_id = $_COOKIE["region_id"];
}

if (!isset($_REQUEST["step"])) {
	$_REQUEST["step"] = "cart";
}

if (!empty($_SESSION["user_id"])) {
	$sess_id = " user_id = '" . $_SESSION["user_id"] . "' ";
	$a_sess = " a.user_id = '" . $_SESSION["user_id"] . "' ";
	$b_sess = " b.user_id = '" . $_SESSION["user_id"] . "' ";
	$c_sess = " c.user_id = '" . $_SESSION["user_id"] . "' ";
	$sess = "";
}
else {
	$sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
	$a_sess = " a.session_id = '" . real_cart_mac_ip() . "' ";
	$b_sess = " b.session_id = '" . real_cart_mac_ip() . "' ";
	$c_sess = " c.session_id = '" . real_cart_mac_ip() . "' ";
	$sess = real_cart_mac_ip();
}

assign_template();
assign_dynamic("flow");
$position = assign_ur_here(0, $_LANG["shopping_flow"]);
$smarty->assign("page_title", $position["title"]);
$smarty->assign("ur_here", $position["ur_here"]);
$smarty->assign("promotion_goods", get_promote_goods());
$smarty->assign("categories", get_categories_tree());
$smarty->assign("helps", get_shop_help());
$smarty->assign("lang", $_LANG);
$smarty->assign("show_marketprice", $_CFG["show_marketprice"]);
$smarty->assign("data_dir", DATA_DIR);
$smarty->assign("user_id", $_SESSION["user_id"]);

if ($_REQUEST["step"] == "add_to_cart") {
	include_once ("includes/cls_json.php");
	$_POST["goods"] = strip_tags(urldecode($_POST["goods"]));
	$_POST["goods"] = json_str_iconv($_POST["goods"]);
	if (!empty($_REQUEST["goods_id"]) && empty($_POST["goods"])) {
		if (!is_numeric($_REQUEST["goods_id"]) || (intval($_REQUEST["goods_id"]) <= 0)) {
			ecs_header("Location:./\n");
		}

		$goods_id = intval($_REQUEST["goods_id"]);
		exit();
	}

	$result = array("error" => 0, "message" => "", "content" => "", "goods_id" => "", "divId" => "", "confirm_type" => "", "number" => "");
	$json = new JSON();

	if (empty($_POST["goods"])) {
		$result["error"] = 1;
		exit($json->encode($result));
	}

	$goods = $json->decode($_POST["goods"]);
	$warehouse_id = intval($goods->warehouse_id);
	$area_id = intval($goods->area_id);
	$confirm_type = (isset($goods->confirm_type) ? $goods->confirm_type : 0);

	if ($GLOBALS["_CFG"]["open_area_goods"] == 1) {
		$leftJoin = "";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_goods") . " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_area_goods") . " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";
		$sql = "SELECT g.user_id, g.review_status, g.model_attr,  IF(g.model_price < 1, g.goods_number, IF(g.model_price < 2, wg.region_number, wag.region_number)) AS goods_number  FROM " . $GLOBALS["ecs"]->table("goods") . " as g " . $leftJoin . " WHERE g.goods_id = '" . $goods->goods_id . "'";
		$goodsInfo = $GLOBALS["db"]->getRow($sql);
		$area_list = get_goods_link_area_list($goods->goods_id, $goodsInfo["user_id"]);

		if ($area_list["goods_area"]) {
			if (!in_array($area_id, $area_list["goods_area"])) {
				$no_area = 2;
			}
		}
		else {
			$no_area = 2;
		}

		if ($goodsInfo["model_attr"] == 1) {
			$table_products = "products_warehouse";
			$type_files = " and warehouse_id = '$warehouse_id'";
		}
		else if ($goodsInfo["model_attr"] == 2) {
			$table_products = "products_area";
			$type_files = " and area_id = '$area_id'";
		}
		else {
			$table_products = "products";
			$type_files = "";
		}

		$sql = "SELECT * FROM " . $GLOBALS["ecs"]->table($table_products) . " WHERE goods_id = '" . $goods->goods_id . "'" . $type_files . " LIMIT 0, 1";
		$prod = $GLOBALS["db"]->getRow($sql);

		if (empty($prod)) {
			$prod = 1;
		}
		else {
			$prod = 0;
		}

		if ($no_area == 2) {
			$result["error"] = 1;
			$result["message"] = "该地区暂不支持配送";
			exit($json->encode($result));
		}
		else if ($goodsInfo["review_status"] <= 2) {
			$result["error"] = 1;
			$result["message"] = "该商品已下架";
			exit($json->encode($result));
		}
	}

	if (empty($goods->spec) && empty($goods->quick)) {
		$groupBy = " group by ga.goods_attr_id ";
		$leftJoin = "";
		$shop_price = "wap.attr_price, wa.attr_price, g.model_attr, ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("goods") . " as g on g.goods_id = ga.goods_id";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_attr") . " as wap on ga.goods_id = wap.goods_id and wap.warehouse_id = '$warehouse_id' and ga.goods_attr_id = wap.goods_attr_id ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_area_attr") . " as wa on ga.goods_id = wa.goods_id and wa.area_id = '$area_id' and ga.goods_attr_id = wa.goods_attr_id ";
		$sql = "SELECT a.attr_id, a.attr_name, a.attr_type, ga.goods_attr_id, ga.attr_value, IF(g.model_attr < 1, ga.attr_price, IF(g.model_attr < 2, wap.attr_price, wa.attr_price)) as attr_price FROM " . $GLOBALS["ecs"]->table("goods_attr") . " AS ga LEFT JOIN " . $GLOBALS["ecs"]->table("attribute") . " AS a ON a.attr_id = ga.attr_id " . $leftJoin . "WHERE a.attr_type != 0 AND ga.goods_id = '" . $goods->goods_id . "' " . $groupBy . "ORDER BY a.sort_order, IF(g.model_attr < 1, ga.attr_price, IF(g.model_attr < 2, wap.attr_price, wa.attr_price)), ga.goods_attr_id";
		$res = $GLOBALS["db"]->getAll($sql);

		if (!empty($res)) {
			$spe_arr = array();

			foreach ($res as $row ) {
				$spe_arr[$row["attr_id"]]["attr_type"] = $row["attr_type"];
				$spe_arr[$row["attr_id"]]["name"] = $row["attr_name"];
				$spe_arr[$row["attr_id"]]["attr_id"] = $row["attr_id"];
				$spe_arr[$row["attr_id"]]["values"][] = array("label" => $row["attr_value"], "price" => $row["attr_price"], "format_price" => price_format($row["attr_price"], false), "id" => $row["goods_attr_id"]);
			}

			$i = 0;
			$spe_array = array();

			foreach ($spe_arr as $row ) {
				$spe_array[] = $row;
			}

			if (!empty($goods->divId)) {
				$result["divId"] = $goods->divId;
			}

			if (!empty($goods->confirm_type)) {
				$result["confirm_type"] = $goods->confirm_type;
			}

			if (!empty($goods->number)) {
				$result["number"] = $goods->number;
			}

			$result["error"] = ERR_NEED_SELECT_ATTR;
			$result["goods_id"] = $goods->goods_id;
			$result["warehouse_id"] = $warehouse_id;
			$result["area_id"] = $area_id;
			$result["parent"] = $goods->parent;
			$smarty->assign("spe_array", $spe_array);
			$result["message"] = $smarty->fetch("library/goods_attr.lbi");
			exit($json->encode($result));
		}
	}

	if ($_CFG["one_step_buy"] == "1") {
		clear_cart();
	}

	if (!is_numeric($goods->number) || (intval($goods->number) <= 0)) {
		$result["error"] = 1;
		$result["message"] = $_LANG["invalid_number"];
	}
	else {
		$xiangouInfo = get_purchasing_goods_info($goods->goods_id);

		if ($xiangouInfo["is_xiangou"] == 1) {
			$user_id = (!empty($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0);
			$sql = "SELECT goods_number FROM " . $ecs->table("cart") . "WHERE goods_id = " . $goods->goods_id . " and " . $sess_id;
			$cartGoodsNumInfo = $db->getRow($sql);
			$start_date = $xiangouInfo["xiangou_start_date"];
			$end_date = $xiangouInfo["xiangou_end_date"];
			$orderGoods = get_for_purchasing_goods($start_date, $end_date, $goods->goods_id, $user_id);
			$nowTime = gmtime();
			if (($start_date < $nowTime) && ($nowTime < $end_date)) {
				if ($xiangouInfo["xiangou_num"] <= $orderGoods["goods_number"]) {
					$result["error"] = 1;
					$max_num = $xiangouInfo["xiangou_num"] - $orderGoods["goods_number"];
					$result["message"] = "该商品购买已达到限购条件,无法再购买";
					exit($json->encode($result));
				}
				else if (0 < $xiangouInfo["xiangou_num"]) {
					if ($xiangouInfo["xiangou_num"] < ($cartGoodsNumInfo["goods_number"] + $orderGoods["goods_number"] + $goods->number)) {
						$result["error"] = 1;
						$result["message"] = "该商品已经累计超过限购数量";
						exit($json->encode($result));
					}
				}
			}
		}

		if (addto_cart($goods->goods_id, $goods->number, $goods->spec, $goods->parent, $warehouse_id, $area_id)) {
			if (!empty($goods->divId)) {
				$result["divId"] = $goods->divId;
			}

			if (2 < $_CFG["cart_confirm"]) {
				$result["message"] = "";
			}
			else {
				$result["message"] = ($_CFG["cart_confirm"] == 1 ? $_LANG["addto_cart_success_1"] : $_LANG["addto_cart_success_2"]);
			}

			$result["goods_id"] = $goods->goods_id;
			$result["content"] = insert_cart_info();
			$result["one_step_buy"] = $_CFG["one_step_buy"];
		}
		else {
			$result["message"] = $err->last_message();
			$result["error"] = $err->error_no;
			$result["goods_id"] = stripslashes($goods->goods_id);

			if (is_array($goods->spec)) {
				$result["product_spec"] = implode(",", $goods->spec);
			}
			else {
				$result["product_spec"] = $goods->spec;
			}
		}
	}

	if (0 < $confirm_type) {
		$result["confirm_type"] = $confirm_type;
	}
	else {
		$result["confirm_type"] = (!empty($_CFG["cart_confirm"]) ? $_CFG["cart_confirm"] : 2);
	}

	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "add_to_cart_showDiv") {
	include_once ("includes/cls_json.php");
	$_POST["goods"] = strip_tags(urldecode($_POST["goods"]));
	$_POST["goods"] = json_str_iconv($_POST["goods"]);
	if (!empty($_REQUEST["goods_id"]) && empty($_POST["goods"])) {
		if (!is_numeric($_REQUEST["goods_id"]) || (intval($_REQUEST["goods_id"]) <= 0)) {
			ecs_header("Location:./\n");
		}

		$goods_id = intval($_REQUEST["goods_id"]);
		exit();
	}

	$result = array("error" => 0, "message" => "", "content" => "", "goods_id" => "", "goods_number" => "", "subtotal" => "", "script_name" => "", "goods_recommend" => "");
	$json = new JSON();

	if (empty($_POST["goods"])) {
		$result["error"] = 1;
		exit($json->encode($result));
	}

	$goods = $json->decode($_POST["goods"]);

	if (!empty($goods->script_name)) {
		$result["script_name"] = $goods->script_name;
	}
	else {
		$result["script_name"] = 0;
	}

	if (!empty($goods->goods_recommend)) {
		$result["goods_recommend"] = $goods->goods_recommend;
	}
	else {
		$result["goods_recommend"] = "";
	}

	if (empty($goods->spec) && empty($goods->quick)) {
		$groupBy = " group by ga.goods_attr_id ";
		$leftJoin = "";
		$shop_price = "wap.attr_price, wa.attr_price, g.model_attr, ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("goods") . " as g on g.goods_id = ga.goods_id";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_attr") . " as wap on ga.goods_id = wap.goods_id and wap.warehouse_id = '" . $goods->warehouse_id . "' and ga.goods_attr_id = wap.goods_attr_id ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_area_attr") . " as wa on ga.goods_id = wa.goods_id and wa.area_id = '" . $goods->area_id . "' and ga.goods_attr_id = wa.goods_attr_id ";
		$sql = "SELECT a.attr_id, a.attr_name, a.attr_type, ga.goods_attr_id, ga.attr_value, IF(g.model_attr < 1, ga.attr_price, IF(g.model_attr < 2, wap.attr_price, wa.attr_price)) as attr_price FROM " . $GLOBALS["ecs"]->table("goods_attr") . " AS ga LEFT JOIN " . $GLOBALS["ecs"]->table("attribute") . " AS a ON a.attr_id = ga.attr_id " . $leftJoin . "WHERE a.attr_type != 0 AND ga.goods_id = '" . $goods->goods_id . "' " . $groupBy . "ORDER BY a.sort_order, IF(g.model_attr < 1, ga.attr_price, IF(g.model_attr < 2, wap.attr_price, wa.attr_price)), ga.goods_attr_id";
		$res = $GLOBALS["db"]->getAll($sql);

		if (!empty($res)) {
			$spe_arr = array();

			foreach ($res as $row ) {
				$spe_arr[$row["attr_id"]]["attr_type"] = $row["attr_type"];
				$spe_arr[$row["attr_id"]]["name"] = $row["attr_name"];
				$spe_arr[$row["attr_id"]]["attr_id"] = $row["attr_id"];
				$spe_arr[$row["attr_id"]]["values"][] = array("label" => $row["attr_value"], "price" => $row["attr_price"], "format_price" => price_format($row["attr_price"], false), "id" => $row["goods_attr_id"]);
			}

			$i = 0;
			$spe_array = array();

			foreach ($spe_arr as $row ) {
				$spe_array[] = $row;
			}

			$result["error"] = ERR_NEED_SELECT_ATTR;
			$result["goods_id"] = $goods->goods_id;
			$result["parent"] = $goods->parent;
			$result["message"] = $spe_array;

			if (!empty($goods->script_name)) {
				$result["script_name"] = $goods->script_name;
			}
			else {
				$result["script_name"] = 0;
			}

			exit($json->encode($result));
		}
	}

	if ($_CFG["one_step_buy"] == "1") {
		clear_cart();
	}

	if (!is_numeric($goods->number) || (intval($goods->number) <= 0)) {
		$result["error"] = 1;
		$result["message"] = $_LANG["invalid_number"];
	}
	else {
		$xiangouInfo = get_purchasing_goods_info($goods->goods_id);

		if ($xiangouInfo["is_xiangou"] == 1) {
			$user_id = (!empty($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0);
			$sql = "SELECT goods_number FROM " . $ecs->table("cart") . "WHERE goods_id = " . $goods->goods_id . " and " . $sess_id;
			$cartGoodsNumInfo = $db->getRow($sql);
			$start_date = $xiangouInfo["xiangou_start_date"];
			$end_date = $xiangouInfo["xiangou_end_date"];
			$orderGoods = get_for_purchasing_goods($start_date, $end_date, $goods->goods_id, $user_id);
			$nowTime = gmtime();
			if (($start_date < $nowTime) && ($nowTime < $end_date)) {
				if ($xiangouInfo["xiangou_num"] <= $orderGoods["goods_number"]) {
					$result["error"] = 1;
					$max_num = $xiangouInfo["xiangou_num"] - $orderGoods["goods_number"];
					$result["message"] = "该商品购买已达到限购条件,无法再购买";
					$result["show_info"] = "";
					exit($json->encode($result));
				}
				else if (0 < $xiangouInfo["xiangou_num"]) {
					if ($xiangouInfo["xiangou_num"] < ($cartGoodsNumInfo["goods_number"] + $orderGoods["goods_number"] + $goods->number)) {
						$result["error"] = 1;
						$result["message"] = "该商品已经累计超过限购数量";
						$result["show_info"] = "";
						exit($json->encode($result));
					}
				}
			}
		}

		if (addto_cart($goods->goods_id, $goods->number, $goods->spec, $goods->parent, $goods->warehouse_id, $goods->area_id)) {
			if (2 < $_CFG["cart_confirm"]) {
				$result["message"] = "";
			}
			else {
				$result["message"] = ($_CFG["cart_confirm"] == 1 ? $_LANG["addto_cart_success_1"] : $_LANG["addto_cart_success_2"]);
			}

			$result["content"] = insert_cart_info();
			$result["one_step_buy"] = $_CFG["one_step_buy"];
		}
		else {
			$result["message"] = $err->last_message();
			$result["error"] = $err->error_no;
			$result["goods_id"] = stripslashes($goods->goods_id);

			if (is_array($goods->spec)) {
				$result["product_spec"] = implode(",", $goods->spec);
			}
			else {
				$result["product_spec"] = $goods->spec;
			}
		}
	}

	$result["confirm_type"] = (!empty($_CFG["cart_confirm"]) ? $_CFG["cart_confirm"] : 2);
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$result["goods_id"] = $goods->goods_id;
	$cart_goods = get_cart_goods();
	$result["goods_number"] = 0;

	foreach ($cart_goods["goods_list"] as $val ) {
		$result["goods_number"] += $val["goods_number"];
	}

	$result["show_info"] = insert_show_div_info($result["goods_number"], $result["script_name"], $result["goods_id"], $result["goods_recommend"], $cart_goods["total"]["goods_amount"], $cart_goods["total"]["real_goods_count"]);
	$result["cart_num"] = $result["goods_number"];
	$cart_info = array("goods_list" => $cart_goods["goods_list"], "number" => $result["goods_number"], "amount" => $cart_goods["total"]["goods_amount"]);
	$GLOBALS["smarty"]->assign("cart_info", $cart_info);
	$result["cart_content"] = $GLOBALS["smarty"]->fetch("library/cart_menu_info.lbi");
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "add_to_cart_combo") {
	include_once ("includes/cls_json.php");
	$_POST["goods"] = strip_tags(urldecode($_POST["goods"]));
	$_POST["goods"] = json_str_iconv($_POST["goods"]);
	if (!empty($_REQUEST["goods_id"]) && empty($_POST["goods"])) {
		if (!is_numeric($_REQUEST["goods_id"]) || (intval($_REQUEST["goods_id"]) <= 0)) {
			ecs_header("Location:./\n");
		}

		$goods_id = intval($_REQUEST["goods_id"]);
		exit();
	}

	$result = array("error" => 0, "message" => "", "content" => "", "goods_id" => "");
	$json = new JSON();

	if (empty($_POST["goods"])) {
		$result["error"] = 1;
		exit($json->encode($result));
	}

	$goods = $json->decode($_POST["goods"]);

	if ($_CFG["one_step_buy"] == "1") {
		clear_cart();
	}

	if (!is_numeric($goods->number) || (intval($goods->number) <= 0)) {
		$result["error"] = 1;
		$result["message"] = $_LANG["invalid_number"];
	}
	else {
		$xiangouInfo = get_purchasing_goods_info($goods->goods_id);

		if ($xiangouInfo["is_xiangou"] == 1) {
			$user_id = (!empty($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0);
			$sql = "SELECT goods_number FROM " . $ecs->table("cart") . "WHERE goods_id = " . $goods->goods_id . " and " . $sess_id;
			$cartGoodsNumInfo = $db->getRow($sql);
			$start_date = $xiangouInfo["xiangou_start_date"];
			$end_date = $xiangouInfo["xiangou_end_date"];
			$orderGoods = get_for_purchasing_goods($start_date, $end_date, $goods->goods_id, $user_id);
			$nowTime = gmtime();
			if (($start_date < $nowTime) && ($nowTime < $end_date)) {
				if ($xiangouInfo["xiangou_num"] <= $orderGoods["goods_number"]) {
					$result["error"] = 1;
					$max_num = $xiangouInfo["xiangou_num"] - $orderGoods["goods_number"];
					$result["message"] = "该商品购买已达到限购条件,无法再购买";
					exit($json->encode($result));
				}
				else if (0 < $xiangouInfo["xiangou_num"]) {
					if ($xiangouInfo["xiangou_num"] < ($cartGoodsNumInfo["goods_number"] + $orderGoods["goods_number"] + $goods->number)) {
						$result["error"] = 1;
						$result["message"] = "该商品已经累计超过限购数量";
						exit($json->encode($result));
					}
				}
			}
		}

		if (addto_cart_combo($goods->goods_id, $goods->number, $goods->spec, $goods->parent, $goods->group, $goods->warehouse_id, $goods->area_id, $goods->goods_attr)) {
			if (2 < $_CFG["cart_confirm"]) {
				$result["message"] = "";
			}
			else {
				$result["message"] = ($_CFG["cart_confirm"] == 1 ? $_LANG["addto_cart_success_1"] : $_LANG["addto_cart_success_2"]);
			}

			$result["group"] = $goods->group;
			$result["goods_id"] = stripslashes($goods->goods_id);
			$result["content"] = "";
			$result["one_step_buy"] = $_CFG["one_step_buy"];
			$warehouse_area["warehouse_id"] = $goods->warehouse_id;
			$warehouse_area["area_id"] = $goods->area_id;
			$combo_goods_info = get_combo_goods_info($goods->goods_id, $goods->number, $goods->spec, $goods->parent, $warehouse_area);
			$result["fittings_price"] = $combo_goods_info["fittings_price"];
			$result["spec_price"] = $combo_goods_info["spec_price"];
			$result["goods_price"] = $combo_goods_info["goods_price"];
			$result["stock"] = $combo_goods_info["stock"];
			$result["parent"] = $goods->parent;
		}
		else {
			$result["message"] = $err->last_message();
			$result["error"] = $err->error_no;
			$result["group"] = $goods->group;
			$result["goods_id"] = stripslashes($goods->goods_id);

			if (is_array($goods->spec)) {
				$result["product_spec"] = implode(",", $goods->spec);
			}
			else {
				$result["product_spec"] = $goods->spec;
			}
		}
	}

	$result["warehouse_id"] = $goods->warehouse_id;
	$result["area_id"] = $goods->area_id;
	$result["goods_attr"] = $goods->goods_attr;
	$result["goods_group"] = str_replace("_" . $goods->parent, "", $goods->group);
	$combo_goods = get_cart_combo_goods_list($goods->goods_id, $goods->parent, $goods->group);
	$result["combo_amount"] = $combo_goods["combo_amount"];
	$result["combo_number"] = $combo_goods["combo_number"];
	$result["add_group"] = $goods->add_group;
	$parent_id = $goods->parent;
	$warehouse_id = $goods->warehouse_id;
	$area_id = $goods->area_id;
	$rev = $goods->group;
	$fitt_goods = (isset($goods->fitt_goods) ? $goods->fitt_goods : array());

	if (!in_array($goods->goods_id, $fitt_goods)) {
		array_unshift($fitt_goods, $goods->goods_id);
	}

	$goods_info = get_goods_fittings_info($parent_id, $warehouse_id, $area_id, $rev);
	$fittings = get_goods_fittings(array($parent_id), $warehouse_id, $area_id, $rev, 1);
	$fittings = array_merge($goods_info, $fittings);
	$fittings = array_values($fittings);
	$fittings_interval = get_choose_goods_combo_cart($fittings);

	if ($fittings_interval["return_attr"] < 1) {
		$result["fittings_minMax"] = price_format($fittings_interval["all_price_ori"]);
		$result["market_minMax"] = price_format($fittings_interval["all_market_price"]);
		$result["save_minMaxPrice"] = price_format($fittings_interval["save_price_amount"]);
	}
	else {
		$result["fittings_minMax"] = price_format($fittings_interval["fittings_min"]) . "-" . number_format($fittings_interval["fittings_max"], 2, ".", "");
		$result["market_minMax"] = price_format($fittings_interval["market_min"]) . "-" . number_format($fittings_interval["market_max"], 2, ".", "");

		if ($fittings_interval["save_minPrice"] == $fittings_interval["save_maxPrice"]) {
			$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]);
		}
		else {
			$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]) . "-" . number_format($fittings_interval["save_maxPrice"], 2, ".", "");
		}
	}

	$goodsGroup = explode("_", $goods->group);
	$result["groupId"] = $goodsGroup[2];
	$result["fitt_goods"] = $fitt_goods;
	$result["confirm_type"] = (!empty($_CFG["cart_confirm"]) ? $_CFG["cart_confirm"] : 2);
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "del_in_cart_combo") {
	include_once ("includes/cls_json.php");
	$_POST["goods"] = strip_tags(urldecode($_POST["goods"]));
	$_POST["goods"] = json_str_iconv($_POST["goods"]);
	if (!empty($_REQUEST["goods_id"]) && empty($_POST["goods"])) {
		if (!is_numeric($_REQUEST["goods_id"]) || (intval($_REQUEST["goods_id"]) <= 0)) {
			ecs_header("Location:./\n");
		}

		$goods_id = intval($_REQUEST["goods_id"]);
		exit();
	}

	$result = array("error" => 0, "message" => "");
	$json = new JSON();

	if (empty($_POST["goods"])) {
		$result["error"] = 1;
		exit($json->encode($result));
	}

	$goods = $json->decode($_POST["goods"]);
	$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart_combo") . " WHERE " . $sess_id . " AND goods_id = '" . $goods->goods_id . "' AND group_id = '" . $goods->group . "'";
	$GLOBALS["db"]->query($sql);
	$sql = "select count(*) from " . $GLOBALS["ecs"]->table("cart_combo") . " where " . $sess_id . " and parent_id = '" . $goods->parent . "' AND group_id = '" . $goods->group . "'";
	$rec_count = $GLOBALS["db"]->getOne($sql);

	if ($rec_count < 1) {
		$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart_combo") . " WHERE " . $sess_id . " AND goods_id = '" . $goods->parent . "' AND parent_id = 0  AND group_id = '" . $goods->group . "'";
		$GLOBALS["db"]->query($sql);
	}

	$result["error"] = 0;
	$result["group"] = substr($goods->group, 0, strrpos($goods->group, "_"));
	$result["parent"] = $goods->parent;
	$combo_goods = get_cart_combo_goods_list($goods->goods_id, $goods->parent, $goods->group);

	if (empty($combo_goods["shop_price"])) {
		$shop_price = get_final_price($goods->parent, 1, true, $goods->goods_attr, $goods->warehouse_id, $goods->area_id);
		$combo_goods["combo_amount"] = price_format($shop_price, false);
	}

	$result["combo_amount"] = $combo_goods["combo_amount"];
	$result["combo_number"] = $combo_goods["combo_number"];
	$parent_id = $goods->parent;
	$warehouse_id = $goods->warehouse_id;
	$area_id = $goods->area_id;
	$rev = $goods->group;

	if (0 < $combo_goods["combo_number"]) {
		$goods_info = get_goods_fittings_info($parent_id, $warehouse_id, $area_id, $rev);
		$fittings = get_goods_fittings(array($parent_id), $warehouse_id, $area_id, $rev, 1);
	}
	else {
		$goods_info = get_goods_fittings_info($parent_id, $warehouse_id, $area_id, "", 1);
		$fittings = get_goods_fittings(array($parent_id), $warehouse_id, $area_id);
	}

	$fittings = array_merge($goods_info, $fittings);
	$fittings = array_values($fittings);
	$fittings_interval = get_choose_goods_combo_cart($fittings);

	if (0 < $combo_goods["combo_number"]) {
		if ($fittings_interval["return_attr"] < 1) {
			$result["fittings_minMax"] = price_format($fittings_interval["all_price_ori"]);
			$result["market_minMax"] = price_format($fittings_interval["all_market_price"]);
			$result["save_minMaxPrice"] = price_format($fittings_interval["save_price_amount"]);
		}
		else {
			$result["fittings_minMax"] = price_format($fittings_interval["fittings_min"]) . "-" . number_format($fittings_interval["fittings_max"], 2, ".", "");
			$result["market_minMax"] = price_format($fittings_interval["market_min"]) . "-" . number_format($fittings_interval["market_max"], 2, ".", "");

			if ($fittings_interval["save_minPrice"] == $fittings_interval["save_maxPrice"]) {
				$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]);
			}
			else {
				$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]) . "-" . number_format($fittings_interval["save_maxPrice"], 2, ".", "");
			}
		}
	}
	else {
		$result["fittings_minMax"] = price_format($fittings_interval["fittings_min"]) . "-" . number_format($fittings_interval["fittings_max"], 2, ".", "");
		$result["market_minMax"] = price_format($fittings_interval["market_min"]) . "-" . number_format($fittings_interval["market_max"], 2, ".", "");

		if ($fittings_interval["save_minPrice"] == $fittings_interval["save_maxPrice"]) {
			$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]);
		}
		else {
			$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]) . "-" . number_format($fittings_interval["save_maxPrice"], 2, ".", "");
		}
	}

	$goodsGroup = explode("_", $goods->group);
	$result["groupId"] = $goodsGroup[2];
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "add_to_cart_group") {
	include_once ("includes/cls_json.php");
	$_POST["goods"] = strip_tags(urldecode($_POST["goods"]));
	$_POST["goods"] = json_str_iconv($_POST["goods"]);
	$result = array("error" => 0, "message" => "");
	$json = new JSON();

	if (empty($_POST["goods"])) {
		$result["error"] = 1;
		$result["message"] = "系统无法接收不完整的数据";
		exit($json->encode($result));
	}

	$goods = $json->decode($_POST["goods"]);
	$group = $goods->group . "_" . $goods->goods_id;
	$sql = "SELECT rec_id FROM " . $GLOBALS["ecs"]->table("cart_combo") . " WHERE " . $sess_id . " AND group_id = '" . $group . "' ORDER BY parent_id limit 1";
	$res = $GLOBALS["db"]->query($sql);

	if ($res) {
		$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE " . $sess_id . " AND group_id = '" . $group . "'";
		$GLOBALS["db"]->query($sql);
		$sql = "INSERT INTO " . $GLOBALS["ecs"]->table("cart") . "(user_id, session_id, goods_id, goods_sn, product_id, group_id, goods_name, market_price, goods_price, goods_number, goods_attr, is_real, extension_code, parent_id, rec_type, is_gift, is_shipping, can_handsel, model_attr, goods_attr_id, warehouse_id, area_id, add_time) SELECT user_id, session_id, goods_id, goods_sn, product_id, group_id, goods_name, market_price, goods_price, goods_number, goods_attr, is_real, extension_code, parent_id, rec_type, is_gift, is_shipping, can_handsel, model_attr, goods_attr_id, warehouse_id, area_id, add_time FROM " . $GLOBALS["ecs"]->table("cart_combo") . " WHERE " . $sess_id . " AND group_id = '" . $group . "'";
		$GLOBALS["db"]->query($sql);
		$sql = "UPDATE " . $GLOBALS["ecs"]->table("cart") . " set goods_number = '$goods->number' WHERE " . $sess_id . " AND group_id = '" . $group . "'";
		$GLOBALS["db"]->query($sql);
		$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart_combo") . " WHERE " . $sess_id . " AND group_id = '" . $group . "'";
		$GLOBALS["db"]->query($sql);
	}
	else {
		$result["error"] = 1;
		$result["message"] = "暂无数据可提交，请重新选择";
		exit($json->encode($result));
	}

	$result["error"] = 0;
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "add_cart_combo_list") {
	include_once ("includes/cls_json.php");
	$_POST["group"] = strip_tags(urldecode($_POST["group"]));
	$_POST["group"] = json_str_iconv($_POST["group"]);
	$result = array("error" => 0, "message" => "", "content" => "", "goods_id" => "");
	$json = new JSON();

	if (empty($_POST["group"])) {
		$result["error"] = 1;
		exit($json->encode($result));
	}

	$group = $json->decode($_POST["group"]);
	$number = $group->number;
	$goods = explode("_", $group->rev);
	$goodSEqual = (isset($group->fitt_goods) ? $group->fitt_goods : array());
	$goods_id = $goods[3];
	$warehouse_id = $goods[4];
	$area_id = $goods[5];
	$rev = $goods[0] . "_" . $goods[1] . "_" . $goods[2] . "_" . $goods[3];
	$group = $goods[0] . "_" . $goods[1] . "_" . $goods[2];
	$result["groupId"] = $goods[2];
	$result["number"] = $number;

	if (!empty($number)) {
		$smarty->assign("number", $number);
	}

	$smarty->assign("group", $group);
	$smarty->assign("warehouse_id", $warehouse_id);
	$smarty->assign("area_id", $area_id);
	$smarty->assign("goods_id", $goods_id);
	$list_select = get_combo_goods_list_select(0, $goods[3], $rev);
	$combo_goods = get_cart_combo_goods_list(0, $goods[3], $rev);
	$result["group_rev"] = $goods[0] . "_" . $goods[1] . "_" . $goods[2] . "_" . $goods[3] . "_" . $goods[4] . "_" . $goods[5];
	$smarty->assign("group_rev", $result["group_rev"]);
	$fittings_top = get_goods_fittings(array($goods_id), $warehouse_id, $area_id, $goods[2], 2);
	$fittings_top = array_values($fittings_top);
	$smarty->assign("fittings_top", $fittings_top);
	$smarty->assign("list_select", $list_select);

	if ($goodSEqual) {
		$goods_info = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, $rev);
		$fittings = get_goods_fittings(array($goods_id), $warehouse_id, $area_id, $rev, 1, $goodSEqual);
		$fittings = array_merge($goods_info, $fittings);
		$fittings = array_values($fittings);
		$fittings_interval = get_choose_goods_combo_cart($fittings, $number);
		$result["amount"] = (!empty($fittings_interval["fittings_price"]) ? $fittings_interval["fittings_price"] : 0);

		if ($list_select == 1) {
			$result["goods_amount"] = (!empty($fittings_interval["fittings_price"]) ? price_format($fittings_interval["fittings_price"]) : 0);
			$result["goods_market_amount"] = (!empty($fittings_interval["all_market_price"]) ? price_format($fittings_interval["all_market_price"]) : 0);
			$result["save_amount"] = price_format($fittings_interval["save_price_amount"]);
		}
		else {
			$result["goods_amount"] = price_format($fittings_interval["fittings_min"]) . "-" . number_format($fittings_interval["fittings_max"], 2, ".", "");

			if ($fittings_interval["save_minPrice"] == $fittings_interval["save_maxPrice"]) {
				$result["save_amount"] = price_format($fittings_interval["save_minPrice"]);
			}
			else {
				$result["save_amount"] = price_format($fittings_interval["save_minPrice"]) . "-" . number_format($fittings_interval["save_maxPrice"], 2, ".", "");
			}

			$result["goods_market_amount"] = price_format($fittings_interval["market_min"]) . "-" . number_format($fittings_interval["market_max"], 2, ".", "");
		}

		$result["fittings_minMax"] = $result["goods_amount"];
		$result["market_minMax"] = $result["goods_market_amount"];
		$result["save_minMaxPrice"] = $result["save_amount"];
	}
	else {
		if (0 < $combo_goods["combo_number"]) {
			$goods_info = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, $rev);
			$fittings = get_goods_fittings(array($goods_id), $warehouse_id, $area_id, $rev, 1);
		}
		else {
			$goods_info = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, "", 1);
			$fittings = get_goods_fittings(array($goods_id), $warehouse_id, $area_id);
		}

		$fittings = array_merge($goods_info, $fittings);
		$fittings = array_values($fittings);
		$fittings_interval = get_choose_goods_combo_cart($fittings);

		if (0 < $combo_goods["combo_number"]) {
			if ($list_select == 1) {
				$result["fittings_minMax"] = price_format($fittings_interval["all_price_ori"]);
				$result["market_minMax"] = price_format($fittings_interval["all_market_price"]);
				$result["save_minMaxPrice"] = price_format($fittings_interval["save_price_amount"]);
			}
			else {
				if ($fittings_interval["return_attr"] < 1) {
					$result["fittings_minMax"] = price_format($fittings_interval["all_price_ori"]);
					$result["save_minMaxPrice"] = price_format($fittings_interval["save_price_amount"]);
				}
				else {
					$result["fittings_minMax"] = price_format($fittings_interval["fittings_min"]) . "-" . number_format($fittings_interval["fittings_max"], 2, ".", "");

					if ($fittings_interval["save_minPrice"] == $fittings_interval["save_maxPrice"]) {
						$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]);
					}
					else {
						$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]) . "-" . number_format($fittings_interval["save_maxPrice"], 2, ".", "");
					}
				}

				if ($fittings_interval["return_attr"] < 1) {
					$result["market_minMax"] = price_format($fittings_interval["all_market_price"]);
				}
				else {
					$result["market_minMax"] = price_format($fittings_interval["market_min"]) . "-" . number_format($fittings_interval["market_max"], 2, ".", "");
				}
			}
		}
		else {
			$result["fittings_minMax"] = price_format($fittings_interval["fittings_min"]) . "-" . number_format($fittings_interval["fittings_max"], 2, ".", "");

			if ($fittings_interval["save_minPrice"] == $fittings_interval["save_maxPrice"]) {
				$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]);
			}
			else {
				$result["save_minMaxPrice"] = price_format($fittings_interval["save_minPrice"]) . "-" . number_format($fittings_interval["save_maxPrice"], 2, ".", "");
			}

			$result["market_minMax"] = price_format($fittings_interval["market_min"]) . "-" . number_format($fittings_interval["market_max"], 2, ".", "");
		}
	}

	$result["list_select"] = $list_select;
	$result["null_money"] = price_format(0);
	$result["collocation_number"] = $fittings_interval["collocation_number"];

	if ($combo_goods["combo_number"] < 1) {
		$fittings = array();
	}

	$smarty->assign("fittings", $fittings);
	$smarty->assign("fittings_minMax", $result["fittings_minMax"]);
	$smarty->assign("market_minMax", $result["market_minMax"]);
	$smarty->assign("save_minMaxPrice", $result["save_minMaxPrice"]);
	$smarty->assign("collocation_number", $result["collocation_number"]);
	$sql = "select group_number from " . $GLOBALS["ecs"]->table("goods") . " where goods_id = '$goods_id'";
	$group_number = $GLOBALS["db"]->getOne($sql);
	$smarty->assign("group_number", $group_number);
	$smarty->assign("null_money", price_format(0));
	$result["content"] = $smarty->fetch("library/goods_fittings_result.lbi");
	$result["content_type"] = $smarty->fetch("library/goods_fittings_result_type.lbi");
	exit($json->encode($result));
}

if ($_REQUEST["step"] == "add_cart_combo_goodsAttr") {
	include_once ("includes/cls_json.php");
	$_POST["group"] = strip_tags(urldecode($_POST["group"]));
	$_POST["group"] = json_str_iconv($_POST["group"]);
	$result = array("error" => 0, "message" => "", "content" => "", "goods_id" => "", "goods_amount" => 0);
	$json = new JSON();

	if (empty($_POST["group"])) {
		$result["error"] = 1;
		exit($json->encode($result));
	}

	$group = $json->decode($_POST["group"]);
	$goodsRow = explode("_", $group->group_rev);
	$goodSEqual = $group->fitt_goods;
	$type = $group->type;
	$tImg = $group->tImg;
	$attr_id = $group->attr;
	$number = 1;
	$goods_id = $group->goods_id;
	$warehouse_id = $goodsRow[4];
	$area_id = $goodsRow[5];
	$group_id = $goodsRow[0] . "_" . $goodsRow[1] . "_" . $goodsRow[2] . "_" . $goodsRow[3];
	$goods = get_goods_info($goods_id, $warehouse_id, $area_id);

	if ($goods_id == 0) {
		$result["message"] = $_LANG["err_change_attr"];
		$result["error"] = 1;
	}
	else {
		if ($number == 0) {
			$result["qty"] = $number = 1;
		}
		else {
			$result["qty"] = $number;
		}

		$group_attr = implode("|", $group->attr);
		$products = get_warehouse_id_attr_number($goods_id, $group_attr, $goods["user_id"], $warehouse_id, $area_id);
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
		$result["attr_number"] = $attr_number;
		$shop_price = get_final_price($goods_id, $number, true, $attr_id, $warehouse_id, $area_id);
		$prod_attr = array();

		if (!empty($prod["goods_attr"])) {
			$prod_attr = explode("|", $prod["goods_attr"]);
		}

		if (count($prod_attr) <= 1) {
			if (empty($result["attr_number"])) {
				$result["message"] = "商品库存不足";
			}
		}
		else if (1 < count($prod_attr)) {
			if (count($prod_attr) == count($attr_id)) {
				if (empty($result["attr_number"])) {
					$result["message"] = "商品库存不足";
				}
			}
			else {
				unset($result["attr_number"]);
			}
		}

		if (is_spec($prod_attr) && !empty($prod)) {
			$product_info = get_products_info($goods_id, $prod_attr, $warehouse_id, $area_id);
		}

		$warehouse_area = array("warehouse_id" => $warehouse_id, "area_id" => $area_id);
		$spec_price = spec_price($attr_id, $goods_id, $warehouse_area);
		$goods_attr = get_goods_attr_info($attr_id, "pice", $warehouse_id, $area_id);
		$parent = array("goods_attr_id" => implode(",", $attr_id), "product_id" => $product_info["product_id"], "goods_attr" => addslashes($goods_attr));
		$GLOBALS["db"]->autoExecute($GLOBALS["ecs"]->table("cart_combo"), $parent, "UPDATE", "group_id = '$group_id' AND goods_id = '$goods_id' AND " . $sess_id);

		if ($type == 1) {
			$goods_price = $shop_price;
		}
		else {
			$sql = "select goods_price from " . $GLOBALS["ecs"]->table("group_goods") . " where parent_id = '" . $goodsRow[3] . "' and goods_id = '$goods_id' and group_id = '" . $goodsRow[2] . "'";
			$goods_price = $GLOBALS["db"]->getOne($sql);
			$goods_price = $goods_price + $spec_price;
		}

		$img_flie = "";

		if (!empty($tImg)) {
			$img_flie = ", img_flie = '$tImg'";
		}

		$sql = "update " . $GLOBALS["ecs"]->table("cart_combo") . " set goods_price = '$goods_price' " . $img_flie . " where group_id = '$group_id' AND goods_id = '$goods_id' AND " . $sess_id;
		$GLOBALS["db"]->query($sql);
		$result["goods_id"] = $goods_id;
		$result["shop_price"] = price_format($shop_price);
		$result["market_price"] = $goods["market_price"];
		$result["result"] = price_format($shop_price * $number);
		$result["groupId"] = $goodsRow[2];
		$attr_type_list = get_goods_attr_type_list($goods_id, 1);

		if ($attr_type_list == count($attr_id)) {
			$result["attr_equal"] = 1;
		}
		else {
			$result["attr_equal"] = 0;
		}

		$goods_info = get_goods_fittings_info($goodsRow[3], $warehouse_id, $area_id, $group_id);
		$fittings = get_goods_fittings(array($goodsRow[3]), $warehouse_id, $area_id, $group_id, 1, $goodSEqual);
		$fittings = array_merge($goods_info, $fittings);
		$fittings = array_values($fittings);
		$fittings_interval = get_choose_goods_combo_cart($fittings);

		if ($fittings_interval["return_attr"] < 1) {
			$result["amount"] = (!empty($fittings_interval["all_price_ori"]) ? $fittings_interval["all_price_ori"] : 0);
			$result["goods_amount"] = (!empty($fittings_interval["all_price_ori"]) ? price_format($fittings_interval["all_price_ori"]) : 0);
		}
		else {
			$result["amount"] = (!empty($fittings_interval["fittings_price"]) ? $fittings_interval["fittings_price"] : 0);
			$result["goods_amount"] = (!empty($fittings_interval["fittings_price"]) ? price_format($fittings_interval["fittings_price"]) : 0);
		}

		$result["goods_market_amount"] = (!empty($fittings_interval["all_market_price"]) ? price_format($fittings_interval["all_market_price"]) : 0);
		$result["save_amount"] = price_format($fittings_interval["save_price_amount"]);
	}

	$list_select = get_combo_goods_list_select(0, $goodsRow[3], $group_id);
	$result["list_select"] = $list_select;
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "add_del_cart_combo_list") {
	include_once ("includes/cls_json.php");
	$_POST["group"] = strip_tags(urldecode($_POST["group"]));
	$_POST["group"] = json_str_iconv($_POST["group"]);
	$result = array("error" => 0, "message" => "", "content" => "", "goods_id" => "");
	$json = new JSON();

	if (empty($_POST["group"])) {
		$result["error"] = 1;
		exit($json->encode($result));
	}

	$group = $json->decode($_POST["group"]);
	$goodsRow = explode("|", $group->group_rev);
	$goods_id = $goodsRow[0];
	$group_id = str_replace("=", "_", $goodsRow[3]);
	$goodsRow2 = explode("=", $goodsRow[3]);
	$parent_id = $goodsRow2[1];
	$goodSEqual = $group->fitt_goods;
	$sql = "delete from " . $GLOBALS["ecs"]->table("cart_combo") . " where goods_id = '$goods_id' and group_id = '$group_id' and " . $sess_id;
	$GLOBALS["db"]->query($sql);
	$sql = "select count(*) from " . $GLOBALS["ecs"]->table("cart_combo") . " where " . $sess_id . " and parent_id = '$parent_id' AND group_id = '$group_id'";
	$rec_count = $GLOBALS["db"]->getOne($sql);

	if ($rec_count < 1) {
		$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart_combo") . " WHERE " . $sess_id . " AND goods_id = '$parent_id' AND parent_id = 0  AND group_id = '$group_id'";
		$GLOBALS["db"]->query($sql);
		$result["fitt_goods"] = "";
	}
	else {
		$arr = array();

		foreach ($goodSEqual as $key => $row ) {
			if ($row != $goods_id) {
				$arr[$key] = $row;
			}
		}
	}

	$result["fitt_goods"] = $arr;
	$result["add_group"] = $group->add_group;
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "link_buy") {
	$goods_id = intval($_GET["goods_id"]);

	if (!cart_goods_exists($goods_id, array())) {
		addto_cart($goods_id);
	}

	ecs_header("Location:./flow.php\n");
	exit();
}
else if ($_REQUEST["step"] == "login") {
	include_once ("languages/" . $_CFG["lang"] . "/user.php");

	if (0 < $_SESSION["user_id"]) {
		ecs_header("Location:./flow.php?step=consignee\n");
		exit();
	}

	if ($_SERVER["REQUEST_METHOD"] == "GET") {
		$smarty->assign("anonymous_buy", $_CFG["anonymous_buy"]);
		$sql = "SELECT COUNT(*) FROM " . $ecs->table("cart") . " WHERE " . $sess_id . " AND is_gift > 0";

		if (0 < $db->getOne($sql)) {
			$smarty->assign("need_rechoose_gift", 1);
		}

		$captcha = intval($_CFG["captcha"]);
		if (($captcha & CAPTCHA_LOGIN) && (!$captcha & CAPTCHA_LOGIN_FAIL || (($captcha & CAPTCHA_LOGIN_FAIL) && (2 < $_SESSION["login_fail"]))) && (0 < gd_version())) {
			$smarty->assign("enabled_login_captcha", 1);
			$smarty->assign("rand", mt_rand());
		}

		if ($captcha & CAPTCHA_REGISTER) {
			$smarty->assign("enabled_register_captcha", 1);
			$smarty->assign("rand", mt_rand());
		}
	}
	else {
		include_once ("includes/lib_passport.php");
		if (!empty($_POST["act"]) && ($_POST["act"] == "signin")) {
			$captcha = intval($_CFG["captcha"]);
			if (($captcha & CAPTCHA_LOGIN) && (!$captcha & CAPTCHA_LOGIN_FAIL || (($captcha & CAPTCHA_LOGIN_FAIL) && (2 < $_SESSION["login_fail"]))) && (0 < gd_version())) {
				if (empty($_POST["captcha"])) {
					show_message($_LANG["invalid_captcha"]);
				}

				include_once ("includes/cls_captcha.php");
				$validator = new captcha();
				$validator->session_word = "captcha_login";

				if (!$validator->check_word($_POST["captcha"])) {
					show_message($_LANG["invalid_captcha"]);
				}
			}

			if ($user->login($_POST["username"], $_POST["password"], isset($_POST["remember"]))) {
				update_user_info();
				recalculate_price();

				if (!empty($_SESSION["user_id"])) {
					$login_sess = " user_id = '" . $_SESSION["user_id"] . "' ";
				}
				else {
					$login_sess = " session_id = '" . real_cart_mac_ip() . "' ";
				}

				$sql = "SELECT COUNT(*) FROM " . $ecs->table("cart") . " WHERE " . $login_sess;

				if (0 < $db->getOne($sql)) {
					ecs_header("Location: flow.php\n");
				}
				else {
					ecs_header("Location:index.php\n");
				}

				exit();
			}
			else {
				$$_SESSION["login_fail"]++;
				show_message($_LANG["signin_failed"], "", "flow.php?step=login");
			}
		}
		else {
			if (!empty($_POST["act"]) && ($_POST["act"] == "signup")) {
				if ((intval($_CFG["captcha"]) & CAPTCHA_REGISTER) && (0 < gd_version())) {
					if (empty($_POST["captcha"])) {
						show_message($_LANG["invalid_captcha"]);
					}

					include_once ("includes/cls_captcha.php");
					$validator = new captcha();

					if (!$validator->check_word($_POST["captcha"])) {
						show_message($_LANG["invalid_captcha"]);
					}
				}

				if (register(trim($_POST["username"]), trim($_POST["password"]), trim($_POST["email"]))) {
					ecs_header("Location: flow.php?step=consignee\n");
					exit();
				}
				else {
					$err->show();
				}
			}
		}
	}
}
else if ($_REQUEST["step"] == "consignee") {
	include_once ("includes/lib_transaction.php");

	if ($_SERVER["REQUEST_METHOD"] == "GET") {
		require (ROOT_PATH . "/includes/lib_area.php");

		if (isset($_REQUEST["direct_shopping"])) {
			$_SESSION["direct_shopping"] = 1;
		}

		$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
		$smarty->assign("country_list", get_regions());
		$smarty->assign("shop_country", $_CFG["shop_country"]);
		$smarty->assign("shop_province_list", get_regions(1, $_CFG["shop_country"]));

		if (0 < $_SESSION["user_id"]) {
			$consignee_list = get_consignee_list($_SESSION["user_id"]);

			if (count($consignee_list) < 5) {
				$consignee_list[] = array("country" => $_CFG["shop_country"], "email" => isset($_SESSION["email"]) ? $_SESSION["email"] : "");
			}
		}
		else if (isset($_SESSION["flow_consignee"])) {
			$consignee_list = array($_SESSION["flow_consignee"]);
		}
		else {
			$consignee_list[] = array("country" => $_CFG["shop_country"], "province" => $province_id, "city" => $city_id, "district" => $district_id);
		}

		$smarty->assign("name_of_region", array($_CFG["name_of_region_1"], $_CFG["name_of_region_2"], $_CFG["name_of_region_3"], $_CFG["name_of_region_4"]));
		$smarty->assign("consignee_list", $consignee_list);
		$province_list = array();
		$city_list = array();
		$district_list = array();

		foreach ($consignee_list as $region_id => $consignee ) {
			$consignee["country"] = (isset($consignee["country"]) ? intval($consignee["country"]) : 1);
			$consignee["province"] = (isset($consignee["province"]) ? intval($consignee["province"]) : $province_id);
			$consignee["city"] = (isset($consignee["city"]) ? intval($consignee["city"]) : $city_id);
			$province_list[$region_id] = get_regions(1, $consignee["country"]);
			$city_list[$region_id] = get_regions(2, $consignee["province"]);
			$district_list[$region_id] = get_regions(3, $consignee["city"]);
		}

		$smarty->assign("province_list", $province_list);
		$smarty->assign("city_list", $city_list);
		$smarty->assign("district_list", $district_list);
		$smarty->assign("real_goods_count", exist_real_goods(0, $flow_type) ? 1 : 0);
	}
	else {
		$consignee = array("address_id" => empty($_POST["address_id"]) ? 0 : intval($_POST["address_id"]), "consignee" => empty($_POST["consignee"]) ? "" : compile_str(trim($_POST["consignee"])), "country" => empty($_POST["country"]) ? "" : intval($_POST["country"]), "province" => empty($_POST["province"]) ? "" : intval($_POST["province"]), "city" => empty($_POST["city"]) ? "" : intval($_POST["city"]), "district" => empty($_POST["district"]) ? "" : intval($_POST["district"]), "email" => empty($_POST["email"]) ? "" : compile_str($_POST["email"]), "address" => empty($_POST["address"]) ? "" : compile_str($_POST["address"]), "zipcode" => empty($_POST["zipcode"]) ? "" : compile_str(make_semiangle(trim($_POST["zipcode"]))), "tel" => empty($_POST["tel"]) ? "" : compile_str(make_semiangle(trim($_POST["tel"]))), "mobile" => empty($_POST["mobile"]) ? "" : compile_str(make_semiangle(trim($_POST["mobile"]))), "sign_building" => empty($_POST["sign_building"]) ? "" : compile_str($_POST["sign_building"]), "best_time" => empty($_POST["best_time"]) ? "" : compile_str($_POST["best_time"]));

		if (0 < $_SESSION["user_id"]) {
			include_once (ROOT_PATH . "includes/lib_transaction.php");
			$consignee["user_id"] = $_SESSION["user_id"];
			save_consignee($consignee, true);
		}

		$_SESSION["flow_consignee"] = stripslashes_deep($consignee);
		ecs_header("Location: flow.php?step=checkout&direct_shopping=1\n");
		exit();
	}
}
else if ($_REQUEST["step"] == "drop_consignee") {
	include_once ("includes/lib_transaction.php");
	$consignee_id = intval($_GET["id"]);

	if (drop_consignee($consignee_id)) {
		ecs_header("Location: flow.php?step=consignee\n");
		exit();
	}
	else {
		show_message($_LANG["not_fount_consignee"]);
	}
}
else if ($_REQUEST["step"] == "checkout") {
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$_SESSION["merchants_shipping"] = array();
	$direct_shopping = (isset($_REQUEST["direct_shopping"]) ? $_REQUEST["direct_shopping"] : 0);
	$cart_value = (isset($_REQUEST["cart_value"]) ? htmlspecialchars($_REQUEST["cart_value"]) : "");

	if (empty($cart_value)) {
		$cart_value = get_cart_value($flow_type);
	}

	$_SESSION["cart_value"] = $cart_value;
	$smarty->assign("cart_value", $cart_value);

	if ($flow_type == CART_GROUP_BUY_GOODS) {
		$smarty->assign("is_group_buy", 1);
	}
	else if ($flow_type == CART_EXCHANGE_GOODS) {
		$smarty->assign("is_exchange_goods", 1);
	}
	else if ($flow_type == CART_PRESALE_GOODS) {
		$smarty->assign("is_presale_goods", 1);
	}
	else {
		$_SESSION["flow_order"]["extension_code"] = "";
	}

	$sql = "SELECT COUNT(*) FROM " . $ecs->table("cart") . " WHERE " . $sess_id . "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";

	if ($db->getOne($sql) == 0) {
		show_message($_LANG["no_goods_in_cart"], "", "", "warning");
	}

	if (empty($direct_shopping) && ($_SESSION["user_id"] == 0)) {
		ecs_header("Location: flow.php?step=login\n");
		exit();
	}

	$consignee = get_consignee($_SESSION["user_id"]);

	if ($consignee) {
		setcookie("province", $consignee["province"], gmtime() + (3600 * 24 * 30));
		setcookie("city", $consignee["city"], gmtime() + (3600 * 24 * 30));
		setcookie("district", $consignee["district"], gmtime() + (3600 * 24 * 30));
		$flow_warehouse = get_warehouse_goods_region($consignee["province"]);
		setcookie("area_region", $flow_warehouse["region_id"], gmtime() + (3600 * 24 * 30));
		setcookie("flow_region", $flow_warehouse["region_id"], gmtime() + (3600 * 24 * 30));
	}

	$region_id = get_province_id_warehouse($consignee["province"]);
	$area_info = get_area_info($consignee["province"]);
	$smarty->assign("warehouse_id", $region_id);
	$smarty->assign("area_id", $area_info["region_id"]);
	$user_address = get_order_user_address_list($_SESSION["user_id"]);
	if (($direct_shopping != 1) && !empty($_SESSION["user_id"])) {
		$_SESSION["browse_trace"] = "flow.php";
	}
	else {
		$_SESSION["browse_trace"] = "flow.php?step=checkout";
	}

	if (!$user_address && $consignee) {
		$consignee["province_name"] = get_goods_region_name($consignee["province"]);
		$consignee["city_name"] = get_goods_region_name($consignee["city"]);
		$consignee["district_name"] = get_goods_region_name($consignee["district"]);
		$consignee["region"] = $consignee["province_name"] . "&nbsp;" . $consignee["city_name"] . "&nbsp;" . $consignee["district_name"];
		$user_address = array($consignee);
	}

	$smarty->assign("user_address", $user_address);
	$smarty->assign("auditStatus", $_CFG["auditStatus"]);
	$user_id = (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0);
	$smarty->assign("user_id", $user_id);
	$_SESSION["flow_consignee"] = $consignee;
	$consignee["province_name"] = get_goods_region_name($consignee["province"]);
	$consignee["city_name"] = get_goods_region_name($consignee["city"]);
	$consignee["district_name"] = get_goods_region_name($consignee["district"]);
	$consignee["consignee_address"] = $consignee["province_name"] . $consignee["city_name"] . $consignee["district_name"] . $consignee["address"];
	$smarty->assign("consignee", $consignee);
	$cart_goods_list = cart_goods($flow_type, $cart_value, 1, $region_id, $area_info["region_id"]);
	$cart_goods_list_new = cart_by_favourable($cart_goods_list);
	$smarty->assign("goods_list", $cart_goods_list_new);
	$cart_goods_number = get_buy_cart_goods_number($flow_type, $cart_value);
	$smarty->assign("cart_goods_number", $cart_goods_number);
	$cart_goods = cart_goods($flow_type, $cart_value);
	if (($flow_type != CART_GENERAL_GOODS) || ($_CFG["one_step_buy"] == "1")) {
		$smarty->assign("allow_edit_cart", 0);
	}
	else {
		$smarty->assign("allow_edit_cart", 1);
	}

	$smarty->assign("config", $_CFG);
	$order = flow_order_info();
	$smarty->assign("order", $order);
	if ((!isset($_CFG["can_invoice"]) || ($_CFG["can_invoice"] == "1")) && isset($_CFG["invoice_content"]) && (trim($_CFG["invoice_content"]) != "") && ($flow_type != CART_EXCHANGE_GOODS)) {
		$inv_content_list = explode("\n", str_replace("\r", "", $_CFG["invoice_content"]));
		$smarty->assign("inv_content", $inv_content_list[0]);
		$order["need_inv"] = 1;
		$order["inv_type"] = $_CFG["invoice_type"]["type"][0];
		$order["inv_payee"] = "个人";
		$order["inv_content"] = $inv_content_list[0];
	}

	if (($flow_type != CART_EXCHANGE_GOODS) && ($flow_type != CART_GROUP_BUY_GOODS)) {
		$discount = compute_discount(3, $cart_value);
		$smarty->assign("discount", $discount["discount"]);
		$favour_name = (empty($discount["name"]) ? "" : join(",", $discount["name"]));
		$smarty->assign("your_discount", sprintf($_LANG["your_discount"], $favour_name, price_format($discount["discount"])));
	}

	if (!$user_address) {
		$consignee = array("province" => 0, "city" => 0);
		$smarty->assign("country_list", get_regions());
		$smarty->assign("please_select", "请选择");
		$province_list = get_regions_log(1, 1);
		$city_list = get_regions_log(2, $consignee["province"]);
		$district_list = get_regions_log(3, $consignee["city"]);
		$smarty->assign("province_list", $province_list);
		$smarty->assign("city_list", $city_list);
		$smarty->assign("district_list", $district_list);
		$smarty->assign("consignee", $consignee);
	}

	$total = order_fee($order, $cart_goods, $consignee, 0, $cart_value, 0, $cart_goods_list);
	$smarty->assign("total", $total);
	$smarty->assign("shopping_money", sprintf($_LANG["shopping_money"], $total["formated_goods_price"]));
	$smarty->assign("market_price_desc", sprintf($_LANG["than_market_price"], $total["formated_market_price"], $total["formated_saving"], $total["save_rate"]));

	if ($order["shipping_id"] == 0) {
		$cod = true;
		$cod_fee = 0;
	}
	else {
		$shipping = shipping_info($order["shipping_id"]);
		$cod = $shipping["support_cod"];

		if ($cod) {
			if ($flow_type == CART_GROUP_BUY_GOODS) {
				$group_buy_id = $_SESSION["extension_id"];

				if ($group_buy_id <= 0) {
					show_message("error group_buy_id");
				}

				$group_buy = group_buy_info($group_buy_id);

				if (empty($group_buy)) {
					show_message("group buy not exists: " . $group_buy_id);
				}

				if (0 < $group_buy["deposit"]) {
					$cod = false;
					$cod_fee = 0;
					$smarty->assign("gb_deposit", $group_buy["deposit"]);
				}
			}

			if ($cod) {
				$shipping_area_info = shipping_area_info($order["shipping_id"], $region);
				$cod_fee = $shipping_area_info["pay_fee"];
			}
		}
		else {
			$cod_fee = 0;
		}
	}

	$payment_list = available_payment_list(1, $cod_fee);

	if (isset($payment_list)) {
		foreach ($payment_list as $key => $payment ) {
			if (substr($payment["pay_code"], 0, 4) == "pay_") {
				unset($payment_list[$key]);
				continue;
			}

			if ($payment["is_cod"] == "1") {
				$payment_list[$key]["format_pay_fee"] = "<span id=\"ECS_CODFEE\">" . $payment["format_pay_fee"] . "</span>";
			}

			if (($payment["pay_code"] == "yeepayszx") && (300 < $total["amount"])) {
				unset($payment_list[$key]);
			}

			if ($payment["pay_code"] == "alipay_wap") {
				unset($payment_list[$key]);
			}

			if ($payment["pay_code"] == "balance") {
				if ($_SESSION["user_id"] == 0) {
					unset($payment_list[$key]);
				}
				else if ($_SESSION["flow_order"]["pay_id"] == $payment["pay_id"]) {
					$smarty->assign("disable_surplus", 1);
				}
			}
		}
	}

	$smarty->assign("payment_list", $payment_list);

	if (0 < $total["real_goods_count"]) {
		if (!isset($_CFG["use_package"]) || ($_CFG["use_package"] == "1")) {
			$smarty->assign("pack_list", pack_list());
		}

		if (!isset($_CFG["use_card"]) || ($_CFG["use_card"] == "1")) {
			$smarty->assign("card_list", card_list());
		}
	}

	$user_info = user_info($_SESSION["user_id"]);
	if ((!isset($_CFG["use_surplus"]) || ($_CFG["use_surplus"] == "1")) && (0 < $_SESSION["user_id"]) && (0 < $user_info["user_money"])) {
		$smarty->assign("allow_use_surplus", 1);
		$smarty->assign("your_surplus", $user_info["user_money"]);
	}

	if ((!isset($_CFG["use_integral"]) || ($_CFG["use_integral"] == "1")) && (0 < $_SESSION["user_id"]) && (0 < $user_info["pay_points"]) && ($flow_type != CART_GROUP_BUY_GOODS) && ($flow_type != CART_EXCHANGE_GOODS)) {
		$smarty->assign("allow_use_integral", 1);
		$smarty->assign("order_max_integral", flow_available_points($cart_value));
		$smarty->assign("your_integral", $user_info["pay_points"]);
	}

	if ((!isset($_CFG["use_bonus"]) || ($_CFG["use_bonus"] == "1")) && ($flow_type != CART_GROUP_BUY_GOODS) && ($flow_type != CART_EXCHANGE_GOODS)) {
		$user_bonus = user_bonus($_SESSION["user_id"], $total["goods_price"], $cart_value);

		if (!empty($user_bonus)) {
			foreach ($user_bonus as $key => $val ) {
				$user_bonus[$key]["bonus_money_formated"] = price_format($val["type_money"], false);
			}

			$smarty->assign("bonus_list", $user_bonus);
		}

		$smarty->assign("allow_use_bonus", 1);
	}

	if (!isset($_CFG["use_how_oos"]) || ($_CFG["use_how_oos"] == "1")) {
		if (is_array($GLOBALS["_LANG"]["oos"]) && !empty($GLOBALS["_LANG"]["oos"])) {
			$smarty->assign("how_oos_list", $GLOBALS["_LANG"]["oos"]);
		}
	}

	$_SESSION["flow_order"] = $order;
}
else if ($_REQUEST["step"] == "select_shipping") {
	include_once ("includes/cls_json.php");
	$json = new JSON();
	$result = array("error" => "", "content" => "", "need_insure" => 0);
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		$result["error"] = $_LANG["no_goods_in_cart"];
	}
	else {
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();
		$order["shipping_id"] = intval($_REQUEST["shipping"]);
		$regions = array($consignee["country"], $consignee["province"], $consignee["city"], $consignee["district"]);
		$shipping_info = shipping_area_info($order["shipping_id"], $regions);
		$total = order_fee($order, $cart_goods, $consignee);
		$smarty->assign("total", $total);
		$smarty->assign("total_integral", cart_amount(false, $flow_type) - $total["bonus"] - $total["integral_money"]);
		$smarty->assign("total_bonus", price_format(get_total_bonus(), false));

		if ($flow_type == CART_GROUP_BUY_GOODS) {
			$smarty->assign("is_group_buy", 1);
		}

		$result["cod_fee"] = $shipping_info["pay_fee"];

		if (strpos($result["cod_fee"], "%") === false) {
			$result["cod_fee"] = price_format($result["cod_fee"], false);
		}

		$ru_list = get_ru_info_list($total["ru_list"]);
		$smarty->assign("warehouse_fee", $ru_list);
		$smarty->assign("freight_model", $GLOBALS["_CFG"]["freight_model"]);
		$result["need_insure"] = ((0 < $shipping_info["insure"]) && !empty($order["need_insure"]) ? 1 : 0);
		$result["content"] = $smarty->fetch("library/order_total.lbi");
	}

	echo $json->encode($result);
	exit();
}
else if ($_REQUEST["step"] == "select_insure") {
	include_once ("includes/cls_json.php");
	$json = new JSON();
	$result = array("error" => "", "content" => "", "need_insure" => 0);
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		$result["error"] = $_LANG["no_goods_in_cart"];
	}
	else {
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();
		$order["need_insure"] = intval($_REQUEST["insure"]);
		$_SESSION["flow_order"] = $order;
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
		$smarty->assign("total_integral", cart_amount(false, $flow_type) - $total["bonus"] - $total["integral_money"]);
		$smarty->assign("total_bonus", price_format(get_total_bonus(), false));

		if ($flow_type == CART_GROUP_BUY_GOODS) {
			$smarty->assign("is_group_buy", 1);
		}

		$result["content"] = $smarty->fetch("library/order_total.lbi");
	}

	echo $json->encode($result);
	exit();
}
else if ($_REQUEST["step"] == "pickSite") {
	include ("includes/cls_json.php");
	$json = new JSON();
	$res = array("err_msg" => "", "result" => "");
	$mark = (isset($_REQUEST["mark"]) ? intval($_REQUEST["mark"]) : 0);

	if ($mark == 1) {
		$days = array();

		for ($i = 0; $i <= 6; $i++) {
			$days[$i]["shipping_date"] = date("Y-m-d", strtotime(" +" . $i . "day"));
			$days[$i]["date_year"] = $days[$i]["shipping_date"];
			$days[$i]["week"] = "周" . transition_date($days[$i]["shipping_date"]);
			$days[$i]["date"] = substr($days[$i]["shipping_date"], 5);
		}

		$shipping_date_list = $db->getAll("SELECT * FROM " . $ecs->table("shipping_date"));
		$select = array();

		foreach ($shipping_date_list as $key => $val ) {
			$m = 0;

			for ($s = 0; $s < 7; $s++) {
				if ($s < $val["select_day"]) {
					$select[$m]["day"] = 0;
					$select[$m]["date"] = $days[$m]["date"];
					$select[$m]["week"] = $days[$m]["week"];
					$select[$m]["shipping_date"] = $days[$m]["shipping_date"];
				}
				else {
					$strtime = $days[$m]["date_year"] . " " . $val["end_date"];
					$strtime = strtotime($strtime);
					$select[$m]["day"] = 1;

					if ($strtime < (gmtime() + (8 * 3600))) {
						$select[$m]["day"] = 0;
					}

					$select[$m]["date"] = $days[$m]["date"];
					$select[$m]["week"] = $days[$m]["week"];
					$select[$m]["shipping_date"] = $days[$m]["shipping_date"];
				}

				$m++;
			}

			$shipping_date_list[$key]["select_day"] = $select;
			$select = array();
		}

		$smarty->assign("years", local_date("Y", gmtime()));
		$smarty->assign("days", $days);
		$smarty->assign("shipping_date_list", $shipping_date_list);
		$res["result"] = $GLOBALS["smarty"]->fetch("library/picksite_date.lbi");
	}
	else {
		$district = $_SESSION["flow_consignee"]["district"];
		$city = $_SESSION["flow_consignee"]["city"];
		$sql = "SELECT * FROM " . $ecs->table("region") . " WHERE parent_id = '$city'";
		$district_list = $db->getAll($sql);
		$picksite_list = get_self_point($district);
		$smarty->assign("picksite_list", $picksite_list);
		$smarty->assign("district_list", $district_list);
		$smarty->assign("district", $district);
		$smarty->assign("city", $city);
		$res["result"] = $GLOBALS["smarty"]->fetch("library/picksite.lbi");
	}

	exit($json->encode($res));
}
else if ($_REQUEST["step"] == "getPickSiteList") {
	include_once ("includes/cls_json.php");
	$district = (!empty($_POST["id"]) ? intval($_POST["id"]) : 0);
	$result = array("error" => 0, "message" => "", "content" => "");
	$json = new JSON();

	if ($district == 0) {
		$sql = "SELECT a.region_id ,a.shipping_area_id,b.region_name,b.parent_id as city,c.name,c.user_name,c.id as point_id,c.address,c.mobile,c.img_url,c.anchor,c.line FROM " . $GLOBALS["ecs"]->table("area_region") . " AS a\r\n                LEFT JOIN " . $GLOBALS["ecs"]->table("region") . " AS b ON a.region_id=b.region_id  LEFT JOIN " . $GLOBALS["ecs"]->table("shipping_point") . " AS c ON c.shipping_area_id=a.shipping_area_id WHERE c.name != '' AND a.region_id IN (SELECT region_id FROM " . $ecs->table("region") . " WHERE parent_id='{$_SESSION[flow_consignee][city]}')";
		$self_point = $db->getAll($sql);
	}
	else {
		$self_point = get_self_point($district);
	}

	if (empty($self_point)) {
		$result["error"] = 1;
	}

	exit($json->encode($self_point));
}
else if ($_REQUEST["step"] == "select_picksite") {
	include ("includes/cls_json.php");
	$json = new JSON();
	$res = array("error" => 0, "err_msg" => "", "content" => "");
	$picksite_id = (isset($_REQUEST["picksite_id"]) ? intval($_REQUEST["picksite_id"]) : 0);
	$district = (isset($_REQUEST["district"]) ? intval($_REQUEST["district"]) : 0);
	$shipping_date = (isset($_REQUEST["shipping_date"]) ? htmlspecialchars($_REQUEST["shipping_date"]) : "");
	$time_range = (isset($_REQUEST["time_range"]) ? htmlspecialchars($_REQUEST["time_range"]) : "");
	$mark = (isset($_REQUEST["mark"]) ? intval($_REQUEST["mark"]) : 0);

	if ($mark == 0) {
		$_SESSION["flow_consignee"]["point_id"] = $picksite_id;
	}
	else {
		if ($shipping_date) {
			$week = "周" . transition_date($shipping_date);
		}

		$shipping_dateStr = date("m", strtotime($shipping_date)) . "月" . date("d", strtotime($shipping_date)) . "日【" . $week . "】" . $time_range;
		$_SESSION["flow_consignee"]["shipping_dateStr"] = $shipping_dateStr;
	}

	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods_list = cart_goods($flow_type, $_SESSION["cart_value"], 1);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		if (empty($cart_goods)) {
			$result["error"] = 1;
			$result["err_msg"] = $_LANG["no_goods_in_cart"];
		}
		else if (!check_consignee_info($consignee, $flow_type)) {
			$result["error"] = 2;
			$result["err_msg"] = $_LANG["au_buy_after_login"];
		}
	}

	$smarty->assign("goods_list", cart_by_favourable($cart_goods_list));
	$res["content"] = $GLOBALS["smarty"]->fetch("library/flow_cart_goods.lbi");
	exit($json->encode($res));
}
else if ($_REQUEST["step"] == "select_payment") {
	include_once ("includes/cls_json.php");
	$json = new JSON();
	$result = array("error" => 0, "massage" => "", "content" => "", "need_insure" => 0, "payment" => 1);
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		if (empty($cart_goods)) {
			$result["error"] = 1;
		}
		else if (!check_consignee_info($consignee, $flow_type)) {
			$result["error"] = 2;
		}
	}
	else {
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();
		$order["pay_id"] = intval($_REQUEST["payment"]);
		$payment_info = payment_info($order["pay_id"]);
		$result["pay_code"] = $payment_info["pay_code"];
		$_SESSION["flow_order"] = $order;
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
		$smarty->assign("total_integral", cart_amount(false, $flow_type) - $total["bonus"] - $total["integral_money"]);
		$smarty->assign("total_bonus", price_format(get_total_bonus(), false));

		if ($flow_type == CART_GROUP_BUY_GOODS) {
			$smarty->assign("is_group_buy", 1);
		}

		$result["content"] = $smarty->fetch("library/order_total.lbi");
	}

	echo $json->encode($result);
	exit();
}
else if ($_REQUEST["step"] == "select_pack") {
	include_once ("includes/cls_json.php");
	$json = new JSON();
	$result = array("error" => "", "content" => "", "need_insure" => 0);
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		$result["error"] = $_LANG["no_goods_in_cart"];
	}
	else {
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();
		$order["pack_id"] = intval($_REQUEST["pack"]);
		$_SESSION["flow_order"] = $order;
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
		$smarty->assign("total_integral", cart_amount(false, $flow_type) - $total["bonus"] - $total["integral_money"]);
		$smarty->assign("total_bonus", price_format(get_total_bonus(), false));

		if ($flow_type == CART_GROUP_BUY_GOODS) {
			$smarty->assign("is_group_buy", 1);
		}

		$result["content"] = $smarty->fetch("library/order_total.lbi");
	}

	echo $json->encode($result);
	exit();
}
else if ($_REQUEST["step"] == "select_card") {
	include_once ("includes/cls_json.php");
	$json = new JSON();
	$result = array("error" => "", "content" => "", "need_insure" => 0);
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		$result["error"] = $_LANG["no_goods_in_cart"];
	}
	else {
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();
		$order["card_id"] = intval($_REQUEST["card"]);
		$_SESSION["flow_order"] = $order;
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
		$smarty->assign("total_integral", cart_amount(false, $flow_type) - $order["bonus"] - $total["integral_money"]);
		$smarty->assign("total_bonus", price_format(get_total_bonus(), false));

		if ($flow_type == CART_GROUP_BUY_GOODS) {
			$smarty->assign("is_group_buy", 1);
		}

		$result["content"] = $smarty->fetch("library/order_total.lbi");
	}

	echo $json->encode($result);
	exit();
}
else if ($_REQUEST["step"] == "change_surplus") {
	include_once ("includes/cls_json.php");
	$surplus = floatval($_GET["surplus"]);
	$user_info = user_info($_SESSION["user_id"]);

	if (($user_info["user_money"] + $user_info["credit_line"]) < $surplus) {
		$result["error"] = $_LANG["surplus_not_enough"];
	}
	else {
		$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
		$smarty->assign("config", $_CFG);
		$consignee = get_consignee($_SESSION["user_id"]);
		$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
		if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
			$result["error"] = $_LANG["no_goods_in_cart"];
		}
		else {
			$order = flow_order_info();
			$order["surplus"] = $surplus;
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
	}

	$json = new JSON();
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "change_integral") {
	include_once ("includes/cls_json.php");
	$points = floatval($_GET["points"]);
	$user_info = user_info($_SESSION["user_id"]);
	$order = flow_order_info();
	$_SESSION["cart_value"] = $cart_value;
	$flow_points = flow_available_points($cart_value);
	$user_points = $user_info["pay_points"];

	if ($user_points < $points) {
		$result["error"] = $_LANG["integral_not_enough"];
	}
	else if ($flow_points < $points) {
		$result["error"] = sprintf($_LANG["integral_too_much"], $flow_points);
	}
	else {
		$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
		$order["integral"] = $points;
		$consignee = get_consignee($_SESSION["user_id"]);
		$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
		if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
			$result["error"] = $_LANG["no_goods_in_cart"];
		}
		else {
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
			$smarty->assign("config", $_CFG);

			if ($flow_type == CART_GROUP_BUY_GOODS) {
				$smarty->assign("is_group_buy", 1);
			}

			$result["content"] = $smarty->fetch("library/order_total.lbi");
			$result["error"] = "";
		}
	}

	$json = new JSON();
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "change_bonus") {
	include_once ("includes/cls_json.php");
	$result = array("error" => "", "content" => "");
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		$result["error"] = $_LANG["no_goods_in_cart"];
	}
	else {
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();
		$bonus = bonus_info(intval($_GET["bonus"]));
		if ((!empty($bonus) && ($bonus["user_id"] == $_SESSION["user_id"])) || ($_GET["bonus"] == 0)) {
			$order["bonus_id"] = intval($_GET["bonus"]);
		}
		else {
			$order["bonus_id"] = 0;
			$result["error"] = $_LANG["invalid_bonus"];
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

		$result["content"] = $smarty->fetch("library/order_total.lbi");
	}

	$json = new JSON();
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "change_needinv") {
	include_once ("includes/cls_json.php");
	$result = array("error" => "", "content" => "");
	$json = new JSON();
	$_GET["inv_type"] = (!empty($_GET["inv_type"]) ? json_str_iconv(urldecode($_GET["inv_type"])) : "");
	$_GET["invPayee"] = (!empty($_GET["invPayee"]) ? json_str_iconv(urldecode($_GET["invPayee"])) : "");
	$_GET["inv_content"] = (!empty($_GET["inv_content"]) ? json_str_iconv(urldecode($_GET["inv_content"])) : "");
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
		if (isset($_GET["need_inv"]) && (intval($_GET["need_inv"]) == 1)) {
			$order["need_inv"] = 1;
			$order["inv_type"] = trim(stripslashes($_GET["inv_type"]));
			$order["inv_payee"] = trim(stripslashes($_GET["inv_payee"]));
			$order["inv_content"] = trim(stripslashes($_GET["inv_content"]));
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

		exit($smarty->fetch("library/order_total.lbi"));
	}
}
else if ($_REQUEST["step"] == "change_oos") {
	$order = flow_order_info();
	$order["how_oos"] = intval($_GET["oos"]);
	$_SESSION["flow_order"] = $order;
}
else if ($_REQUEST["step"] == "check_surplus") {
	$surplus = floatval($_GET["surplus"]);
	$user_info = user_info($_SESSION["user_id"]);

	if (($user_info["user_money"] + $user_info["credit_line"]) < $surplus) {
		exit($_LANG["surplus_not_enough"]);
	}

	exit();
}
else if ($_REQUEST["step"] == "check_integral") {
	$points = floatval($_GET["integral"]);
	$user_info = user_info($_SESSION["user_id"]);
	$_SESSION["cart_value"] = $cart_value;
	$flow_points = flow_available_points($cart_value);
	$user_points = $user_info["pay_points"];

	if ($user_points < $points) {
		exit($_LANG["integral_not_enough"]);
	}

	if ($flow_points < $points) {
		exit(sprintf($_LANG["integral_too_much"], $flow_points));
	}

	exit();
}
else if ($_REQUEST["step"] == "done") {
	include_once ("includes/lib_clips.php");
	include_once ("includes/lib_payment.php");
	$where_flow = "";
	$pay_type = (isset($_POST["pay_type"]) ? intval($_POST["pay_type"]) : 0);
	$done_cart_value = (isset($_POST["done_cart_value"]) ? htmlspecialchars($_POST["done_cart_value"]) : 0);
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$sql = "SELECT COUNT(*) FROM " . $ecs->table("cart") . " WHERE " . $sess_id . " AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";

	if ($db->getOne($sql) == 0) {
		header("Location: flow.php\n");
	}

	if (($_CFG["use_storage"] == "1") && ($_CFG["stock_dec_time"] == SDT_PLACE)) {
		$cart_goods_stock = get_cart_goods($done_cart_value);
		$_cart_goods_stock = array();

		foreach ($cart_goods_stock["goods_list"] as $value ) {
			$_cart_goods_stock[$value["rec_id"]] = $value["goods_number"];
		}

		flow_cart_stock($_cart_goods_stock);
		unset($cart_goods_stock);
		unset($_cart_goods_stock);
	}

	if (empty($_SESSION["direct_shopping"]) && ($_SESSION["user_id"] == 0)) {
		ecs_header("Location: flow.php?step=login\n");
		exit();
	}

	$consignee = get_consignee($_SESSION["user_id"]);

	if (!check_consignee_info($consignee, $flow_type)) {
		ecs_header("Location: flow.php?step=consignee\n");
		exit();
	}

	$_POST["how_oos"] = (isset($_POST["how_oos"]) ? intval($_POST["how_oos"]) : 0);
	$_POST["card_message"] = (isset($_POST["card_message"]) ? compile_str($_POST["card_message"]) : "");
	$_POST["inv_type"] = (!empty($_POST["inv_type"]) ? compile_str($_POST["inv_type"]) : "");
	$_POST["inv_payee"] = (isset($_POST["inv_payee"]) ? compile_str($_POST["inv_payee"]) : "");
	$_POST["inv_content"] = (isset($_POST["inv_content"]) ? compile_str($_POST["inv_content"]) : "");
	$_POST["postscript"] = (isset($_POST["postscript"]) ? compile_str($_POST["postscript"]) : "");

	if (count($_POST["shipping"]) == 1) {
		$shipping["shipping_id"] = $_POST["shipping"][0];
	}
	else {
		$shipping = get_order_post_shipping($_POST["shipping"], $_POST["ru_id"]);
	}

	$order = array("shipping_id" => $shipping["shipping_id"], "pay_id" => intval($_POST["payment"]), "pack_id" => isset($_POST["pack"]) ? intval($_POST["pack"]) : 0, "card_id" => isset($_POST["card"]) ? intval($_POST["card"]) : 0, "card_message" => trim($_POST["card_message"]), "surplus" => isset($_POST["surplus"]) ? floatval($_POST["surplus"]) : 0, "integral" => isset($_POST["integral"]) ? intval($_POST["integral"]) : 0, "bonus_id" => isset($_POST["bonus"]) ? intval($_POST["bonus"]) : 0, "need_inv" => isset($_POST["inv_payee"]) ? 1 : 0, "inv_type" => isset($_CFG["invoice_type"]["type"][0]) ? $_CFG["invoice_type"]["type"][0] : "", "inv_payee" => isset($_POST["inv_payee"]) ? trim($_POST["inv_payee"]) : "", "inv_content" => isset($_POST["inv_content"]) ? trim($_POST["inv_content"]) : "", "postscript" => trim($_POST["postscript"]), "how_oos" => isset($_LANG["oos"][$_POST["how_oos"]]) ? addslashes($_LANG["oos"][$_POST["how_oos"]]) : "", "need_insure" => isset($_POST["need_insure"]) ? intval($_POST["need_insure"]) : 0, "user_id" => $_SESSION["user_id"], "add_time" => gmtime(), "order_status" => OS_UNCONFIRMED, "shipping_status" => SS_UNSHIPPED, "pay_status" => PS_UNPAYED, "agency_id" => get_agency_by_regions(array($consignee["country"], $consignee["province"], $consignee["city"], $consignee["district"])), "point_id" => isset($_POST["point_id"]) ? intval($_POST["point_id"]) : 0, "shipping_dateStr" => isset($_POST["shipping_dateStr"]) ? trim($_POST["shipping_dateStr"]) : "");
	if (isset($_SESSION["flow_type"]) && (intval($_SESSION["flow_type"]) != CART_GENERAL_GOODS)) {
		$order["extension_code"] = $_SESSION["extension_code"];
		$order["extension_id"] = $_SESSION["extension_id"];
	}
	else {
		$order["extension_code"] = "";
		$order["extension_id"] = 0;
	}

	$user_id = $_SESSION["user_id"];

	if (0 < $user_id) {
		$user_info = user_info($user_id);
		$order["surplus"] = min($order["surplus"], $user_info["user_money"] + $user_info["credit_line"]);

		if ($order["surplus"] < 0) {
			$order["surplus"] = 0;
		}

		$flow_points = flow_available_points($done_cart_value);
		$user_points = $user_info["pay_points"];
		$order["integral"] = min($order["integral"], $user_points, $flow_points);

		if ($order["integral"] < 0) {
			$order["integral"] = 0;
		}
	}
	else {
		$order["surplus"] = 0;
		$order["integral"] = 0;
	}

	if (0 < $order["bonus_id"]) {
		$bonus = bonus_info($order["bonus_id"]);
		if (empty($bonus) || ($bonus["user_id"] != $user_id) || (0 < $bonus["order_id"]) || (cart_amount(true, $flow_type) < $bonus["min_goods_amount"])) {
			$order["bonus_id"] = 0;
		}
	}
	else if (isset($_POST["bonus_sn"])) {
		$bonus_sn = trim($_POST["bonus_sn"]);
		$bonus = bonus_info(0, $bonus_sn);
		$now = gmtime();
		if (empty($bonus) || (0 < $bonus["user_id"]) || (0 < $bonus["order_id"]) || (cart_amount(true, $flow_type) < $bonus["min_goods_amount"]) || ($bonus["use_end_date"] < $now)) {
		}
		else {
			if (0 < $user_id) {
				$sql = "UPDATE " . $ecs->table("user_bonus") . " SET user_id = '$user_id' WHERE bonus_id = '{$bonus["bonus_id"]}' LIMIT 1";
				$db->query($sql);
			}

			$order["bonus_id"] = $bonus["bonus_id"];
			$order["bonus_sn"] = $bonus_sn;
		}
	}

	$cart_goods_list = cart_goods($flow_type, $done_cart_value, 1);
	$cart_goods = cart_goods($flow_type, $done_cart_value);

	if (empty($cart_goods)) {
		show_message($_LANG["no_goods_in_cart"], $_LANG["back_home"], "./", "warning");
	}

	if (($flow_type == CART_GENERAL_GOODS) && (cart_amount(true, CART_GENERAL_GOODS) < $_CFG["min_goods_amount"])) {
		show_message(sprintf($_LANG["goods_amount_not_enough"], price_format($_CFG["min_goods_amount"], false)));
	}

	foreach ($consignee as $key => $value ) {
		$order[$key] = addslashes($value);
	}

	foreach ($cart_goods as $val ) {
		if ($val["is_real"]) {
			$is_real_good = 1;
		}
	}

	$total = order_fee($order, $cart_goods, $consignee, 1, $done_cart_value, $pay_type, $cart_goods_list);
	$order["bonus"] = $total["bonus"];
	$order["goods_amount"] = $total["goods_price"];
	$order["discount"] = $total["discount"];
	$order["surplus"] = $total["surplus"];
	$order["tax"] = $total["tax"];
	$discount_amout = compute_discount_amount($done_cart_value);
	$temp_amout = $order["goods_amount"] - $discount_amout;

	if ($temp_amout <= 0) {
		$order["bonus_id"] = 0;
	}

	if (!empty($order["shipping_id"])) {
		if (count($_POST["shipping"]) == 1) {
			$shipping = shipping_info($order["shipping_id"]);
		}

		$order["shipping_name"] = addslashes($shipping["shipping_name"]);
	}

	$order["shipping_fee"] = $total["shipping_fee"];
	$order["insure_fee"] = $total["shipping_insure"];

	if (0 < $order["pay_id"]) {
		$payment = payment_info($order["pay_id"]);
		$order["pay_name"] = addslashes($payment["pay_name"]);
	}

	$order["pay_fee"] = $total["pay_fee"];
	$order["cod_fee"] = $total["cod_fee"];

	if (0 < $order["pack_id"]) {
		$pack = pack_info($order["pack_id"]);
		$order["pack_name"] = addslashes($pack["pack_name"]);
	}

	$order["pack_fee"] = $total["pack_fee"];

	if (0 < $order["card_id"]) {
		$card = card_info($order["card_id"]);
		$order["card_name"] = addslashes($card["card_name"]);
	}

	$order["card_fee"] = $total["card_fee"];
	$order["order_amount"] = number_format($total["amount"], 2, ".", "");
	if (isset($_SESSION["direct_shopping"]) && !empty($_SESSION["direct_shopping"])) {
		$where_flow = "?step=checkout&direct_shopping=" . $_SESSION["direct_shopping"];
	}

	if (($payment["pay_code"] == "balance") && (0 < $order["order_amount"])) {
		if (0 < $order["surplus"]) {
			$order["order_amount"] = $order["order_amount"] + $order["surplus"];
			$order["surplus"] = 0;
		}

		if (($user_info["user_money"] + $user_info["credit_line"]) < $order["order_amount"]) {
			show_message($_LANG["balance_not_enough"], $_LANG["back_up_page"], "flow.php" . $where_flow);
		}
		else if ($_SESSION["flow_type"] == CART_PRESALE_GOODS) {
			$order["surplus"] = $order["order_amount"];
			$order["pay_status"] = PS_PAYED_PART;
			$order["order_status"] = OS_CONFIRMED;
			$order["order_amount"] = ($order["goods_amount"] + $order["shipping_fee"] + $order["insure_fee"] + $order["tax"]) - $order["discount"] - $order["surplus"];
		}
		else {
			$order["surplus"] = $order["order_amount"];
			$order["order_amount"] = 0;
		}
	}

	if ($order["order_amount"] <= 0) {
		$order["order_status"] = OS_CONFIRMED;
		$order["confirm_time"] = gmtime();
		$order["pay_status"] = PS_PAYED;
		$order["pay_time"] = gmtime();
		$order["order_amount"] = 0;
	}

	$order["integral_money"] = $total["integral_money"];
	$order["integral"] = $total["integral"];

	if ($order["extension_code"] == "exchange_goods") {
		$order["integral_money"] = 0;
		$order["integral"] = $total["exchange_integral"];
	}

	$order["from_ad"] = (!empty($_SESSION["from_ad"]) ? $_SESSION["from_ad"] : "0");
	$order["referer"] = (!empty($_SESSION["referer"]) ? addslashes($_SESSION["referer"]) : "");

	if ($flow_type != CART_GENERAL_GOODS) {
		$order["extension_code"] = $_SESSION["extension_code"];
		$order["extension_id"] = $_SESSION["extension_id"];
	}

	$affiliate = unserialize($_CFG["affiliate"]);
	if (isset($affiliate["on"]) && ($affiliate["on"] == 1) && ($affiliate["config"]["separate_by"] == 1)) {
		$parent_id = get_affiliate();

		if ($user_id == $parent_id) {
			$parent_id = 0;
		}
	}
	else {
		if (isset($affiliate["on"]) && ($affiliate["on"] == 1) && ($affiliate["config"]["separate_by"] == 0)) {
			$parent_id = 0;
		}
		else {
			$parent_id = 0;
		}
	}

	$order["parent_id"] = $parent_id;
	$error_no = 0;

	do {
		$order["order_sn"] = get_order_sn();
		$GLOBALS["db"]->autoExecute($GLOBALS["ecs"]->table("order_info"), $order, "INSERT");
		$error_no = $GLOBALS["db"]->errno();
		if ((0 < $error_no) && ($error_no != 1062)) {
			exit($GLOBALS["db"]->errorMsg());
		}
	} while ($error_no == 1062);

	$new_order_id = $db->insert_id();
	$order["order_id"] = $new_order_id;
	$goodsIn = "";
	$cartValue = (!empty($done_cart_value) ? $done_cart_value : "");

	if (!empty($cartValue)) {
		$goodsIn = " and rec_id in($cartValue)";
	}

	$sql = "INSERT INTO " . $ecs->table("order_goods") . "( order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, model_attr, goods_attr_id, ru_id, shopping_fee, warehouse_id, area_id)  SELECT '$new_order_id', goods_id, goods_name, goods_sn, product_id, goods_number, market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, model_attr, goods_attr_id, ru_id, shopping_fee, warehouse_id, area_id FROM " . $ecs->table("cart") . " WHERE " . $sess_id . " AND rec_type = '$flow_type'" . $goodsIn;
	$db->query($sql);

	if ($order["extension_code"] == "auction") {
		$sql = "UPDATE " . $ecs->table("goods_activity") . " SET is_finished='2' WHERE act_id=" . $order["extension_id"];
		$db->query($sql);
	}

	if ((0 < $order["user_id"]) && (0 < $order["surplus"])) {
		log_account_change($order["user_id"], $order["surplus"] * -1, 0, 0, 0, sprintf($_LANG["pay_order"], $order["order_sn"]));
	}

	if ((0 < $order["user_id"]) && (0 < $order["integral"])) {
		if (!isset($_REQUEST["pay_type"]) && ($pay_type == 0)) {
			log_account_change($order["user_id"], 0, 0, 0, $order["integral"] * -1, sprintf($_LANG["pay_order"], $order["order_sn"]));
		}
	}

	if ((0 < $order["bonus_id"]) && (0 < $temp_amout)) {
		use_bonus($order["bonus_id"], $new_order_id);
	}

	if (($_CFG["use_storage"] == "1") && ($_CFG["stock_dec_time"] == SDT_PLACE)) {
		change_order_goods_storage($order["order_id"], true, SDT_PLACE, $_CFG["stock_dec_time"]);
	}

	if (count($cart_goods) <= 1) {
		if (1 <= $cart_goods[0]["ru_id"]) {
			$sql = "SELECT seller_email FROM " . $GLOBALS["ecs"]->table("seller_shopinfo") . " WHERE ru_id = '" . $cart_goods[0]["ru_id"] . "'";
			$service_email = $GLOBALS["db"]->getOne($sql);
		}
		else {
			$service_email = $_CFG["service_email"];
		}
	}
	else {
		$service_email = $_CFG["service_email"];
	}

	if ($order["order_amount"] <= 0) {
		$sql = "SELECT goods_id, goods_name, goods_number AS num FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE is_real = 0 AND extension_code = 'virtual_card' AND " . $sess_id . " AND rec_type = '$flow_type'";
		$res = $GLOBALS["db"]->getAll($sql);
		$virtual_goods = array();

		foreach ($res as $row ) {
			$virtual_goods["virtual_card"][] = array("goods_id" => $row["goods_id"], "goods_name" => $row["goods_name"], "num" => $row["num"]);
		}

		if ($virtual_goods && ($flow_type != CART_GROUP_BUY_GOODS)) {
			if (virtual_goods_ship($virtual_goods, $msg, $order["order_sn"], true)) {
				$sql = "SELECT COUNT(*) FROM " . $ecs->table("order_goods") . " WHERE order_id = '{$order["order_id"]}'  AND is_real = 1";

				if ($db->getOne($sql) <= 0) {
					update_order($order["order_id"], array("shipping_status" => SS_SHIPPED, "shipping_time" => gmtime()));

					if (0 < $order["user_id"]) {
						$user = user_info($order["user_id"]);
						$integral = integral_to_give($order);
						log_account_change($order["user_id"], 0, 0, intval($integral["rank_points"]), intval($integral["custom_points"]), sprintf($_LANG["order_gift_integral"], $order["order_sn"]));
						send_order_bonus($order["order_id"]);
					}
				}
			}
		}
	}

	clear_cart($flow_type, $done_cart_value);
	clear_all_files();
	$order["log_id"] = insert_pay_log($new_order_id, $order["order_amount"], PAY_ORDER);

	if (0 < $order["order_amount"]) {
		$payment = payment_info($order["pay_id"]);
		include_once ("includes/modules/payment/" . $payment["pay_code"] . ".php");
		$pay_obj = new $payment["pay_code"]();
		$pay_online = $pay_obj->get_code($order, unserialize_config($payment["pay_config"]));
		$order["pay_desc"] = $payment["pay_desc"];
		$smarty->assign("pay_online", $pay_online);
	}

	if (!empty($order["shipping_name"])) {
		$order["shipping_name"] = trim(stripcslashes($order["shipping_name"]));
	}

	if (isset($_SESSION["direct_shopping"])) {
		$smarty->assign("direct_shopping", $_SESSION["direct_shopping"]);
	}

	$smarty->assign("order", $order);
	$smarty->assign("total", $total);
	$smarty->assign("goods_list", $cart_goods);
	$smarty->assign("order_submit_back", sprintf($_LANG["order_submit_back"], $_LANG["back_home"], $_LANG["goto_user_center"]));
	user_uc_call("add_feed", array($order["order_id"], BUY_GOODS));
	unset($_SESSION["flow_consignee"]);
	unset($_SESSION["flow_order"]);
	unset($_SESSION["direct_shopping"]);
	$order_id = $order["order_id"];
	$row = get_main_order_info($order_id);
	$order_info = get_main_order_info($order_id, 1);
	$ru_id = explode(",", $order_info["all_ruId"]["ru_id"]);

	if (1 < count($ru_id)) {
		get_insert_order_goods_single($order_info, $row, $order_id);
	}

	$sql = "select count(order_id) from " . $ecs->table("order_info") . " where main_order_id = " . $order["order_id"];
	$child_order = $db->getOne($sql);

	if (1 < $child_order) {
		$child_order_info = get_child_order_info($order["order_id"]);
		$smarty->assign("child_order_info", $child_order_info);
	}

	$smarty->assign("pay_type", $pay_type);
	$smarty->assign("child_order", $child_order);
	$goods_buy_list = get_order_goods_buy_list($region_id, $area_id);
	$smarty->assign("goods_buy_list", $goods_buy_list);

	if (count($ru_id) == 1) {
		$sellerId = $ru_id[0];

		if ($sellerId == 0) {
			$sms_shop_mobile = $_CFG["sms_shop_mobile"];
		}
		else {
			$sql = "SELECT mobile FROM " . $ecs->table("seller_shopinfo") . " WHERE ru_id = '$sellerId'";
			$sms_shop_mobile = $db->getOne($sql);
		}

		if (($_CFG["sms_order_placed"] == "1") && ($sms_shop_mobile != "")) {
			include_once ("includes/cls_sms.php");
			$sms = new sms();
			$msg = ($order["pay_status"] == PS_UNPAYED ? $_LANG["order_placed_sms"] : $_LANG["order_placed_sms"] . "[" . $_LANG["sms_paid"] . "]");
			$sms->send($sms_shop_mobile, sprintf($msg, $order["consignee"], $order["mobile"]), "", 13, 1);
		}

		if ($_CFG["send_service_email"] && ($service_email != "")) {
			$tpl = get_mail_template("remind_of_new_order");
			$smarty->assign("order", $order);
			$smarty->assign("goods_list", $cart_goods);
			$smarty->assign("shop_name", $_CFG["shop_name"]);
			$smarty->assign("send_date", date($_CFG["time_format"]));
			$content = $smarty->fetch("str:" . $tpl["template_content"]);
			send_mail($_CFG["shop_name"], $service_email, $tpl["template_subject"], $content, $tpl["is_html"]);
		}
	}
}
else if ($_REQUEST["step"] == "ajax_update_cart") {
	require_once (ROOT_PATH . "includes/cls_json.php");
	$json = new JSON();
	$result = array("error" => 0, "message" => "");
	if (isset($_POST["rec_id"]) && isset($_POST["goods_number"])) {
		$key = $_POST["rec_id"];
		$val = $_POST["goods_number"];
		$warehouse_id = intval($_POST["warehouse_id"]);
		$area_id = intval($_POST["area_id"]);
		$val = intval(make_semiangle($val));
		if (($val <= 0) && !is_numeric($key)) {
			$result["error"] = 99;
			$result["message"] = "";
			exit($json->encode($result));
		}

		$sql = "SELECT `goods_id`, `goods_attr_id`,`product_id`, `extension_code`, `warehouse_id`, `area_id` FROM" . $GLOBALS["ecs"]->table("cart") . " WHERE rec_id='$key' AND " . $sess_id;
		$goods = $GLOBALS["db"]->getRow($sql);
		$shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wg.region_number as wg_number, wag.region_price, wag.region_promote_price, wag.region_number as wag_number, ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_goods") . " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' ";
		$leftJoin .= " left join " . $GLOBALS["ecs"]->table("warehouse_area_goods") . " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";
		$sql = "SELECT g.goods_name," . $shop_price . " g.model_price, g.model_inventory, g.model_attr, g.goods_number, g.group_number, c.group_id, c.extension_code, c.goods_name AS act_name FROM " . $GLOBALS["ecs"]->table("goods") . " AS g left join " . $GLOBALS["ecs"]->table("cart") . " AS c on g.goods_id =c.goods_id " . $leftJoin . "WHERE c.rec_id = '$key'";
		$row = $GLOBALS["db"]->getRow($sql);
		$xiangouInfo = get_purchasing_goods_info($goods["goods_id"]);

		if ($xiangouInfo["is_xiangou"] == 1) {
			$user_id = (!empty($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0);
			$start_date = $xiangouInfo["xiangou_start_date"];
			$end_date = $xiangouInfo["xiangou_end_date"];
			$orderGoods = get_for_purchasing_goods($start_date, $end_date, $goods["goods_id"], $user_id);
			$nowTime = gmtime();
			if (($start_date < $nowTime) && ($nowTime < $end_date)) {
				if ($xiangouInfo["xiangou_num"] <= $orderGoods["goods_number"]) {
					$max_num = $xiangouInfo["xiangou_num"] - $orderGoods["goods_number"];
					$result["message"] = "该" . $row["goods_name"] . "商品您已购买过，无法再购买";
					$sql = "UPDATE " . $GLOBALS["ecs"]->table("cart") . " SET goods_number = 0 WHERE rec_id='$key'";
					$GLOBALS["db"]->query($sql);
					$result["error"] = 1;
					exit($json->encode($result));
				}
				else if (0 < $xiangouInfo["xiangou_num"]) {
					if (($xiangouInfo["is_xiangou"] == 1) && ($xiangouInfo["xiangou_num"] < ($orderGoods["goods_number"] + $val))) {
						$max_num = $xiangouInfo["xiangou_num"] - $orderGoods["goods_number"];
						$result["message"] = "该" . $row["goods_name"] . "商品已经累计超过限购数量";
						$cart_Num = $xiangouInfo["xiangou_num"] - $orderGoods["goods_number"];
						$sql = "UPDATE " . $GLOBALS["ecs"]->table("cart") . " SET goods_number = '$cart_Num' WHERE rec_id='$key'";
						$GLOBALS["db"]->query($sql);
						$result["error"] = 1;
						$result["cart_Num"] = $cart_Num;
						$result["rec_id"] = $key;
						exit($json->encode($result));
					}
				}
			}
		}

		if ((0 < intval($GLOBALS["_CFG"]["use_storage"])) && ($goods["extension_code"] != "package_buy")) {
			if ($row["model_inventory"] == 1) {
				$row["goods_number"] = $row["wg_number"];
			}
			else if ($row["model_inventory"] == 2) {
				$row["goods_number"] = $row["wag_number"];
			}

			$goods["product_id"] = trim($goods["product_id"]);

			if (!empty($goods["product_id"])) {
				if ($row["model_attr"] == 1) {
					$table_products = "products_warehouse";
				}
				else if ($row["model_attr"] == 2) {
					$table_products = "products_area";
				}
				else {
					$table_products = "products";
				}

				$sql = "SELECT product_number FROM " . $GLOBALS["ecs"]->table($table_products) . " WHERE goods_id = '" . $goods["goods_id"] . "' and product_id = '" . $goods["product_id"] . "' LIMIT 1";
				$product_number = $GLOBALS["db"]->getOne($sql);

				if ($product_number < $val) {
					$result["error"] = 2;
					$result["message"] = sprintf($GLOBALS["_LANG"]["stock_insufficiency"], $row["goods_name"], $product_number, $product_number);
					exit($json->encode($result));
				}
			}
			else if ($row["goods_number"] < $val) {
				$result["error"] = 1;
				$result["message"] = sprintf($GLOBALS["_LANG"]["stock_insufficiency"], $row["goods_name"], $row["goods_number"], $row["goods_number"]);
				exit($json->encode($result));
			}
		}
		else {
			if ((0 < intval($GLOBALS["_CFG"]["use_storage"])) && ($goods["extension_code"] == "package_buy")) {
				if (judge_package_stock($goods["goods_id"], $val)) {
					$result["error"] = 3;
					$result["message"] = $GLOBALS["_LANG"]["package_stock_insufficiency"];
					exit($json->encode($result));
				}
			}
		}

		$sql = "SELECT b.goods_number,b.rec_id\r\n                FROM " . $GLOBALS["ecs"]->table("cart") . " a, " . $GLOBALS["ecs"]->table("cart") . " b\r\n                WHERE a.rec_id = '$key'\r\n                AND " . $a_sess . "\r\n                AND a.extension_code <>'package_buy'\r\n                AND b.parent_id = a.goods_id\r\n                AND " . $b_sess;
		$offers_accessories_res = $GLOBALS["db"]->getAll($sql);

		if (0 < $val) {
			if ((0 < $row["group_number"]) && ($row["group_number"] < $val) && !empty($row["group_id"])) {
				$result["error"] = 1;
				$result["message"] = sprintf($GLOBALS["_LANG"]["group_stock_insufficiency"], $row["goods_name"], $row["group_number"], $row["group_number"]);
				exit($json->encode($result));
			}

			for ($i = 0; $i < count($offers_accessories_res); $i++) {
				$sql = "update " . $GLOBALS["ecs"]->table("cart") . " set goods_number = '$val' WHERE " . $sess_id . "AND rec_id ='" . $offers_accessories_res[$i]["rec_id"] . "' AND group_id = '" . $row["group_id"] . "'";
				$GLOBALS["db"]->query($sql);
			}

			if ($goods["extension_code"] == "package_buy") {
				$sql = "UPDATE " . $GLOBALS["ecs"]->table("cart") . " SET goods_number= '$val' WHERE rec_id='$key' AND " . $sess_id;
			}
			else {
				$attr_id = (empty($goods["goods_attr_id"]) ? array() : explode(",", $goods["goods_attr_id"]));
				$goods_price = get_final_price($goods["goods_id"], $val, true, $attr_id, $_POST["warehouse_id"], $_POST["area_id"]);
				$sql = "UPDATE " . $GLOBALS["ecs"]->table("cart") . " SET goods_number= '$val', goods_price = '$goods_price' WHERE rec_id='$key' AND " . $sess_id;
			}
		}
		else {
			for ($i = 0; $i < count($offers_accessories_res); $i++) {
				$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE " . $sess_id . "AND rec_id ='" . $offers_accessories_res[$i]["rec_id"] . "'";
				$GLOBALS["db"]->query($sql);
			}

			$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE rec_id='$key' AND " . $sess_id;
		}

		$GLOBALS["db"]->query($sql);
		$result["rec_id"] = $key;
		$result["goods_number"] = $val;
		$result["goods_subtotal"] = "";
		$result["total_desc"] = "";

		if ($row["extension_code"] == "package_buy") {
			$result["ext_info"] = $GLOBALS["db"]->getOne("SELECT ext_info FROM " . $GLOBALS["ecs"]->table("goods_activity") . " WHERE act_name = '" . $row["act_name"] . "'");
			$ext_arr = unserialize($result["ext_info"]);
			unset($result["ext_info"]);
			$goods_price = $ext_arr["package_price"];
		}

		$result["goods_price"] = price_format($goods_price);
		$result["cart_info"] = insert_cart_info();
		$cValue = htmlspecialchars($_POST["cValue"]);
		$cart_goods = get_cart_goods($cValue, 0, $warehouse_id, $area_id);

		foreach ($cart_goods["goods_list"] as $goods ) {
			if ($goods["rec_id"] == $key) {
				if (0 < $goods["dis_amount"]) {
					$result["goods_subtotal"] = $goods["subtotal"] . "<br/><font style='color:#F60'>(优惠：" . $goods["discount_amount"] . ")</font>";
				}
				else {
					$result["goods_subtotal"] = $goods["subtotal"];
				}

				$result["rec_goods"] = $goods["goods_id"];
				break;
			}
		}

		$discount = compute_discount(3, $key);
		$save_total_amount = $cart_goods["total"]["subtotal_discount_amount"];
		$result["save_total_amount"] = price_format($save_total_amount + $discount["discount"]);
		$result["group"] = array();
		$subtotal_number = 0;

		foreach ($cart_goods["goods_list"] as $goods ) {
			$subtotal_number += $goods["goods_number"];
			if ((0 < $goods["parent_id"]) && ($result["rec_goods"] == $goods["parent_id"])) {
				if ($goods["rec_id"] != $key) {
					$result["group"][$goods["rec_id"]]["rec_group"] = $goods["group_id"] . "_" . $goods["rec_id"];
					$result["group"][$goods["rec_id"]]["rec_group_number"] = $goods["goods_number"];
					$result["group"][$goods["rec_id"]]["rec_group_talId"] = $goods["group_id"] . "_" . $goods["rec_id"] . "_subtotal";
					$result["group"][$goods["rec_id"]]["rec_group_subtotal"] = price_format($goods["goods_amount"], false);
				}
			}
		}

		$result["subtotal_number"] = $subtotal_number;

		if ($result["group"]) {
			$result["group"] = array_values($result["group"]);
		}

		$result["flow_info"] = insert_flow_info($cart_goods["total"]["goods_price"], $cart_goods["total"]["market_price"], $cart_goods["total"]["saving"], $cart_goods["total"]["save_rate"], $cart_goods["total"]["goods_amount"], $cart_goods["total"]["real_goods_count"]);
		$act_id = (isset($_POST["favourable_id"]) ? intval($_POST["favourable_id"]) : 0);

		if (0 < $act_id) {
			$favourable = favourable_info($act_id);
			$favourable_available = favourable_available($favourable);

			if (!$favourable_available) {
				$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE " . $sess_id . " AND is_gift = '$act_id'";
				$GLOBALS["db"]->query($sql);
			}

			$cart_fav_box = cart_favourable_box($act_id);
			$smarty->assign("activity", $cart_fav_box);
			$result["favourable_box_content"] = $smarty->fetch("library/cart_favourable_box.lbi");
			$result["act_id"] = $act_id;
		}

		exit($json->encode($result));
	}
	else {
		$result["error"] = 100;
		$result["message"] = "";
		exit($json->encode($result));
	}
}
else if ($_REQUEST["step"] == "ajax_cart_goods_amount") {
	require_once (ROOT_PATH . "includes/cls_json.php");
	$json = new JSON();
	$result = array("error" => 0, "message" => "");
	$rec_id = (!empty($_REQUEST["rec_id"]) ? htmlspecialchars($_REQUEST["rec_id"]) : "");
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$cart_goods = cart_goods($flow_type, $rec_id);
	$goods_amount = get_cart_check_goods($cart_goods, $rec_id);
	$result["error"] = 0;
	$result["message"] = "";
	$result["goods_amount"] = $goods_amount["subtotal_amount"];
	$result["subtotal_number"] = $goods_amount["subtotal_number"];
	$favourable_list = favourable_list($_SESSION["user_rank"]);
	usort($favourable_list, "cmp_favourable");
	$smarty->assign("favourable_list", $favourable_list);
	$result["favourable_list_content"] = $smarty->fetch("library/cart_favourable_list.lbi");
	$discount = compute_discount(3, $rec_id);
	$favour_name = (empty($discount["name"]) ? "" : join(",", $discount["name"]));
	$result["discount"] = $discount["discount"];
	$result["your_discount"] = "<div class=\"ncc-all-account\" style=\" font-size:14px; position:relative; bottom:-10px;\">" . sprintf($_LANG["your_discount"], $favour_name, price_format($discount["discount"])) . "</div>";
	$save_amount = 0;

	foreach ($cart_goods as $key => $row ) {
		$save_amount += $row["dis_amount"];
	}

	$save_total_amount = price_format($save_amount + $discount["discount"]);
	$result["save_total_amount"] = $save_total_amount;
	$is_gift = 0;
	$act_id = (isset($_POST["favourable_id"]) ? intval($_POST["favourable_id"]) : 0);
	$recid = (isset($_POST["recid"]) ? intval($_POST["recid"]) : 0);

	if ($act_id) {
		$sql = "SELECT COUNT(*) FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE " . $sess_id . " AND is_gift = '$act_id' LIMIT 1";
		$is_gift = $GLOBALS["db"]->getOne($sql);
		$sql = "DELETE FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE " . $sess_id . " AND is_gift = '$act_id'";
		$GLOBALS["db"]->query($sql);
		$cart_fav_box = cart_favourable_box($act_id);
		$smarty->assign("activity", $cart_fav_box);
		$result["favourable_box_content"] = $smarty->fetch("library/cart_favourable_box.lbi");
	}

	$result["act_id"] = $act_id;
	$result["is_gift"] = $is_gift;
	$result["recid"] = $recid;
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "update_cart") {
	if (isset($_POST["goods_number"]) && is_array($_POST["goods_number"])) {
		flow_update_cart($_POST["goods_number"]);
	}

	show_message($_LANG["update_cart_notice"], $_LANG["back_to_cart"], "flow.php");
	exit();
}
else if ($_REQUEST["step"] == "pdf") {
	$consignee = get_consignee($_SESSION["user_id"]);
	$userinfo = get_user_info($consignee["user_id"]);
	$consignee["user_name"] = $userinfo["user_name"];
	$_SESSION["flow_consignee"] = $consignee;
	$smarty->assign("consignee", $consignee);
	$orderid = $_REQUEST["order"];
	$order_inf = order_info($orderid);
	$order_inf["add_time"] = local_date("Y-m-d H:i:s", $order_inf["add_time"]);
	$order_goods = get_order_pdf_goods($orderid);
	$shop_info = get_order_ruid($orderid);
	ob_start();
	include ("./html/order_info.php");
	$content = ob_get_clean();
	require_once ("./html/html2pdf.class.php");
	ob_clean();

	try {
		$html2pdf = new HTML2PDF("P", "A3", "fr");
		$html2pdf->setDefaultFont("stsongstdlight");
		$html2pdf->writeHTML($content, isset($_GET["vuehtml"]));
		$html2pdf->Output("orderDdf_" . $orderid . ".pdf");
	}
	catch (HTML2PDF_exception $e) {
		echo $e;
		exit();
	}
}
else if ($_REQUEST["step"] == "drop_goods") {
	if (!empty($_GET["sig"])) {
		$n_id = explode("@", $_GET["id"]);

		foreach ($n_id as $val ) {
			flow_drop_cart_goods($val);
		}
	}
	else {
		$rec_id = intval($_GET["id"]);
		flow_drop_cart_goods($rec_id);
	}

	ecs_header("Location: flow.php\n");
	exit();
}
else if ($_REQUEST["step"] == "add_favourable") {
	include_once ("includes/cls_json.php");
	$json = new JSON();
	$select_gift = explode(",", $_POST["select_gift"]);
	$act_id = intval($_POST["act_id"]);
	$favourable = favourable_info($act_id);

	if (empty($favourable)) {
		$result["error"] = 1;
		$result["message"] = $_LANG["favourable_not_exist"];
		exit($json->encode($result));
	}

	if (!favourable_available($favourable)) {
		$result["error"] = 2;
		$result["message"] = $_LANG["favourable_not_available"];
		exit($json->encode($result));
	}

	$cart_favourable = cart_favourable();

	if (favourable_used($favourable, $cart_favourable)) {
		$result["error"] = 3;
		$result["message"] = $_LANG["favourable_used"];
		exit($json->encode($result));
	}

	if ($favourable["act_type"] == FAT_GOODS) {
		if (empty($select_gift)) {
			$result["error"] = 4;
			$result["message"] = $_LANG["pls_select_gift"];
			exit($json->encode($result));
		}

		$sql = "SELECT goods_name FROM " . $ecs->table("cart") . " WHERE " . $sess_id . " AND rec_type = '" . CART_GENERAL_GOODS . "' AND is_gift = '$act_id' AND goods_id " . db_create_in($select_gift);
		$gift_name = $db->getCol($sql);

		if (!empty($gift_name)) {
			$result["error"] = 5;
			$result["message"] = sprintf($_LANG["gift_in_cart"], join(",", $gift_name));
			exit($json->encode($result));
		}

		$count = (isset($cart_favourable[$act_id]) ? $cart_favourable[$act_id] : 0);
		if ((0 < $favourable["act_type_ext"]) && ($favourable["act_type_ext"] < ($count + count($select_gift)))) {
			$result["error"] = 6;
			$result["message"] = $_LANG["gift_count_exceed"];
			exit($json->encode($result));
		}

		foreach ($favourable["gift"] as $gift ) {
			if (in_array($gift["id"], $select_gift)) {
				add_gift_to_cart($act_id, $gift["id"], $gift["price"]);
			}
		}

		$smarty->assign("activity", cart_favourable_box($act_id));
		$result["content"] = $smarty->fetch("library/cart_favourable_box.lbi");
		$result["act_id"] = $act_id;
	}
	else if ($favourable["act_type"] == FAT_DISCOUNT) {
		add_favourable_to_cart($act_id, $favourable["act_name"], (cart_favourable_amount($favourable) * (100 - $favourable["act_type_ext"])) / 100);
	}
	else if ($favourable["act_type"] == FAT_PRICE) {
		add_favourable_to_cart($act_id, $favourable["act_name"], $favourable["act_type_ext"]);
	}

	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "clear") {
	$sql = "DELETE FROM " . $ecs->table("cart") . " WHERE " . $sess_id;
	$db->query($sql);
	ecs_header("Location:./\n");
}
else if ($_REQUEST["step"] == "drop_to_collect") {
	$rec_id = intval($_GET["id"]);

	if (0 < $_SESSION["user_id"]) {
		$goods_id = $db->getOne("SELECT  goods_id FROM " . $ecs->table("cart") . " WHERE rec_id = '$rec_id' AND " . $sess_id);
		$count = $db->getOne("SELECT goods_id FROM " . $ecs->table("collect_goods") . " WHERE user_id = '{$_SESSION["user_id"]}' AND goods_id = '$goods_id'");

		if (empty($count)) {
			$time = gmtime();
			$sql = "INSERT INTO " . $GLOBALS["ecs"]->table("collect_goods") . " (user_id, goods_id, add_time)VALUES ('{$_SESSION["user_id"]}', '$goods_id', '$time')";
			$db->query($sql);
		}
	}

	flow_drop_cart_goods($rec_id);
	ecs_header("Location: flow.php\n");
	exit();
}
else if ($_REQUEST["step"] == "validate_bonus") {
	$bonus_sn = trim($_REQUEST["bonus_sn"]);

	if (is_numeric($bonus_sn)) {
		$bonus = bonus_info(0, $bonus_sn, $_SESSION["cart_value"]);
	}
	else {
		$bonus = array();
	}

	$bonus_kill = price_format($bonus["type_money"], false);
	include_once ("includes/cls_json.php");
	$result = array("error" => "", "content" => "");
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$consignee = get_consignee($_SESSION["user_id"]);
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		$result["error"] = $_LANG["no_goods_in_cart"];
	}
	else {
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();
		if (((!empty($bonus) && ($bonus["user_id"] == $_SESSION["user_id"])) || ((0 < $bonus["type_money"]) && empty($bonus["user_id"]))) && ($bonus["order_id"] <= 0)) {
			$now = gmtime();

			if ($bonus["use_end_date"] < $now) {
				$order["bonus_id"] = "";
				$result["error"] = $_LANG["bonus_use_expire"];
			}
			else {
				$order["bonus_id"] = $bonus["bonus_id"];
				$order["bonus_sn"] = $bonus_sn;
			}
		}
		else {
			$order["bonus_id"] = "";
			$result["error"] = $_LANG["invalid_bonus"];
		}

		$total = order_fee($order, $cart_goods, $consignee);

		if ($total["goods_price"] < $bonus["min_goods_amount"]) {
			$order["bonus_id"] = "";
			$result["error"] = sprintf($_LANG["bonus_min_amount_error"], price_format($bonus["min_goods_amount"], false));
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

		$result["content"] = $smarty->fetch("library/order_total.lbi");
	}

	$json = new JSON();
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "add_package_to_cart") {
	include_once ("includes/cls_json.php");
	$_POST["package_info"] = json_str_iconv($_POST["package_info"]);
	$result = array("error" => 0, "message" => "", "content" => "", "package_id" => "");
	$json = new JSON();

	if (empty($_POST["package_info"])) {
		$result["error"] = 1;
		exit($json->encode($result));
	}

	$package = $json->decode($_POST["package_info"]);

	if ($_CFG["one_step_buy"] == "1") {
		clear_cart();
	}

	if (!is_numeric($package->number) || (intval($package->number) <= 0)) {
		$result["error"] = 1;
		$result["message"] = $_LANG["invalid_number"];
	}
	else if (add_package_to_cart($package->package_id, $package->number, $package->warehouse_id, $package->area_id)) {
		if (2 < $_CFG["cart_confirm"]) {
			$result["message"] = "";
		}
		else {
			$result["message"] = ($_CFG["cart_confirm"] == 1 ? $_LANG["addto_cart_success_1"] : $_LANG["addto_cart_success_2"]);
		}

		$result["content"] = insert_cart_info();
		$result["one_step_buy"] = $_CFG["one_step_buy"];
	}
	else {
		$result["message"] = $err->last_message();
		$result["error"] = $err->error_no;
		$result["package_id"] = stripslashes($package->package_id);
	}

	$confirm_type = (isset($package->confirm_type) ? $package->confirm_type : 0);

	if (0 < $confirm_type) {
		$result["confirm_type"] = $confirm_type;
	}
	else {
		$result["confirm_type"] = (!empty($_CFG["cart_confirm"]) ? $_CFG["cart_confirm"] : 2);
	}

	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "show_gift_div") {
	include_once ("includes/cls_json.php");
	$json = new JSON();
	$favourable_id = $_POST["favourable_id"];
	$favourable = favourable_list($_SESSION["user_rank"], -1, $favourable_id);
	$activity = $favourable[0];
	$activity["act_type_ext"] = intval($activity["act_type_ext"]);
	$cart_favourable_num = cart_favourable();
	$activity["cart_favourable_gift_num"] = (!empty($cart_favourable_num[$favourable_id]) ? intval($cart_favourable_num[$favourable_id]) : 0);
	$activity["favourable_used"] = favourable_used($activity, $cart_favourable_num);
	$activity["left_gift_num"] = intval($activity["act_type_ext"]) - (empty($cart_favourable_num[$activity["act_id"]]) ? 0 : intval($cart_favourable_num[$activity["act_id"]]));

	foreach ($activity["gift"] as $key => $row ) {
		$activity["act_gift_list"][$key] = $row;
		$activity["act_gift_list"][$key]["url"] = build_uri("goods", array("gid" => $row["id"]), $row["name"]);
	}

	$smarty->assign("activity", $activity);
	$result["content"] = $smarty->fetch("library/cart_gift_box.lbi");
	$result["act_id"] = $favourable_id;
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "edit_Consignee") {
	include ("includes/cls_json.php");
	$json = new JSON();
	$res = array("message" => "", "result" => "", "qty" => 1);
	$address_id = (isset($_REQUEST["address_id"]) ? intval($_REQUEST["address_id"]) : 0);

	if ($address_id == 0) {
		$consignee["country"] = 1;
		$consignee["province"] = 0;
		$consignee["city"] = 0;
	}

	$consignee = get_update_flow_consignee($address_id);
	$smarty->assign("consignee", $consignee);
	$smarty->assign("country_list", get_regions());
	$smarty->assign("please_select", "请选择");
	$province_list = get_regions_log(1, $consignee["country"]);
	$city_list = get_regions_log(2, $consignee["province"]);
	$district_list = get_regions_log(3, $consignee["city"]);
	$smarty->assign("province_list", $province_list);
	$smarty->assign("city_list", $city_list);
	$smarty->assign("district_list", $district_list);

	if ($_SESSION["user_id"] <= 0) {
		$result["error"] = 2;
		$result["message"] = "您尚未登录，请登录您的账号！";
	}
	else {
		$result["error"] = 0;
		$result["content"] = $smarty->fetch("library/consignee_new.lbi");
	}

	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "insert_Consignee") {
	include ("includes/cls_json.php");
	$json = new JSON();
	$result = array("message" => "", "result" => "", "error" => 0);
	$_REQUEST["csg"] = (isset($_REQUEST["csg"]) ? json_str_iconv($_REQUEST["csg"]) : "");
	$csg = $json->decode($_REQUEST["csg"]);
	$consignee = array("address_id" => empty($csg->address_id) ? 0 : intval($csg->address_id), "consignee" => empty($csg->consignee) ? "" : compile_str(trim($csg->consignee)), "country" => empty($csg->country) ? 0 : intval($csg->country), "province" => empty($csg->province) ? 0 : intval($csg->province), "city" => empty($csg->city) ? 0 : intval($csg->city), "district" => empty($csg->district) ? 0 : intval($csg->district), "email" => empty($csg->email) ? "" : compile_str($csg->email), "address" => empty($csg->address) ? "" : compile_str($csg->address), "zipcode" => empty($csg->zipcode) ? "" : compile_str(make_semiangle(trim($csg->zipcode))), "tel" => empty($csg->tel) ? "" : compile_str(make_semiangle(trim($csg->tel))), "mobile" => empty($csg->mobile) ? "" : compile_str(make_semiangle(trim($csg->mobile))), "sign_building" => empty($csg->sign_building) ? "" : compile_str($csg->sign_building), "best_time" => empty($csg->best_time) ? "" : compile_str($csg->best_time));

	if ($consignee) {
		setcookie("province", $consignee["province"], gmtime() + (3600 * 24 * 30));
		setcookie("city", $consignee["city"], gmtime() + (3600 * 24 * 30));
		setcookie("district", $consignee["district"], gmtime() + (3600 * 24 * 30));
		$flow_warehouse = get_warehouse_goods_region($consignee["province"]);
		setcookie("area_region", $flow_warehouse["region_id"], gmtime() + (3600 * 24 * 30));
		setcookie("flow_region", $flow_warehouse["region_id"], gmtime() + (3600 * 24 * 30));
	}

	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);

	if ($result["error"] == 0) {
		if (0 < $_SESSION["user_id"]) {
			include_once (ROOT_PATH . "includes/lib_transaction.php");

			if (0 < $consignee["address_id"]) {
				$addressId = " and address_id <> '" . $consignee["address_id"] . "' ";
			}

			$sql = "SELECT COUNT(*) FROM " . $ecs->table("user_address") . " WHERE consignee = '" . $consignee["consignee"] . "' AND country = '" . $consignee["country"] . "' AND province = '" . $consignee["province"] . "' AND city = '" . $consignee["city"] . "' AND district = '" . $consignee["district"] . "' AND user_id = '" . $_SESSION["user_id"] . "'" . $addressId;
			$row = $db->getOne($sql);

			if (0 < $row) {
				$result["error"] = 4;
				$result["message"] = "配送信息已存在";
			}
			else {
				$result["error"] = 0;
				$consignee["user_id"] = $_SESSION["user_id"];
				$saveConsignee = save_consignee($consignee, true);
				$sql = "select address_id from " . $GLOBALS["ecs"]->table("users") . " where user_id = '" . $_SESSION["user_id"] . "'";
				$user_address_id = $GLOBALS["db"]->getOne($sql);

				if (0 < $user_address_id) {
					$consignee["address_id"] = $user_address_id;
				}

				$sql = "select count(*) from " . $GLOBALS["ecs"]->table("user_address") . " where user_id = '" . $_SESSION["user_id"] . "'";
				$count = $GLOBALS["db"]->getOne($sql);

				if ($_CFG["auditStatus"] == 1) {
					if ($count <= $_CFG["auditCount"]) {
						$result["message"] = "";
					}
					else if ($saveConsignee["update"] == false) {
						if (0 < $consignee["address_id"]) {
							$result["message"] = "信息编辑成功，待审核...";
						}
						else {
							$result["message"] = "信息添加成功，待审核...";
						}
					}
					else {
						$result["message"] = "";
					}
				}
				else if (0 < $consignee["address_id"]) {
					$sql = "UPDATE " . $GLOBALS["ecs"]->table("users") . " SET address_id = '" . $consignee["address_id"] . "'  WHERE user_id = '" . $consignee["user_id"] . "'";
					$GLOBALS["db"]->query($sql);
					$_SESSION["flow_consignee"] = $consignee;
					$result["message"] = "信息编辑成功";
				}
				else {
					$result["message"] = "信息添加成功";
				}
			}

			$user_address = get_order_user_address_list($_SESSION["user_id"]);
			$smarty->assign("user_address", $user_address);
			$consignee["province_name"] = get_goods_region_name($consignee["province"]);
			$consignee["city_name"] = get_goods_region_name($consignee["city"]);
			$consignee["district_name"] = get_goods_region_name($consignee["district"]);
			$consignee["consignee_address"] = $consignee["province_name"] . $consignee["city_name"] . $consignee["district_name"] . $consignee["address"];
			$smarty->assign("consignee", $consignee);
			$result["content"] = $smarty->fetch("library/consignee_flow.lbi");
			$region_id = get_province_id_warehouse($consignee["province"]);
			$area_info = get_area_info($consignee["province"]);
			$smarty->assign("warehouse_id", $region_id);
			$smarty->assign("area_id", $area_info["region_id"]);
			$cart_goods_list = cart_goods($flow_type, $_SESSION["cart_value"], 1, $region_id, $area_info["region_id"]);
			$smarty->assign("goods_list", cart_by_favourable($cart_goods_list));
			$result["goods_list"] = $smarty->fetch("library/flow_cart_goods.lbi");
			$order = flow_order_info();
			$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
			$cart_goods_number = get_buy_cart_goods_number($flow_type, $_SESSION["cart_value"]);
			$smarty->assign("cart_goods_number", $cart_goods_number);
			$total = order_fee($order, $cart_goods, $consignee, 0, $_SESSION["cart_value"], 0, $cart_goods_list);
			$smarty->assign("total", $total);
			$result["order_total"] = $smarty->fetch("library/order_total.lbi");
		}
		else {
			$result["error"] = 2;
			$result["message"] = "您尚未登录，请登录您的账号！";
		}
	}

	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "delete_Consignee") {
	include ("includes/cls_json.php");
	$json = new JSON();
	$res = array("message" => "", "result" => "", "qty" => 1);
	$result["error"] = 0;
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$address_id = (isset($_REQUEST["address_id"]) ? intval($_REQUEST["address_id"]) : 0);
	$sql = "delete from " . $ecs->table("user_address") . " where address_id = '$address_id'";
	$db->query($sql);
	$consignee = $_SESSION["flow_consignee"];
	$smarty->assign("consignee", $consignee);

	if ($consignee) {
		setcookie("province", $consignee["province"], gmtime() + (3600 * 24 * 30));
		setcookie("city", $consignee["city"], gmtime() + (3600 * 24 * 30));
		setcookie("district", $consignee["district"], gmtime() + (3600 * 24 * 30));
		$flow_warehouse = get_warehouse_goods_region($consignee["province"]);
		setcookie("area_region", $flow_warehouse["region_id"], gmtime() + (3600 * 24 * 30));
		setcookie("flow_region", $flow_warehouse["region_id"], gmtime() + (3600 * 24 * 30));
	}

	$region_id = get_province_id_warehouse($consignee["province"]);
	$area_info = get_area_info($consignee["province"]);
	$smarty->assign("warehouse_id", $region_id);
	$smarty->assign("area_id", $area_info["region_id"]);
	$user_address = get_order_user_address_list($_SESSION["user_id"]);
	$smarty->assign("user_address", $user_address);

	if (!$user_address) {
		$consignee = array("province" => 0, "city" => 0);
		$smarty->assign("country_list", get_regions());
		$smarty->assign("please_select", "请选择");
		$province_list = get_regions_log(1, 1);
		$city_list = get_regions_log(2, $consignee["province"]);
		$district_list = get_regions_log(3, $consignee["city"]);
		$smarty->assign("province_list", $province_list);
		$smarty->assign("city_list", $city_list);
		$smarty->assign("district_list", $district_list);
		$smarty->assign("consignee", $consignee);
		$result["error"] = 2;
		$result["content"] = $smarty->fetch("library/consignee_new.lbi");
	}
	else {
		$result["content"] = $smarty->fetch("library/consignee_flow.lbi");
	}

	$consignee = get_consignee($_SESSION["user_id"]);

	if (empty($consignee)) {
		$consignee = array("country" => "", "province" => "", "city" => "", "district" => "", "province_name" => "", "city_name" => "", "district_name" => "", "address" => "");
	}

	$consignee["province_name"] = get_goods_region_name($consignee["province"]);
	$consignee["city_name"] = get_goods_region_name($consignee["city"]);
	$consignee["district_name"] = get_goods_region_name($consignee["district"]);
	$consignee["region"] = $consignee["province_name"] . "&nbsp;" . $consignee["city_name"] . "&nbsp;" . $consignee["district_name"];
	$_SESSION["flow_consignee"] = $consignee;
	$consignee["consignee_address"] = $consignee["province_name"] . $consignee["city_name"] . $consignee["district_name"] . $consignee["address"];
	$smarty->assign("consignee", $consignee);
	$cart_goods_list = cart_goods($flow_type, $_SESSION["cart_value"], 1, $region_id, $area_info["region_id"]);
	$smarty->assign("goods_list", cart_by_favourable($cart_goods_list));
	$result["goods_list"] = $smarty->fetch("library/flow_cart_goods.lbi");
	$order = flow_order_info();
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	$cart_goods_number = get_buy_cart_goods_number($flow_type, $_SESSION["cart_value"]);
	$smarty->assign("cart_goods_number", $cart_goods_number);
	$total = order_fee($order, $cart_goods, $consignee, 0, $_SESSION["cart_value"], 0, $cart_goods_list);
	$smarty->assign("total", $total);
	$result["order_total"] = $smarty->fetch("library/order_total.lbi");
	exit($json->encode($result));
}
else if ($_REQUEST["step"] == "edit_consignee_checked") {
	include ("includes/cls_json.php");
	$flow_type = (isset($_SESSION["flow_type"]) ? intval($_SESSION["flow_type"]) : CART_GENERAL_GOODS);
	$_SESSION["cart_value"] = (isset($_SESSION["cart_value"]) ? $_SESSION["cart_value"] : "");
	$json = new JSON();
	$res = array("msg" => "", "result" => "", "qty" => 1);
	$result["error"] = 0;
	$address_id = (isset($_REQUEST["address_id"]) ? intval($_REQUEST["address_id"]) : 0);
	$_SESSION["merchants_shipping"] = array();
	$consignee = get_update_flow_consignee($address_id);
	$_SESSION["flow_consignee"] = $consignee;

	if ($consignee) {
		setcookie("province", $consignee["province"], gmtime() + (3600 * 24 * 30));
		setcookie("city", $consignee["city"], gmtime() + (3600 * 24 * 30));
		setcookie("district", $consignee["district"], gmtime() + (3600 * 24 * 30));
		$flow_warehouse = get_warehouse_goods_region($consignee["province"]);
		setcookie("area_region", $flow_warehouse["region_id"], gmtime() + (3600 * 24 * 30));
		setcookie("flow_region", $flow_warehouse["region_id"], gmtime() + (3600 * 24 * 30));
	}

	$smarty->assign("warehouse_id", $region_id);
	$smarty->assign("area_id", $area_info["region_id"]);
	$consignee["province_name"] = get_goods_region_name($consignee["province"]);
	$consignee["city_name"] = get_goods_region_name($consignee["city"]);
	$consignee["district_name"] = get_goods_region_name($consignee["district"]);
	$consignee["consignee_address"] = $consignee["province_name"] . $consignee["city_name"] . $consignee["district_name"] . $consignee["address"];
	$smarty->assign("consignee", $consignee);
	$cart_goods_number = get_buy_cart_goods_number($flow_type, $_SESSION["cart_value"]);
	$smarty->assign("cart_goods_number", $cart_goods_number);
	$user_address = get_order_user_address_list($_SESSION["user_id"]);
	$smarty->assign("user_address", $user_address);
	$result["content"] = $smarty->fetch("library/consignee_flow.lbi");
	$cart_goods = cart_goods($flow_type, $_SESSION["cart_value"]);
	if (empty($cart_goods) || !check_consignee_info($consignee, $flow_type)) {
		if (empty($cart_goods)) {
			$result["error"] = 1;
			$result["msg"] = "购物车为空或未登录！";
		}
		else if (!check_consignee_info($consignee, $flow_type)) {
			$result["error"] = 2;
			$result["msg"] = "请选择或填写收货地址！";
		}
	}
	else {
		$region_id = get_province_id_warehouse($consignee["province"]);
		$area_info = get_area_info($consignee["province"]);
		$smarty->assign("config", $_CFG);
		$order = flow_order_info();
		$cart_goods_list = cart_goods($flow_type, $_SESSION["cart_value"], 1, $region_id, $area_info["region_id"]);
		$smarty->assign("goods_list", cart_by_favourable($cart_goods_list));
		$total = order_fee($order, $cart_goods, $consignee, 0, $_SESSION["cart_value"], 0, $cart_goods_list);
		$smarty->assign("total", $total);

		if ($flow_type == CART_GROUP_BUY_GOODS) {
			$smarty->assign("is_group_buy", 1);
		}

		$result["goods_list"] = $smarty->fetch("library/flow_cart_goods.lbi");
		$result["order_total"] = $smarty->fetch("library/order_total.lbi");
	}

	exit($json->encode($result));
}
else {
	$_SESSION["flow_type"] = CART_GENERAL_GOODS;

	if ($_CFG["one_step_buy"] == "1") {
		ecs_header("Location: flow.php?step=checkout\n");
		exit();
	}

	require (ROOT_PATH . "/includes/lib_area.php");
	$area_info = get_area_info($province_id);
	$area_id = $area_info["region_id"];
	$smarty->assign("area_id", $area_id);
	$smarty->assign("flow_region", $_COOKIE["flow_region"]);
	$cart_goods = get_cart_goods("", 1, $_COOKIE["flow_region"], $area_id);
	$merchant_goods = $cart_goods["goods_list"];
	$merchant_goods_list = cart_by_favourable($merchant_goods);
	$smarty->assign("goods_list", $merchant_goods_list);
	$smarty->assign("total", $cart_goods["total"]);
	$smarty->assign("shopping_money", sprintf($_LANG["shopping_money"], $cart_goods["total"]["goods_price"]));
	$smarty->assign("market_price_desc", sprintf($_LANG["than_market_price"], $cart_goods["total"]["market_price"], $cart_goods["total"]["saving"], $cart_goods["total"]["save_rate"]));

	if (0 < $_SESSION["user_id"]) {
		require_once (ROOT_PATH . "includes/lib_clips.php");
		$collection_goods = get_collection_goods($_SESSION["user_id"]);
		$smarty->assign("collection_goods", $collection_goods);
	}

	$favourable_list = favourable_list($_SESSION["user_rank"]);
	usort($favourable_list, "cmp_favourable");
	$smarty->assign("favourable_list", $favourable_list);
	$discount = compute_discount();
	$smarty->assign("discount", $discount["discount"]);
	$favour_name = (empty($discount["name"]) ? "" : join(",", $discount["name"]));
	$smarty->assign("your_discount", sprintf($_LANG["your_discount"], $favour_name, price_format($discount["discount"])));
	$smarty->assign("show_goods_thumb", $GLOBALS["_CFG"]["show_goods_in_cart"]);
	$smarty->assign("show_goods_attribute", $GLOBALS["_CFG"]["show_attr_in_cart"]);
	$sql = "SELECT goods_id FROM " . $GLOBALS["ecs"]->table("cart") . " WHERE " . $sess_id . "AND rec_type = '" . CART_GENERAL_GOODS . "' AND is_gift = 0 AND extension_code <> 'package_buy' AND parent_id = 0 ";
	$parent_list = $GLOBALS["db"]->getCol($sql);
	$fittings_list = get_goods_fittings($parent_list);
	$smarty->assign("fittings_list", $fittings_list);
	$guess_goods = get_guess_goods($_SESSION["usre_id"], 1, 1, 18);
	$best_goods = get_recommend_goods("best", "", $region_id, $area_id);
	$smarty->assign("guess_goods", $guess_goods);
	$smarty->assign("guessGoods_count", count($guess_goods));
	$smarty->assign("best_goods", $best_goods);
	$smarty->assign("bestGoods_count", count($best_goods));
	$smarty->assign("province_row", get_region_name($province_id));
	$smarty->assign("city_row", get_region_name($city_id));
	$smarty->assign("district_row", get_region_name($district_id));
	$province_list = get_warehouse_province();
	$smarty->assign("province_list", $province_list);
	$city_list = get_region_city_county($province_id);
	$smarty->assign("city_list", $city_list);
	$district_list = get_region_city_county($city_id);
	$smarty->assign("district_list", $district_list);
}

$history_goods = get_history_goods(0, $region_id, $area_id);
$smarty->assign("history_goods", $history_goods);
$smarty->assign("historyGoods_count", count($history_goods));
$smarty->assign("currency_format", $_CFG["currency_format"]);
$smarty->assign("integral_scale", price_format($_CFG["integral_scale"]));
$smarty->assign("step", $_REQUEST["step"]);
assign_dynamic("shopping_flow");
$smarty->display("flow.dwt");

?>
