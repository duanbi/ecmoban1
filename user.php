<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function get_order_where_count($user_id = 0, $show_type = 0, $where = "")
{
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS["ecs"]->table("order_info") . " as oi WHERE oi.user_id = '$user_id' and oi.is_delete = '$show_type' and (select count(*) from " . $GLOBALS["ecs"]->table("order_info") . " as oi_2 where oi_2.main_order_id = oi.order_id) = 0 " . $where;
	return $GLOBALS["db"]->getOne($sql);
}

function get_regions_log($type = 0, $parent = 0)
{
	$sql = "SELECT region_id, region_name FROM " . $GLOBALS["ecs"]->table("region") . " WHERE region_type = '$type' AND parent_id = '$parent'";
	return $GLOBALS["db"]->GetAll($sql);
}

function move_image_file_single($source, $dest)
{
	if (@copy($source, $dest)) {
		@unlink($source);
		return true;
	}

	return false;
}

function createFolder($path)
{
	if (!file_exists($path)) {
		createfolder(dirname($path));
		mkdir($path, 511);
	}
}

function create_password($pw_length = 8)
{
	$randpwd = "";

	for ($i = 0; $i < $pw_length; $i++) {
		$randpwd .= chr(mt_rand(33, 126));
	}

	return $randpwd;
}

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");
require (ROOT_PATH . "/includes/lib_area.php");
require_once (ROOT_PATH . "/" . ADMIN_PATH . "/includes/lib_goods.php");
require_once (ROOT_PATH . "languages/" . $_CFG["lang"] . "/user.php");
include_once (ROOT_PATH . "/includes/cls_image.php");
$image = new cls_image($_CFG["bgcolor"]);
$user_id = $_SESSION["user_id"];
$action = (isset($_REQUEST["act"]) ? trim($_REQUEST["act"]) : "default");
$affiliate = unserialize($GLOBALS["_CFG"]["affiliate"]);
$smarty->assign("affiliate", $affiliate);
$back_act = "";
$categories_pro = get_category_tree_leve_one();
$smarty->assign("categories_pro", $categories_pro);
$not_login_arr = array("login", "act_login", "register", "act_register", "act_edit_password", "get_password", "send_pwd_email", "get_pwd_mobile", "password", "signin", "add_tag", "collect", "return_to_cart", "logout", "email_list", "validate_email", "send_hash_mail", "order_query", "is_registered", "check_email", "clear_history", "qpassword_name", "get_passwd_question", "check_answer", "oath", "oath_login", "other_login", "is_mobile_phone", "check_phone", "captchas", "phone_captcha", "code_notice", "captchas_pass");
$ui_arr = array("register", "login", "profile", "order_list", "order_detail", "order_delete_restore", "order_to_query", "order_recycle", "address_list", "address", "collection_list", "store_list", "message_list", "tag_list", "get_password", "get_pwd_mobile", "reset_password", "booking_list", "add_booking", "account_raply", "commented_view", "account_deposit", "account_log", "account_detail", "act_account", "pay", "default", "bonus", "group_buy", "group_buy_detail", "affiliate", "comment_list", "validate_email", "track_packages", "transform_points", "qpassword_name", "get_passwd_question", "check_answer", "return_list", "apply_return", "apply_info", "submit_return", "goods_order", "return_detail", "edit_express", "return_shipping", "face", "check_comm", "single_sun", "single_sun_insert", "single_list", "user_picture", "ajax_del_address", "ajax_add_address", "ajax_make_address", "ajax_update_address", "ajax_BatchCancelFollow");

if (empty($_SESSION["user_id"])) {
	if (!in_array($action, $not_login_arr)) {
		if (in_array($action, $ui_arr)) {
			if (!empty($_SERVER["QUERY_STRING"])) {
				$back_act = "user.php?" . strip_tags($_SERVER["QUERY_STRING"]);
			}

			$action = "login";
		}
		else if ($action != "act_add_bonus") {
			exit($_LANG["require_login"]);
		}
	}
}

$sql = "select user_id from " . $ecs->table("admin_user") . " where ru_id = '" . $_SESSION["user_id"] . "'";
$ru_id = $db->getOne($sql);
$is_merchants = 0;

if (0 < $ru_id) {
	$is_merchants = 1;
}

$smarty->assign("is_merchants", $is_merchants);

if (in_array($action, $ui_arr)) {
	assign_template();
	$position = assign_ur_here(0, $_LANG["user_center"]);
	$smarty->assign("page_title", $position["title"]);
	$categories_pro = get_category_tree_leve_one();
	$smarty->assign("categories_pro", $categories_pro);
	$smarty->assign("ur_here", $position["ur_here"]);
	$sql = "SELECT value FROM " . $ecs->table("shop_config") . " WHERE id = 419";
	$row = $db->getRow($sql);
	$car_off = $row["value"];
	$smarty->assign("car_off", $car_off);
	if (!empty($_CFG["points_rule"]) && unserialize($_CFG["points_rule"])) {
		$smarty->assign("show_transform_points", 1);
	}

	$smarty->assign("helps", get_shop_help());
	$smarty->assign("data_dir", DATA_DIR);
	$smarty->assign("action", $action);
	$smarty->assign("lang", $_LANG);
}

if ($action == "default") {
	include_once (ROOT_PATH . "includes/lib_clips.php");

	if ($rank = get_rank_info()) {
		$h = date("G");

		if ($h < 11) {
			$rank["time_reminder"] = "早上好";
		}
		else if ($h < 13) {
			$rank["time_reminder"] = "中午好";
		}
		else if ($h < 17) {
			$rank["time_reminder"] = "下午好";
		}
		else {
			$rank["time_reminder"] = "晚上好";
		}

		$smarty->assign("rank", $rank);

		if (!empty($rank["next_rank_name"])) {
			$smarty->assign("next_rank_name", sprintf($_LANG["next_level"], $rank["next_rank"], $rank["next_rank_name"]));
		}
	}

	$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table("order_info") . " as oi_1 WHERE oi_1.user_id = '$user_id' and oi_1.is_delete = '$type'  and (select count(*) from " . $GLOBALS["ecs"]->table("order_info") . " as oi_2 where oi_2.main_order_id = oi_1.order_id) = 0 ");
	$order_list = get_default_user_orders($user_id, $record_count);
	$smarty->assign("order_list", $order_list);
	$collection_goods = get_default_collection_goods($user_id);
	$smarty->assign("collection_goods", $collection_goods);
	$smarty->assign("guess_goods", get_guess_goods($user_id));
	$helpart_list = get_user_helpart();
	$smarty->assign("helpart_list", $helpart_list);
	$info = get_user_default($user_id);
	$where_pay = " AND   oi.order_status = '" . OS_UNCONFIRMED . "'";
	$unconfirmed = get_order_where_count($user_id, $type, $where_pay);
	$smarty->assign("unconfirmed", $unconfirmed);
	$where_pay = " AND   oi.order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) . " AND oi.pay_status = '" . PS_UNPAYED . "' AND ( oi.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " OR oi.pay_id " . db_create_in(get_payment_id_list(false)) . ") ";
	$pay_count = get_order_where_count($user_id, $type, $where_pay);
	$smarty->assign("pay_count", $pay_count);
	$where_confirmed = " AND order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) . "  AND oi.shipping_status = '" . SS_SHIPPED . "' AND pay_status " . db_create_in(array(PS_PAYED, PS_PAYING));
	$to_confirm_order = get_order_where_count($user_id, $type, $where_confirmed);
	$smarty->assign("to_confirm_order", $to_confirm_order);
	$where_confirmed = " AND order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) . "  AND oi.shipping_status = '" . SS_RECEIVED . "' AND pay_status " . db_create_in(array(PS_PAYED, PS_PAYING));
	$to_finished = get_order_where_count($user_id, $type, $where_confirmed);
	$smarty->assign("to_finished", $to_finished);
	$smarty->assign("info", $info);
	$smarty->assign("user_notice", $_CFG["user_notice"]);
	$smarty->assign("prompt", get_user_prompt($user_id));
	$smarty->display("user_clips.dwt");
}

if ($action == "register") {
	if (0 < intval($_CFG["sms_signin"])) {
		$sms_security_code = $_SESSION["sms_security_code"] = rand(1000, 9999);
		$smarty->assign("sms_security_code", $sms_security_code);
		$smarty->assign("enabled_sms_signin", 1);
	}

	if ((!isset($back_act) || empty($back_act)) && isset($GLOBALS["_SERVER"]["HTTP_REFERER"])) {
		$back_act = (strpos($GLOBALS["_SERVER"]["HTTP_REFERER"], "user.php") ? "./index.php" : $GLOBALS["_SERVER"]["HTTP_REFERER"]);
	}

	$sql = "SELECT * FROM " . $ecs->table("reg_fields") . " WHERE type < 2 AND display = 1 ORDER BY dis_order, id";
	$extend_info_list = $db->getAll($sql);
	$smarty->assign("extend_info_list", $extend_info_list);
	if ((intval($_CFG["captcha"]) & CAPTCHA_REGISTER) && (0 < gd_version())) {
		$smarty->assign("enabled_captcha", 1);
		$smarty->assign("rand", mt_rand());
	}

	$smarty->assign("passwd_questions", $_LANG["passwd_questions"]);
	$smarty->assign("shop_reg_closed", $_CFG["shop_reg_closed"]);
	$smarty->assign("sms_register", $_CFG["sms_signin"]);
	$smarty->display("user_passport.dwt");
}
else if ($action == "act_register") {
	if ($_CFG["shop_reg_closed"]) {
		$smarty->assign("action", "register");
		$smarty->assign("shop_reg_closed", $_CFG["shop_reg_closed"]);
		$smarty->display("user_passport.dwt");
	}
	else {
		include_once (ROOT_PATH . "includes/lib_passport.php");
		$username = (isset($_POST["username"]) ? compile_str(trim($_POST["username"])) : "");
		$password = (isset($_POST["password"]) ? compile_str(trim($_POST["password"])) : "");
		$email = (isset($_POST["email"]) ? compile_str(trim($_POST["email"])) : "");
		$other["msn"] = (isset($_POST["extend_field1"]) ? compile_str(trim($_POST["extend_field1"])) : "");
		$other["qq"] = (isset($_POST["extend_field2"]) ? compile_str(trim($_POST["extend_field2"])) : "");
		$other["office_phone"] = (isset($_POST["extend_field3"]) ? compile_str(trim($_POST["extend_field3"])) : "");
		$other["home_phone"] = (isset($_POST["extend_field4"]) ? compile_str(trim($_POST["extend_field4"])) : "");
		$sel_question = (empty($_POST["sel_question"]) ? "" : compile_str($_POST["sel_question"]));
		$passwd_answer = (isset($_POST["passwd_answer"]) ? compile_str(trim($_POST["passwd_answer"])) : "");
		$other["mobile_phone"] = (isset($_POST["mobile_phone"]) ? compile_str(trim($_POST["mobile_phone"])) : "");
		$other["mobile_code"] = (isset($_POST["mobile_code"]) ? compile_str(trim($_POST["mobile_code"])) : "");
		$back_act = (isset($_POST["back_act"]) ? compile_str(trim($_POST["back_act"])) : "");
		$register_mode = (isset($_POST["register_mode"]) ? intval($_POST["register_mode"]) : 0);

		if (strlen($password) < 6) {
			show_message($_LANG["passport_js"]["password_shorter"]);
		}

		if (0 < strpos($password, " ")) {
			show_message($_LANG["passwd_balnk"]);
		}

		if ((intval($_CFG["captcha"]) & CAPTCHA_REGISTER) && (0 < gd_version())) {
			if (empty($_POST["captcha"])) {
				show_message($_LANG["invalid_captcha"], $_LANG["sign_up"], "user.php?act=register", "error");
			}

			if ($register_mode) {
				$seKey = "mobile_phone";
			}
			else {
				$seKey = "register_email";
			}

			$captcha = (isset($_POST["captcha"]) ? trim($_POST["captcha"]) : "");
			$verify = new Verify();
			$captcha_code = $verify->check($captcha, $seKey);

			if (!$captcha_code) {
				show_message($_LANG["invalid_captcha"], $_LANG["sign_up"], "user.php?act=register", "error");
			}
		}

		if (register($username, $password, $email, $other, $register_mode) !== false) {
			$sql = "SELECT id FROM " . $ecs->table("reg_fields") . " WHERE type = 0 AND display = 1 ORDER BY dis_order, id";
			$fields_arr = $db->getAll($sql);
			$extend_field_str = "";

			foreach ($fields_arr as $val ) {
				$extend_field_index = "extend_field" . $val["id"];

				if (!empty($_POST[$extend_field_index])) {
					$temp_field_content = (100 < strlen($_POST[$extend_field_index]) ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index]);
					$extend_field_str .= " ('" . $_SESSION["user_id"] . "', '" . $val["id"] . "', '" . compile_str($temp_field_content) . "'),";
				}
			}

			$extend_field_str = substr($extend_field_str, 0, -1);

			if ($extend_field_str) {
				$sql = "INSERT INTO " . $ecs->table("reg_extend_info") . " (`user_id`, `reg_field_id`, `content`) VALUES" . $extend_field_str;
				$db->query($sql);
			}

			if (!empty($passwd_answer) && !empty($sel_question)) {
				$sql = "UPDATE " . $ecs->table("users") . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION["user_id"] . "'";
				$db->query($sql);
			}

			if ($GLOBALS["_CFG"]["member_email_validate"] && $GLOBALS["_CFG"]["send_verify_email"]) {
				send_regiter_hash($_SESSION["user_id"]);
			}

			$ucdata = (empty($user->ucdata) ? "" : $user->ucdata);
			header("Location:user.php");
		}
		else {
			$err->show($_LANG["sign_up"], "user.php?act=register");
		}
	}
}
else if ($action == "oath") {
	$type = (empty($_REQUEST["type"]) ? "" : $_REQUEST["type"]);

	if ($type == "taobao") {
		header("location:includes/website/tb_index.php");
		exit();
	}

	include_once (ROOT_PATH . "includes/website/jntoo.php");
	$c = &website($type);

	if ($c) {
		if (empty($_REQUEST["callblock"])) {
			if (empty($_REQUEST["callblock"]) && isset($GLOBALS["_SERVER"]["HTTP_REFERER"])) {
				$back_act = (strpos($GLOBALS["_SERVER"]["HTTP_REFERER"], "user.php") ? "index.php" : $GLOBALS["_SERVER"]["HTTP_REFERER"]);
			}
			else {
				$back_act = "index.php";
			}
		}
		else {
			$back_act = trim($_REQUEST["callblock"]);
		}

		$open = (empty($_REQUEST["open"]) ? 0 : intval($_REQUEST["open"]));
		$url = $c->login($ecs->url() . "user.php?act=oath_login&type=" . $type . "&callblock=" . urlencode($back_act) . "&open=" . $open);

		if (!$url) {
			show_message($c->get_error(), "首页", $ecs->url(), "error");
		}

		header("Location: " . $url);
	}
	else {
		show_message("服务器尚未注册该插件！", "首页", $ecs->url(), "error");
	}
}
else if ($action == "oath_login") {
	if (!empty($_GET["callblock"]) && (0 < intval($_GET["error_code"]))) {
		$return_url = urldecode(trim($_GET["callblock"]));
		header("Location:" . $return_url);
		exit();
	}

	$type = (empty($_REQUEST["type"]) ? "" : $_REQUEST["type"]);
	include_once (ROOT_PATH . "includes/website/jntoo.php");
	$c = &website($type);

	if ($c) {
		$access = $c->getAccessToken();

		if (!$access) {
			show_message($c->get_error(), "首页", $ecs->url(), "error");
		}

		$c->setAccessToken($access);
		$info = $c->getMessage();

		if (!$info) {
			show_message($c->get_error(), "首页", $ecs->url(), "error", false);
		}

		if (!$info["user_id"]) {
			show_message($c->get_error(), "首页", $ecs->url(), "error", false);
		}

		$info_user_id = $type . "_" . $info["user_id"];
		$info["name"] = str_replace("'", "", $info["name"]);

		if (!$info["user_id"]) {
			show_message($c->get_error(), "首页", $ecs->url(), "error", false);
		}

		$sql = "SELECT user_name,password,aite_id FROM " . $ecs->table("users") . " WHERE aite_id = '" . $info_user_id . "' OR aite_id='" . $info["user_id"] . "'";
		$count = $db->getRow($sql);

		if (!$count) {
			if ($user->check_user($info["name"])) {
				$info["name"] = $info["name"] . "_" . $type . rand(10000, 99999);
			}

			$user_pass = $user->compile_password(array("password" => $info["user_id"]));
			$sql = "INSERT INTO " . $ecs->table("users") . "(user_name , password, aite_id , sex , reg_time , user_rank , is_validated) VALUES ('{$info["name"]}' , '$user_pass' , '$info_user_id' , '{$info["sex"]}' , '" . gmtime() . "' , '{$info["rank_id"]}' , '1')";
			$db->query($sql);
		}
		else {
			$sql = "";

			if ($count["aite_id"] == $info["user_id"]) {
				$sql = "UPDATE " . $ecs->table("users") . " SET aite_id = '$info_user_id' WHERE aite_id = '{$count["aite_id"]}'";
				$db->query($sql);
			}

			if ($info["name"] != $count["user_name"]) {
				if ($user->check_user($info["name"])) {
					$info["name"] = $info["name"] . "_" . $type . (rand() * 1000);
				}

				$sql = "UPDATE " . $ecs->table("users") . " SET user_name = '{$info["name"]}' WHERE aite_id = '$info_user_id'";
				$db->query($sql);
			}
		}

		$user->set_session($info["name"]);
		$user->set_cookie($info["name"]);
		update_user_info();
		recalculate_price();

		if (!empty($_REQUEST["open"])) {
			exit("<script>window.opener.window.location.reload(); window.close();</script>");
		}
		else {
			ecs_header("Location: " . $_REQUEST["callblock"]);
		}
	}
}
else if ($action == "other_login") {
	$type = (empty($_REQUEST["type"]) ? "" : $_REQUEST["type"]);
	session_start();
	$info = $_SESSION["user_info"];

	if (empty($info)) {
		show_message("非法访问或请求超时！", "首页", $ecs->url(), "error", false);
	}

	if (!$info["user_id"]) {
		show_message("非法访问或访问出错，请联系管理员！", "首页", $ecs->url(), "error", false);
	}

	$info_user_id = $type . "_" . $info["user_id"];
	$info["name"] = str_replace("'", "", $info["name"]);
	$sql = "SELECT user_name,password,aite_id FROM " . $ecs->table("users") . " WHERE aite_id = '" . $info_user_id . "' OR aite_id='" . $info["user_id"] . "'";
	$count = $db->getRow($sql);
	$login_name = $info["name"];

	if (!$count) {
		if ($user->check_user($info["name"])) {
			$info["name"] = $info["name"] . "_" . $type . (rand() * 1000);
		}

		$login_name = $info["name"];
		$user_pass = $user->compile_password(array("password" => $info["user_id"]));
		$sql = "INSERT INTO " . $ecs->table("users") . "(user_name , password, aite_id , sex , reg_time , user_rank , is_validated) VALUES ('{$info["name"]}' , '$user_pass' , '$info_user_id' , '{$info["sex"]}' , '" . gmtime() . "' , '{$info["rank_id"]}' , '1')";
		$db->query($sql);
	}
	else {
		$login_name = $count["user_name"];
		$sql = "";

		if ($count["aite_id"] == $info["user_id"]) {
			$sql = "UPDATE " . $ecs->table("users") . " SET aite_id = '$info_user_id' WHERE aite_id = '{$count["aite_id"]}'";
			$db->query($sql);
		}
	}

	$user->set_session($login_name);
	$user->set_cookie($login_name);
	update_user_info();
	recalculate_price();
	$redirect_url = "http://" . $_SERVER["HTTP_HOST"] . str_replace("user.php", "index.php", $_SERVER["REQUEST_URI"]);
	header("Location: " . $redirect_url);
}
else if ($action == "validate_email") {
	$hash = (empty($_GET["hash"]) ? "" : trim($_GET["hash"]));

	if ($hash) {
		include_once (ROOT_PATH . "includes/lib_passport.php");
		$id = register_hash("decode", $hash);

		if (0 < $id) {
			$sql = "UPDATE " . $ecs->table("users") . " SET is_validated = 1 WHERE user_id='$id'";
			$db->query($sql);
			$sql = "SELECT user_name, email FROM " . $ecs->table("users") . " WHERE user_id = '$id'";
			$row = $db->getRow($sql);
			show_message(sprintf($_LANG["validate_ok"], $row["user_name"], $row["email"]), $_LANG["profile_lnk"], "user.php");
		}
	}

	show_message($_LANG["validate_fail"]);
}
else if ($action == "is_registered") {
	include_once (ROOT_PATH . "includes/lib_passport.php");
	include (ROOT_PATH . "includes/cls_json.php");
	$json = new JSON();
	$res = array("result" => "", "error" => 0, "mode" => 0);
	$username = trim($_GET["username"]);
	$username = json_str_iconv($username);
	$mode = (isset($_GET["mode"]) && (intval($_GET["mode"]) == 1) ? 1 : 0);
	if ($user->check_user($username) || admin_registered($username)) {
		$res["result"] = "false";
	}
	else {
		$res["result"] = "ok";
	}

	$res["mode"] = $mode;
	exit($json->encode($res));
}
else if ($action == "is_mobile_phone") {
	$phone = trim($_GET["phone"]);
	$phone = json_str_iconv($phone);

	if ($user->check_mobile_phone($phone)) {
		echo "false";
	}
	else {
		echo "true";
	}
}
else if ($action == "check_email") {
	$email = trim($_GET["email"]);

	if ($user->check_email($email)) {
		echo "false";
	}
	else {
		echo "ok";
	}
}
else if ($action == "check_phone") {
	$mobile_phone = trim($_GET["mobile_phone"]);

	if ($user->check_mobile_phone($mobile_phone)) {
		echo "false";
	}
	else {
		echo "ok";
	}
}
else if ($action == "code_notice") {
	$code = (isset($_REQUEST["code"]) ? trim($_REQUEST["code"]) : "");
	$sms_security_code = (isset($_SESSION["sms_mobile_code"]) ? trim($_SESSION["sms_mobile_code"]) : "");

	if ($code == "") {
		echo 1;
	}
	else if ($code != $sms_security_code) {
		echo 2;
	}
	else {
		echo 3;
	}
}
else if ($action == "captchas") {
	$captcha = (isset($_REQUEST["captcha"]) ? trim($_REQUEST["captcha"]) : "");
	if ((intval($_CFG["captcha"]) & CAPTCHA_REGISTER) && (0 < gd_version())) {
		if (empty($captcha)) {
			echo 1;
		}
		else {
			$seKey = "register_email";
			$verify = new Verify();
			$captcha_code = $verify->check($captcha, $seKey, "", "ajax");

			if (!$captcha_code) {
				echo 2;
			}
			else {
				echo 3;
			}
		}
	}
}
else if ($action == "phone_captcha") {
	$captcha = (isset($_REQUEST["captcha"]) ? trim($_REQUEST["captcha"]) : "");
	if ((intval($_CFG["captcha"]) & CAPTCHA_REGISTER) && (0 < gd_version())) {
		if (empty($captcha)) {
			echo 1;
		}
		else {
			$seKey = "mobile_phone";
			$verify = new Verify();
			$captcha_code = $verify->check($captcha, $seKey, "", "ajax");

			if (!$captcha_code) {
				echo 2;
			}
			else {
				echo 3;
			}
		}
	}
}
else if ($action == "login") {
	if (empty($back_act)) {
		if (empty($back_act) && isset($GLOBALS["_SERVER"]["HTTP_REFERER"])) {
			$back_act = (strpos($GLOBALS["_SERVER"]["HTTP_REFERER"], "user.php") ? "./index.php" : $GLOBALS["_SERVER"]["HTTP_REFERER"]);
		}
		else {
			$back_act = "user.php";
		}
	}

	$captcha = intval($_CFG["captcha"]);
	if (($captcha & CAPTCHA_LOGIN) && (!$captcha & CAPTCHA_LOGIN_FAIL || (($captcha & CAPTCHA_LOGIN_FAIL) && (2 < $_SESSION["login_fail"]))) && (0 < gd_version())) {
		$GLOBALS["smarty"]->assign("enabled_captcha", 1);
		$GLOBALS["smarty"]->assign("rand", mt_rand());
	}

	for ($i = 1; $i <= $_CFG["auction_ad"]; $i++) {
		$login_banner .= "'login_banner" . $i . ",";
	}

	$smarty->assign("login_banner", $login_banner);
	$smarty->assign("back_act", $back_act);
	$smarty->display("user_passport.dwt");
}
else if ($action == "captchas_pass") {
	$captcha = (isset($_REQUEST["captcha"]) ? trim($_REQUEST["captcha"]) : "");
	if (intval($_CFG["captcha"]) && (0 < gd_version())) {
		if (empty($captcha)) {
		}
		else {
			$seKey = (!empty($_GET["seKey"]) ? $_GET["seKey"] : "");
			$verify = new Verify();
			$captcha_code = $verify->check($captcha, $seKey, "", "ajax");

			if (!$captcha_code) {
				echo 2;
			}
			else {
				echo 3;
			}
		}
	}
}
else if ($action == "act_login") {
	include_once ("includes/cls_json.php");
	$username = (isset($_POST["username"]) ? trim($_POST["username"]) : "");
	$password = (isset($_POST["password"]) ? trim($_POST["password"]) : "");
	$back_act = (isset($_POST["back_act"]) ? trim($_POST["back_act"]) : "");
	$back_act = str_replace("|", "&", $back_act);
	$result = array("error" => 0, "message" => "", "url" => "");
	$json = new JSON();
	$captcha = intval($_CFG["captcha"]);
	if (($captcha & CAPTCHA_LOGIN) && (!$captcha & CAPTCHA_LOGIN_FAIL || (($captcha & CAPTCHA_LOGIN_FAIL) && (2 < $_SESSION["login_fail"]))) && (0 < gd_version())) {
		if (empty($_POST["captcha"])) {
			$result["error"] = 1;
			$result["message"] = $_LANG["invalid_captcha"];
		}

		$captcha_str = (isset($_POST["captcha"]) ? trim($_POST["captcha"]) : "");
		$verify = new Verify();
		$captcha_code = $verify->check($captcha_str, "captcha_login");

		if (!$captcha_code) {
			$result["error"] = 1;
			$result["message"] = $_LANG["invalid_captcha"];
		}
	}

	if ($result["error"] == 0) {
		if ($user->login($username, $password, isset($_POST["remember"]))) {
			update_user_info();
			recalculate_price();
			$ucdata = (isset($user->ucdata) ? $user->ucdata : "");
			$back_act = (!empty($back_act) ? $back_act : "index.php");
			$result["url"] = $back_act;
		}
		else {
			$$_SESSION["login_fail"]++;
			$result["error"] = 1;
			$result["message"] = $_LANG["login_failure"];
		}
	}

	exit($json->encode($result));
}
else if ($action == "signin") {
	include_once ("includes/cls_json.php");
	$json = new JSON();
	$username = (!empty($_POST["username"]) ? json_str_iconv(trim($_POST["username"])) : "");
	$password = (!empty($_POST["password"]) ? trim($_POST["password"]) : "");
	$captcha = (!empty($_POST["captcha"]) ? json_str_iconv(trim($_POST["captcha"])) : "");
	$result = array("error" => 0, "content" => "");
	$captcha = intval($_CFG["captcha"]);
	if (($captcha & CAPTCHA_LOGIN) && (!$captcha & CAPTCHA_LOGIN_FAIL || (($captcha & CAPTCHA_LOGIN_FAIL) && (2 < $_SESSION["login_fail"]))) && (0 < gd_version())) {
		if (empty($captcha)) {
			$result["error"] = 1;
			$result["content"] = $_LANG["invalid_captcha"];
			exit($json->encode($result));
		}

		include_once ("includes/cls_captcha.php");
		$validator = new captcha();
		$validator->session_word = "captcha_login";

		if (!$validator->check_word($_POST["captcha"])) {
			$result["error"] = 1;
			$result["content"] = $_LANG["invalid_captcha"];
			exit($json->encode($result));
		}
	}

	if ($user->login($username, $password)) {
		update_user_info();
		recalculate_price();
		$smarty->assign("user_info", get_user_info());
		$ucdata = (empty($user->ucdata) ? "" : $user->ucdata);
		$result["ucdata"] = $ucdata;
		$result["content"] = $smarty->fetch("library/member_info.lbi");
	}
	else {
		$$_SESSION["login_fail"]++;

		if (2 < $_SESSION["login_fail"]) {
			$smarty->assign("enabled_captcha", 1);
			$result["html"] = $smarty->fetch("library/member_info.lbi");
		}

		$result["error"] = 1;
		$result["content"] = $_LANG["login_failure"];
	}

	exit($json->encode($result));
}
else if ($action == "logout") {
	if ((!isset($back_act) || empty($back_act)) && isset($GLOBALS["_SERVER"]["HTTP_REFERER"])) {
		$back_act = (strpos($GLOBALS["_SERVER"]["HTTP_REFERER"], "user.php") ? "./index.php" : $GLOBALS["_SERVER"]["HTTP_REFERER"]);
	}

	$user->logout();
	$ucdata = (empty($user->ucdata) ? "" : $user->ucdata);
	header("Location:user.php?act=login");
}
else if ($action == "profile") {
	include_once (ROOT_PATH . "includes/lib_transaction.php");

	if (0 < intval($_CFG["sms_signin"])) {
		$sms_security_code = $_SESSION["sms_security_code"] = rand(1000, 9999);
		$smarty->assign("sms_security_code", $sms_security_code);
		$smarty->assign("enabled_sms_signin", 1);
	}

	$user_info = get_profile($user_id);
	$info = get_user_default($user_id);
	$sql = "SELECT * FROM " . $ecs->table("reg_fields") . " WHERE type < 2 AND display = 1 ORDER BY dis_order, id";
	$extend_info_list = $db->getAll($sql);
	$sql = "SELECT reg_field_id, content FROM " . $ecs->table("reg_extend_info") . " WHERE user_id = $user_id";
	$extend_info_arr = $db->getAll($sql);
	$temp_arr = array();

	foreach ($extend_info_arr as $val ) {
		$temp_arr[$val["reg_field_id"]] = $val["content"];
	}

	foreach ($extend_info_list as $key => $val ) {
		switch ($val["id"]) {
		case 1:
			$extend_info_list[$key]["content"] = $user_info["msn"];
			break;

		case 2:
			$extend_info_list[$key]["content"] = $user_info["qq"];
			break;

		case 3:
			$extend_info_list[$key]["content"] = $user_info["office_phone"];
			break;

		case 4:
			$extend_info_list[$key]["content"] = $user_info["home_phone"];
			break;

		case 5:
			$extend_info_list[$key]["content"] = $user_info["mobile_phone"];
			break;

		default:
			$extend_info_list[$key]["content"] = (empty($temp_arr[$val["id"]]) ? "" : $temp_arr[$val["id"]]);
		}
	}

	$smarty->assign("extend_info_list", $extend_info_list);
	$smarty->assign("passwd_questions", $_LANG["passwd_questions"]);
	$smarty->assign("sms_register", $_CFG["sms_signin"]);
	$smarty->assign("profile", $user_info);
	$smarty->assign("info", $info);
	$smarty->display("user_transaction.dwt");
}
else if ($action == "user_picture") {
	$create = create_password();
	$img_sir = "data/images_user/" . $_SESSION["user_id"];

	if (file_exists($img_sir . "_120.jpg")) {
		$img_sir = $img_sir . "_120.jpg";
	}
	else {
		$img_sir = "data/images_user/0_120.jpg";
	}

	$smarty->assign("create", $create);
	$smarty->assign("img_sir", $img_sir);
	$smarty->assign("user_id", $_SESSION["user_id"]);
	$smarty->display("user_transaction.dwt");
}
else if ($action == "act_edit_profile") {
	include_once (ROOT_PATH . "includes/lib_transaction.php");
	$birthday = trim($_POST["birthdayYear"]) . "-" . trim($_POST["birthdayMonth"]) . "-" . trim($_POST["birthdayDay"]);
	$email = trim($_POST["email"]);
	$other["msn"] = $msn = (isset($_POST["extend_field1"]) ? trim($_POST["extend_field1"]) : "");
	$other["qq"] = $qq = (isset($_POST["extend_field2"]) ? trim($_POST["extend_field2"]) : "");
	$other["office_phone"] = $office_phone = (isset($_POST["extend_field3"]) ? trim($_POST["extend_field3"]) : "");
	$other["home_phone"] = $home_phone = (isset($_POST["extend_field4"]) ? trim($_POST["extend_field4"]) : "");
	$mobile_phone = (isset($_POST["mobile_phone"]) ? trim($_POST["mobile_phone"]) : "");
	$sel_question = (empty($_POST["sel_question"]) ? "" : compile_str($_POST["sel_question"]));
	$passwd_answer = (isset($_POST["passwd_answer"]) ? compile_str(trim($_POST["passwd_answer"])) : "");
	$mobile_code = (isset($_POST["mobile_code"]) ? trim($_POST["mobile_code"]) : "");
	$sql = "SELECT id FROM " . $ecs->table("reg_fields") . " WHERE type = 0 AND display = 1 ORDER BY dis_order, id";
	$fields_arr = $db->getAll($sql);

	foreach ($fields_arr as $val ) {
		$extend_field_index = "extend_field" . $val["id"];

		if (isset($_POST[$extend_field_index])) {
			$temp_field_content = (100 < strlen($_POST[$extend_field_index]) ? mb_substr(htmlspecialchars($_POST[$extend_field_index]), 0, 99) : htmlspecialchars($_POST[$extend_field_index]));
			$sql = "SELECT * FROM " . $ecs->table("reg_extend_info") . "  WHERE reg_field_id = '{$val["id"]}' AND user_id = '$user_id'";

			if ($db->getOne($sql)) {
				$sql = "UPDATE " . $ecs->table("reg_extend_info") . " SET content = '$temp_field_content' WHERE reg_field_id = '{$val["id"]}' AND user_id = '$user_id'";
			}
			else {
				$sql = "INSERT INTO " . $ecs->table("reg_extend_info") . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '{$val["id"]}', '$temp_field_content')";
			}

			$db->query($sql);
		}
	}

	if (!empty($passwd_answer) && !empty($sel_question)) {
		$sql = "UPDATE " . $ecs->table("users") . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION["user_id"] . "'";
		$db->query($sql);
	}

	if (!empty($office_phone) && !preg_match("/^[\d|\_|\-|\s]+$/", $office_phone)) {
		show_message($_LANG["passport_js"]["office_phone_invalid"]);
	}

	if (!empty($home_phone) && !preg_match("/^[\d|\_|\-|\s]+$/", $home_phone)) {
		show_message($_LANG["passport_js"]["home_phone_invalid"]);
	}

	if (!is_email($email) || empty($email)) {
		show_message("Email不为空或格式不正确！");
	}

	if (!empty($msn) && !is_email($msn)) {
		show_message($_LANG["passport_js"]["msn_invalid"]);
	}

	if (!empty($qq) && !preg_match("/^\d+$/", $qq)) {
		show_message($_LANG["passport_js"]["qq_invalid"]);
	}

	if (!empty($mobile_phone) && !preg_match("/^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$/", $mobile_phone)) {
		show_message($_LANG["passport_js"]["mobile_phone_invalid"]);
	}

	$profile = array("user_id" => $user_id, "email" => isset($_POST["email"]) ? trim($_POST["email"]) : "", "mobile_phone" => $mobile_phone, "mobile_code" => $mobile_code, "sex" => isset($_POST["sex"]) ? intval($_POST["sex"]) : 0, "birthday" => $birthday, "other" => isset($other) ? $other : array());

	if (edit_profile($profile)) {
		show_message($_LANG["edit_profile_success"], $_LANG["profile_lnk"], "user.php?act=profile", "info");
	}
	else {
		if ($user->error == ERR_EMAIL_EXISTS) {
			$msg = sprintf($_LANG["email_exist"], $profile["email"]);
		}
		else if ($user->error == ERR_PHONE_EXISTS) {
			$msg = sprintf($_LANG["phone_exist"], $profile["mobile_phone"]);
		}
		else if ($err->error_no) {
			$msg = "手机校验码为空或过期，稍后修改";
		}
		else {
			$msg = $_LANG["edit_profile_failed"];
		}

		show_message($msg, "", "", "info");
	}
}
else if ($action == "get_password") {
	include_once (ROOT_PATH . "includes/lib_passport.php");
	if (isset($_GET["code"]) && isset($_GET["uid"])) {
		$code = trim($_GET["code"]);
		$uid = intval($_GET["uid"]);
		$user_info = $user->get_profile_by_id($uid);
		if (empty($user_info) || ($user_info && (md5($user_info["user_id"] . $_CFG["hash_code"] . $user_info["reg_time"]) != $code))) {
			show_message($_LANG["parm_error"], $_LANG["back_home_lnk"], "./", "info");
		}

		$smarty->assign("uid", $uid);
		$smarty->assign("code", $code);
		$smarty->assign("action", "reset_password");
		$smarty->display("user_passport.dwt");
	}
	else {
		if (0 < intval($_CFG["sms_signin"])) {
			$sms_security_code = $_SESSION["sms_security_code"] = rand(1000, 9999);
			$smarty->assign("sms_security_code", $sms_security_code);
			$smarty->assign("enabled_sms_signin", 1);
		}

		$sql = "SELECT * FROM " . $ecs->table("reg_fields") . " WHERE type < 2 AND display = 1 ORDER BY dis_order, id";
		$extend_info_list = $db->getAll($sql);
		$smarty->assign("extend_info_list", $extend_info_list);
		$smarty->assign("passwd_questions", $_LANG["passwd_questions"]);
		$smarty->display("user_passport.dwt");
	}
}
else if ($action == "qpassword_name") {
	$smarty->display("user_passport.dwt");
}
else if ($action == "get_passwd_question") {
	if (empty($_POST["user_name"])) {
		show_message($_LANG["no_passwd_question"], $_LANG["back_home_lnk"], "./", "info");
	}
	else {
		$user_name = trim($_POST["user_name"]);
	}

	$sql = "SELECT user_id, user_name, passwd_question, passwd_answer FROM " . $ecs->table("users") . " WHERE user_name = '" . $user_name . "'";
	$user_question_arr = $db->getRow($sql);

	if (empty($user_question_arr["passwd_answer"])) {
		show_message($_LANG["no_passwd_question"], $_LANG["back_home_lnk"], "./", "info");
	}

	$_SESSION["temp_user"] = $user_question_arr["user_id"];
	$_SESSION["temp_user_name"] = $user_question_arr["user_name"];
	$_SESSION["passwd_answer"] = $user_question_arr["passwd_answer"];
	$captcha = intval($_CFG["captcha"]);
	if (($captcha & CAPTCHA_LOGIN) && (!$captcha & CAPTCHA_LOGIN_FAIL || (($captcha & CAPTCHA_LOGIN_FAIL) && (2 < $_SESSION["login_fail"]))) && (0 < gd_version())) {
		$GLOBALS["smarty"]->assign("enabled_captcha", 1);
		$GLOBALS["smarty"]->assign("rand", mt_rand());
	}

	$smarty->assign("passwd_question", $_LANG["passwd_questions"][$user_question_arr["passwd_question"]]);
	$smarty->display("user_passport.dwt");
}
else if ($action == "check_answer") {
	$user_name = (empty($_POST["user_name"]) ? "" : compile_str(trim($_POST["user_name"])));
	$sel_question = (empty($_POST["sel_question"]) ? "" : compile_str($_POST["sel_question"]));
	$passwd_answer = (isset($_POST["passwd_answer"]) ? compile_str(trim($_POST["passwd_answer"])) : "");
	$captcha_str = (isset($_POST["captcha"]) ? trim($_POST["captcha"]) : "");
	$verify = new Verify();
	$captcha_code = $verify->check($captcha_str, "psw_question");

	if (!$captcha_code) {
		show_message($_LANG["invalid_captcha"], $_LANG["back_up_page"], "user.php?act=get_password", "error");
		exit();
	}

	$sql = "SELECT user_id, user_name, passwd_question, passwd_answer FROM " . $ecs->table("users") . " WHERE user_name = '" . $user_name . "' AND passwd_question = '$sel_question' AND passwd_answer = '$passwd_answer'";
	$user_question_arr = $db->getRow($sql);

	if (empty($user_question_arr)) {
		show_message($_LANG["wrong_passwd_answer"], "", "user.php?act=get_password", "info");
	}
	else {
		$_SESSION["user_id"] = $user_question_arr["user_id"];
		$_SESSION["user_name"] = $user_question_arr["user_name"];
		$smarty->assign("uid", $_SESSION["user_id"]);
		$smarty->assign("action", "reset_password");
		$smarty->display("user_passport.dwt");
	}
}
else if ($action == "send_pwd_email") {
	include_once (ROOT_PATH . "includes/lib_passport.php");
	$user_name = (!empty($_POST["user_name"]) ? trim($_POST["user_name"]) : "");
	$email = (!empty($_POST["email"]) ? trim($_POST["email"]) : "");
	$captcha_str = (isset($_POST["captcha"]) ? trim($_POST["captcha"]) : "");
	$verify = new Verify();
	$captcha_code = $verify->check($captcha_str, "get_password");

	if (!$captcha_code) {
		show_message($_LANG["invalid_captcha"], $_LANG["back_up_page"], "user.php?act=get_password", "error");
		exit();
	}

	$user_info = $user->get_user_info($user_name);
	if ($user_info && ($user_info["email"] == $email)) {
		$code = md5($user_info["user_id"] . $_CFG["hash_code"] . $user_info["reg_time"]);

		if (send_pwd_email($user_info["user_id"], $user_name, $email, $code)) {
			show_message($_LANG["send_success"] . $email, $_LANG["back_home_lnk"], "./", "info");
		}
		else {
			show_message($_LANG["fail_send_password"], $_LANG["back_page_up"], "./", "info");
		}
	}
	else {
		show_message($_LANG["username_no_email"], $_LANG["back_page_up"], "", "info");
	}
}
else if ($action == "get_pwd_mobile") {
	include_once (ROOT_PATH . "includes/lib_passport.php");
	$user_name = (!empty($_POST["user_name"]) ? trim($_POST["user_name"]) : "");
	$mobile_phone = (!empty($_POST["mobile_phone"]) ? trim($_POST["mobile_phone"]) : "");
	$mobile_code = (!empty($_POST["mobile_code"]) ? trim($_POST["mobile_code"]) : "");
	if (empty($mobile_phone) || empty($mobile_code)) {
		show_message("手机号或手机验证码不为空", $_LANG["back_retry_answer"], "user.php?act=get_password", "info");
	}

	if (($mobile_phone != $_SESSION["sms_mobile"]) || ($mobile_code != $_SESSION["sms_mobile_code"])) {
		show_message("手机号或手机验证码不正确", $_LANG["back_retry_answer"], "user.php?act=get_password", "info");
	}

	$sql = "SELECT user_id, user_name FROM " . $ecs->table("users") . " WHERE mobile_phone = '$mobile_phone' AND user_name = '$user_name'";
	$user_arr = $db->getRow($sql);

	if (empty($user_arr)) {
		show_message("用户名或手机号错误!", $_LANG["back_home_lnk"], "./", "info");
	}

	$_SESSION["user_id"] = $_SESSION["temp_user_id"];
	$_SESSION["user_name"] = $_SESSION["temp_user_name"];
	unset($_SESSION["temp_user_id"]);
	unset($_SESSION["temp_user_name"]);
	$smarty->assign("uid", $_SESSION["user_id"]);
	$smarty->assign("action", "reset_password");
	$smarty->display("user_passport.dwt");
}
else if ($action == "reset_password") {
	$smarty->display("user_passport.dwt");
}
else if ($action == "act_edit_password") {
	include_once (ROOT_PATH . "includes/lib_passport.php");
	$old_password = (isset($_POST["old_password"]) ? trim($_POST["old_password"]) : NULL);
	$new_password = (isset($_POST["new_password"]) ? trim($_POST["new_password"]) : "");
	$user_id = (isset($_POST["uid"]) ? intval($_POST["uid"]) : $user_id);
	$code = (isset($_POST["code"]) ? trim($_POST["code"]) : "");

	if (strlen($new_password) < 6) {
		show_message($_LANG["passport_js"]["password_shorter"]);
	}

	$user_info = $user->get_profile_by_id($user_id);
	if (($user_info && !empty($code) && (md5($user_info["user_id"] . $_CFG["hash_code"] . $user_info["reg_time"]) == $code)) || ((0 < $_SESSION["user_id"]) && ($_SESSION["user_id"] == $user_id) && $user->check_user($_SESSION["user_name"], $old_password))) {
		if ($user->edit_user(array("user_id" => $user_id, "username" => empty($code) ? $_SESSION["user_name"] : $user_info["user_name"], "old_password" => $old_password, "password" => $new_password), empty($code) ? 0 : 1)) {
			$sql = "UPDATE " . $ecs->table("users") . "SET `ec_salt`='0' WHERE user_id= '" . $user_id . "'";
			$db->query($sql);
			$user->logout();
			show_message($_LANG["edit_password_success"], $_LANG["relogin_lnk"], "user.php?act=login", "info");
		}
		else {
			show_message($_LANG["edit_password_failure"], $_LANG["back_page_up"], "", "info");
		}
	}
	else {
		show_message($_LANG["edit_password_failure"], $_LANG["back_page_up"], "", "info");
	}
}
else if ($action == "act_add_bonus") {
	include_once (ROOT_PATH . "includes/lib_transaction.php");
	include ("includes/cls_json.php");
	$json = new JSON();
	$result = array("message" => "", "result" => "", "error" => 0);
	$_REQUEST["bns"] = (isset($_REQUEST["bns"]) ? json_str_iconv($_REQUEST["bns"]) : "");
	$bns = $json->decode($_REQUEST["bns"]);
	$bouns_sn = intval($bns->bonus_sn);
	$password = compile_str($bns->password);
	$captcha_str = (isset($bns->captcha) ? trim($bns->captcha) : "");

	if (0 < gd_version()) {
		$verify = new Verify();
		$captcha_code = $verify->check($captcha_str, "bonus");

		if (!$captcha_code) {
			$result["error"] = 3;
			$result["message"] = $_LANG["invalid_captcha"];
		}
	}

	if ($result["error"] != 3) {
		if (empty($user_id)) {
			$result["error"] = 2;
			$result["message"] = $GLOBALS["_LANG"]["not_login"];
		}
		else if (add_bonus($user_id, $bouns_sn, $password)) {
			$result["message"] = $_LANG["add_bonus_sucess"];
		}
		else {
			$result["error"] = 1;
			$result["message"] = $_LANG["add_bonus_false"];
		}
	}

	exit($json->encode($result));
}
else {
	if (($action == "order_list") || ($action == "order_recycle")) {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$order_type = (isset($_REQUEST["order_type"]) ? htmlspecialchars(trim($_REQUEST["order_type"])) : "");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$sql = "select * from " . $ecs->table("seller_shopinfo") . " where ru_id = 0";
		$basic_info = $db->getRow($sql);
		$smarty->assign("basic_info", $basic_info);
		$smarty->assign("status_list", $_LANG["cs"]);

		if ($action == "order_list") {
			$type = 0;
			$smarty->assign("action", $action);
		}
		else if ($action == "order_recycle") {
			$type = 1;
			$smarty->assign("action", $action);
		}

		$category = get_onetwo_category();
		$smarty->assign("category", $category);
		$where_pay = " AND   oi.order_status = '" . OS_UNCONFIRMED . "'";
		$unconfirmed = get_order_where_count($user_id, $type, $where_pay);
		$smarty->assign("unconfirmed", $unconfirmed);
		$where_pay = " AND   oi.order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) . " AND oi.pay_status = '" . PS_UNPAYED . "' AND ( oi.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " OR oi.pay_id " . db_create_in(get_payment_id_list(false)) . ") ";
		$pay_count = get_order_where_count($user_id, $type, $where_pay);
		$smarty->assign("pay_count", $pay_count);
		$where_confirmed = " AND order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) . "  AND oi.shipping_status = '" . SS_SHIPPED . "' AND pay_status " . db_create_in(array(PS_PAYED, PS_PAYING));
		$to_confirm_order = get_order_where_count($user_id, $type, $where_confirmed);
		$smarty->assign("to_confirm_order", $to_confirm_order);
		$where_confirmed = " AND order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) . "  AND oi.shipping_status = '" . SS_RECEIVED . "' AND pay_status " . db_create_in(array(PS_PAYED, PS_PAYING));
		$to_finished = get_order_where_count($user_id, $type, $where_confirmed);
		$smarty->assign("to_finished", $to_finished);
		$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table("order_info") . " as oi_1 WHERE oi_1.user_id = '$user_id' and oi_1.is_delete = '$type'  and (select count(*) from " . $GLOBALS["ecs"]->table("order_info") . " as oi_2 where oi_2.main_order_id = oi_1.order_id) = 0 ");
		$orders = get_user_orders($user_id, $record_count, $page, $type);
		$merge = get_user_merge($user_id);
		$smarty->assign("order_type", $order_type);
		$smarty->assign("merge", $merge);
		$smarty->assign("orders", $orders);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "order_detail") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_payment.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$order_id = (isset($_GET["order_id"]) ? intval($_GET["order_id"]) : 0);
		$noTime = gmtime();
		$date = array("order_status", "shipping_status", "pay_status", "shipping_time", "auto_delivery_time");
		$orderInfo = get_table_date("order_info", "order_id = '$order_id' and user_id = '$user_id'", $date);

		if ($_CFG["open_delivery_time"] == 1) {
			if (($orderInfo["order_status"] == 5) && ($orderInfo["shipping_status"] == 1) && ($orderInfo["pay_status"] == 2)) {
				$delivery_time = $orderInfo["shipping_time"] + (24 * 3600 * $orderInfo["auto_delivery_time"]);

				if ($delivery_time < $noTime) {
					$sql = "UPDATE " . $GLOBALS["ecs"]->table("order_info") . " SET order_status = '" . OS_SPLITED . "', shipping_status = '" . SS_RECEIVED . "', pay_status = '" . PS_PAYED . "' WHERE order_id = '$order_id'";
					$GLOBALS["db"]->query($sql);
				}
			}
		}

		$order = get_order_detail($order_id, $user_id);

		if ($order === false) {
			$err->show($_LANG["back_home_lnk"], "./");
			exit();
		}

		if (($order["extension_code"] != "group_buy") && ($order["extension_code"] != "exchange_goods") && ($order["extension_code"] != "presale")) {
			$smarty->assign("allow_to_cart", 1);
		}

		$goods_list = order_goods($order_id);

		foreach ($goods_list as $key => $value ) {
			$goods_list[$key]["market_price"] = price_format($value["market_price"], false);
			$goods_list[$key]["goods_price"] = price_format($value["goods_price"], false);
			$goods_list[$key]["subtotal"] = price_format($value["subtotal"], false);
		}

		if (0 < $order["order_amount"]) {
			if (($order["order_status"] == OS_UNCONFIRMED) || ($order["order_status"] == OS_CONFIRMED)) {
				$user = user_info($order["user_id"]);

				if (0 < ($user["user_money"] + $user["credit_line"])) {
					$smarty->assign("allow_edit_surplus", 1);
					$smarty->assign("max_surplus", sprintf($_LANG["max_surplus"], $user["user_money"]));
				}
			}
		}

		if ((0 < $order["order_amount"]) && (($order["pay_status"] == PS_UNPAYED) || ($order["pay_status"] == PS_PAYED_PART)) && ($order["shipping_status"] == SS_UNSHIPPED)) {
			$payment_list = available_payment_list(false, 0, true);

			if (is_array($payment_list)) {
				foreach ($payment_list as $key => $payment ) {
					if (substr($payment["pay_code"], 0, 4) == "pay_") {
						unset($payment_list[$key]);
						continue;
					}

					if (($payment["pay_id"] == $order["pay_id"]) || ($payment["pay_code"] == "balance")) {
						unset($payment_list[$key]);
					}
				}
			}

			$smarty->assign("payment_list", $payment_list);
		}

		$order["order_status"] = $_LANG["os"][$order["order_status"]];
		$order["pay_status_desc"] = $_LANG["ps"][$order["pay_status"]];
		$order["shipping_status"] = $_LANG["ss"][$order["shipping_status"]];
		$sql = "SELECT COUNT(*) FROM " . $ecs->table("feedback") . " WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$user_id'";
		$feedback_num = $db->getOne($sql);
		$sql = "SELECT address_id FROM " . $ecs->table("users") . " WHERE user_id = '$user_id'";
		$address_id = $db->getOne($sql);
		$sql = "SELECT shipping_code FROM " . $ecs->table("shipping") . " WHERE shipping_id = '{$order["shipping_id"]}'";

		if ($db->getOne($sql) == "cac") {
			$sql = "SELECT * FROM " . $ecs->table("shipping_point") . " WHERE id IN (SELECT point_id FROM " . $ecs->table("order_info") . " WHERE order_id='$order_id')";
			$order["point"] = $db->getRow($sql);
			$order["point"]["pickDate"] = $order["shipping_dateStr"];
		}

		$smarty->assign("order", $order);
		$smarty->assign("address_id", $address_id);
		$smarty->assign("goods_list", $goods_list);
		$smarty->assign("feedback_num", $feedback_num);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "order_delete_restore") {
		include_once ("includes/cls_json.php");
		$_POST["order"] = strip_tags(urldecode($_POST["order"]));
		$_POST["order"] = json_str_iconv($_POST["order"]);
		$result = array("error" => 0, "message" => "", "content" => "", "order_id" => "");
		$json = new JSON();

		if (0 < $order->order_id) {
			$result["error"] = 1;
			exit($json->encode($result));
		}

		$order = $json->decode($_POST["order"]);
		$order_id = $order->order_id;
		$result["order_id"] = $order_id;

		if ($order->action == "delete") {
			$type = 1;
			$show_type = 0;
			$smarty->assign("action", "order_list");
		}
		else if ($order->action == "restore") {
			$type = 0;
			$show_type = 1;
			$smarty->assign("action", "order_recycle");
		}
		else if ($order->action == "thorough") {
			$show_type = 1;
			$smarty->assign("action", "order_recycle");
		}

		if ($order->action != "thorough") {
			$parent = array("is_delete" => $type);
			$GLOBALS["db"]->autoExecute($GLOBALS["ecs"]->table("order_info"), $parent, "UPDATE", "order_id = '$order_id'");
		}
		else {
			$main_order_id = $db->getOne("select main_order_id from " . $ecs->table("order_info") . " where order_id = '$order_id'");
			$db->query("UPDATE " . $ecs->table("order_info") . " SET is_delete = 2 where order_id = '$order_id'");
			$sql = "SELECT order_status, shipping_status, pay_status FROM " . $ecs->table("order_info") . " WHERE order_id = '$order_id'";
			$order_info = $db->getRow($sql);
			$parent = array("order_id" => $order_id, "action_user" => "买家", "order_status" => $order_info["order_status"], "shipping_status" => $order_info["shipping_status"], "pay_status" => $order_info["pay_status"], "action_note" => "删除订单", "log_time" => gmtime());
			$GLOBALS["db"]->autoExecute($GLOBALS["ecs"]->table("order_action"), $parent, "INSERT");
		}

		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table("order_info") . " WHERE user_id = '$user_id' and is_delete = '$show_type'");
		$action = "order_list";
		$pager = get_pager("user.php", array("act" => $action), $record_count, $page);
		$orders = get_user_orders($user_id, $pager["size"], $pager["start"], $show_type);
		$smarty->assign("pager", $pager);
		$smarty->assign("orders", $orders);
		$result["content"] = $smarty->fetch("library/user_order_list.lbi");
		$result["page_content"] = $smarty->fetch("library/pages.lbi");
		exit($json->encode($result));
	}
	else if ($action == "order_to_query") {
		include_once ("includes/cls_json.php");
		$_POST["order"] = strip_tags(urldecode($_POST["order"]));
		$_POST["order"] = json_str_iconv($_POST["order"]);
		$result = array("error" => 0, "message" => "", "content" => "", "order_id" => "");
		$json = new JSON();
		$order = $json->decode($_POST["order"]);

		if (0 < $order->order_id) {
			$result["error"] = 1;
			exit($json->encode($result));
		}

		if ($order->action == "order_list") {
			$show_type = 0;
		}
		else {
			$show_type = 1;
		}

		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$where = get_order_search_keyword($order);
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$record_count = $db->getAll("SELECT oi.order_id FROM " . $ecs->table("order_info") . " as oi left join " . $ecs->table("order_goods") . " as og on oi.order_id = og.order_id WHERE oi.user_id = '$user_id' and oi.is_delete = '$show_type'  and (select count(*) from " . $GLOBALS["ecs"]->table("order_info") . " as oi_2 where oi_2.main_order_id = oi.order_id) = 0 " . $where . " group by oi.order_id");
		$record_count = count($record_count);
		$orders = get_user_orders($user_id, $record_count, $page, $show_type, $where, $order);

		if ($order->idTxt == "submitDate") {
			$date_keyword = $order->keyword;
			$status_keyword = $order->status_keyword;
		}
		else if ($order->idTxt == "status_list") {
			$date_keyword = $order->date_keyword;
			$status_keyword = $order->keyword;
		}
		else {
			if (($order->idTxt == "payId") || ($order->idTxt == "to_finished") || ($order->idTxt == "to_confirm_order") || ($order->idTxt == "to_unconfirmed")) {
				$status_keyword = $order->keyword;
			}
		}

		$result["date_keyword"] = $date_keyword;
		$result["status_keyword"] = $status_keyword;
		$smarty->assign("orders", $orders);
		$smarty->assign("status_list", $_LANG["cs"]);
		$smarty->assign("date_keyword", $date_keyword);
		$smarty->assign("status_keyword", $status_keyword);
		$smarty->assign("action", $order->action);
		$result["content"] = $smarty->fetch("library/user_order_list.lbi");
		exit($json->encode($result));
	}
	else if ($action == "cancel_order") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		$order_id = (isset($_GET["order_id"]) ? intval($_GET["order_id"]) : 0);

		if (cancel_order($order_id, $user_id)) {
			ecs_header("Location: user.php?act=order_list\n");
			exit();
		}
		else {
			$err->show($_LANG["order_list_lnk"], "user.php?act=order_list");
		}
	}
	else if ($action == "address_list") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "languages/" . $_CFG["lang"] . "/shopping_flow.php");
		$smarty->assign("lang", $_LANG);
		$smarty->assign("country_list", get_regions());
		$smarty->assign("shop_province_list", get_regions(1, $_CFG["shop_country"]));
		$consignee_list = get_consignee_list($_SESSION["user_id"]);
		if ((count($consignee_list) < 5) && (0 < $_SESSION["user_id"])) {
			$consignee_list[] = array("country" => $_CFG["shop_country"], "email" => isset($_SESSION["email"]) ? $_SESSION["email"] : "");
		}

		$smarty->assign("consignee_list", $consignee_list);

		foreach ($consignee_list as $region_id => $consignee ) {
			$consignee["country"] = (isset($consignee["country"]) ? intval($consignee["country"]) : 0);
			$consignee["province"] = (isset($consignee["province"]) ? intval($consignee["province"]) : 0);
			$consignee["city"] = (isset($consignee["city"]) ? intval($consignee["city"]) : 0);
			$province_list[$region_id] = get_regions(1, $consignee["country"]);
			$city_list[$region_id] = get_regions(2, $consignee["province"]);
			$district_list[$region_id] = get_regions(3, $consignee["city"]);
		}

		$address_id = $db->getOne("SELECT address_id FROM " . $ecs->table("users") . " WHERE user_id='$user_id'");
		$new_consignee_list = get_new_consignee_list($_SESSION["user_id"]);
		$smarty->assign("new_consignee_list", $new_consignee_list);
		$smarty->assign("count_consignee", count($new_consignee_list));
		$smarty->assign("real_goods_count", 1);
		$smarty->assign("shop_country", $_CFG["shop_country"]);
		$smarty->assign("shop_province", get_regions(1, $_CFG["shop_country"]));
		$smarty->assign("province_list", $province_list);
		$smarty->assign("address", $address_id);
		$smarty->assign("city_list", $city_list);
		$smarty->assign("district_list", $district_list);
		$smarty->assign("currency_format", $_CFG["currency_format"]);
		$smarty->assign("integral_scale", $_CFG["integral_scale"]);
		$smarty->assign("name_of_region", array($_CFG["name_of_region_1"], $_CFG["name_of_region_2"], $_CFG["name_of_region_3"], $_CFG["name_of_region_4"]));
		if (!isset($_REQUEST["from_flow"]) && ($_REQUEST["from_flow"] != 1)) {
			$_SESSION["browse_trace"] = "user.php?act=address_list";
		}

		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "address") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$address_id = (isset($_GET["aid"]) ? intval($_GET["aid"]) : 0);
		$smarty->assign("province_list", get_regions(1, 1));
		$consignee = get_user_address_info($address_id);
		$sql = "SELECT address_id FROM " . $ecs->table("users") . " WHERE user_id = '$user_id'";
		$default_address = $db->getOne($sql);

		if ($address_id) {
			$province_list = get_regions(1, 1);
			$city_list = get_regions(2, $consignee["province"]);
			$district_list = get_regions(3, $consignee["city"]);
			$smarty->assign("province_list", $province_list);
			$smarty->assign("city_list", $city_list);
			$smarty->assign("district_list", $district_list);
		}

		$smarty->assign("consignee", $consignee);
		$smarty->assign("address_id", $address_id);
		$smarty->assign("default_address", $default_address);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "ajax_BatchCancelFollow") {
		include_once ("includes/cls_json.php");
		$json = new JSON();
		$res = array("err_msg" => "", "result" => "", "error" => 0);
		$type = (isset($_REQUEST["type"]) ? intval($_REQUEST["type"]) : 0);
		$goods_id = (isset($_REQUEST["goods_id"]) ? trim($_REQUEST["goods_id"]) : "");

		if ($type == 0) {
			$is_attention = 1;
		}
		else if ($type == 1) {
			$is_attention = 0;
		}

		if (!empty($goods_id)) {
			if (($type == 0) || ($type == 1)) {
				$sql = "UPDATE " . $ecs->table("collect_goods") . " SET is_attention = $is_attention WHERE goods_id in($goods_id)";
			}
			else if ($type == 2) {
				$sql = "DELETE FROM" . $ecs->table("collect_goods") . " WHERE goods_id in($goods_id)";
			}

			$db->query($sql);
		}

		$res["goods_id"] = $goods_id;
		exit($json->encode($res));
	}
	else if ($action == "ajax_del_address") {
		include_once ("includes/cls_json.php");
		$json = new JSON();
		$res = array("err_msg" => "", "result" => "", "error" => 0);
		$address_id = (isset($_REQUEST["address_id"]) ? intval($_REQUEST["address_id"]) : 0);
		$sql = "DELETE FROM " . $ecs->table("user_address") . " WHERE address_id = '$address_id'";
		$db->query($sql);
		$res["address_id"] = $address_id;
		exit($json->encode($res));
	}
	else if ($action == "ajax_update_address") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once ("includes/cls_json.php");
		$json = new JSON();
		$res = array("err_msg" => "", "result" => "", "error" => 0);
		$address_id = (isset($_REQUEST["address_id"]) ? intval($_REQUEST["address_id"]) : 0);
		$address = get_user_address_info($address_id);
		$smarty->assign("address", $address);
		$new_province_list = get_regions(1, $address["country"]);
		$new_city_list = get_regions(2, $address["province"]);
		$new_district_list = get_regions(3, $address["city"]);
		$smarty->assign("country_list", get_regions());
		$smarty->assign("new_province_list", $new_province_list);
		$smarty->assign("new_city_list", $new_city_list);
		$smarty->assign("new_district_list", $new_district_list);
		$res["content"] = $smarty->fetch("library/user_editaddress.lbi");
		exit($json->encode($res));
	}
	else if ($action == "ajax_add_address") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "languages/" . $_CFG["lang"] . "/shopping_flow.php");
		include_once ("includes/cls_json.php");
		$_POST["user_address"] = strip_tags(urldecode($_POST["user_address"]));
		$_POST["user_address"] = json_str_iconv($_POST["user_address"]);
		$result = array("error" => 0, "message" => "", "content" => "");
		$json = new JSON();
		$user_address = $json->decode($_POST["user_address"]);
		$address = array("user_id" => $user_id, "address_id" => intval($user_address->address_id), "country" => isset($user_address->country) ? intval($user_address->country) : 0, "province" => isset($user_address->province) ? intval($user_address->province) : 0, "city" => isset($user_address->city) ? intval($user_address->city) : 0, "district" => isset($user_address->district) ? intval($user_address->district) : 0, "address" => isset($user_address->address) ? compile_str(trim($user_address->address)) : "", "consignee" => isset($user_address->consignee) ? compile_str(trim($user_address->consignee)) : "", "email" => isset($user_address->email) ? compile_str(trim($user_address->email)) : "", "tel" => isset($user_address->tel) ? compile_str(make_semiangle(trim($user_address->tel))) : "", "mobile" => isset($user_address->mobile) ? compile_str(make_semiangle(trim($user_address->mobile))) : "", "best_time" => isset($user_address->best_time) ? compile_str(trim($user_address->best_time)) : "", "sign_building" => isset($user_address->sign_building) ? compile_str(trim($user_address->sign_building)) : "", "zipcode" => isset($user_address->zipcode) ? compile_str(make_semiangle(trim($user_address->zipcode))) : "");

		if (!update_address($address)) {
			$result["error"] = 1;
			$result["edit_address_failure"] = "您的收货地址信息更新失败";
		}
		else {
			$result["browse_trace"] = $_SESSION["browse_trace"];
		}

		exit($json->encode($result));
	}
	else if ($action == "ajax_make_address") {
		include_once ("includes/cls_json.php");
		$json = new JSON();
		$res = array("err_msg" => "", "result" => "", "error" => 0);
		$address_id = (isset($_REQUEST["address_id"]) ? intval($_REQUEST["address_id"]) : 0);
		$sql = "UPDATE " . $ecs->table("users") . " SET address_id = '$address_id' WHERE user_id = '$user_id'";
		$db->query($sql);
		$res["address_id"] = $address_id;
		exit($json->encode($res));
	}
	else if ($action == "act_edit_address") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "languages/" . $_CFG["lang"] . "/shopping_flow.php");
		$smarty->assign("lang", $_LANG);
		$default = (isset($_POST["default"]) ? intval($_POST["default"]) : 0);
		$address = array("user_id" => $user_id, "address_id" => intval($_POST["address_id"]), "country" => isset($_POST["country"]) ? intval($_POST["country"]) : 1, "province" => isset($_POST["province"]) ? intval($_POST["province"]) : 0, "city" => isset($_POST["city"]) ? intval($_POST["city"]) : 0, "district" => isset($_POST["district"]) ? intval($_POST["district"]) : 0, "address" => isset($_POST["address"]) ? compile_str(trim($_POST["address"])) : "", "consignee" => isset($_POST["consignee"]) ? compile_str(trim($_POST["consignee"])) : "", "email" => isset($_POST["email"]) ? compile_str(trim($_POST["email"])) : "", "tel" => isset($_POST["tel"]) ? compile_str(make_semiangle(trim($_POST["tel"]))) : "", "mobile" => isset($_POST["mobile"]) ? compile_str(make_semiangle(trim($_POST["mobile"]))) : "", "best_time" => isset($_POST["best_time"]) ? trim($_POST["best_time"]) : "", "sign_building" => isset($_POST["sign_building"]) ? compile_str(trim($_POST["sign_building"])) : "", "zipcode" => isset($_POST["zipcode"]) ? compile_str(make_semiangle(trim($_POST["zipcode"]))) : "");
		if (!$address["user_id"] || !$address["province"] || !$address["mobile"] || !$address["address"] || !$address["consignee"]) {
			show_message("收货人信息不完善！", "返回上一页", "", "error");
		}

		if (update_address($address, $default)) {
			ecs_header("Location: user.php?act=address_list\n");
		}
	}
	else if ($action == "drop_consignee") {
		include_once ("includes/lib_transaction.php");
		$consignee_id = intval($_GET["id"]);

		if (drop_consignee($consignee_id)) {
			ecs_header("Location: user.php?act=address_list\n");
			exit();
		}
		else {
			show_message($_LANG["del_address_false"]);
		}
	}
	else if ($action == "collection_list") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table("collect_goods") . " AS cg, " . $GLOBALS["ecs"]->table("goods") . " AS g  WHERE cg.goods_id = g.goods_id AND cg.user_id='$user_id'");
		$collection_goods = get_collection_goods($user_id, $record_count, $page, "collection_goods_gotoPage");
		$smarty->assign("goods_list", $collection_goods["goods_list"]);
		$smarty->assign("paper", $collection_goods["paper"]);
		$smarty->assign("record_count", $collection_goods["record_count"]);
		$smarty->assign("size", $collection_goods["size"]);
		$smarty->assign("url", $ecs->url());
		$lang_list = array("UTF8" => $_LANG["charset"]["utf8"], "GB2312" => $_LANG["charset"]["zh_cn"], "BIG5" => $_LANG["charset"]["zh_tw"]);
		$smarty->assign("lang_list", $lang_list);
		$smarty->assign("user_id", $user_id);
		$smarty->display("user_clips.dwt");
	}
	else if ($action == "store_list") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table("collect_store") . " WHERE user_id='$user_id'");
		$collection_store = get_collection_store($user_id, $record_count, $page, "collection_store_gotoPage");
		$smarty->assign("store_list", $collection_store["store_list"]);
		$smarty->assign("paper", $collection_store["paper"]);
		$smarty->assign("record_count", $collection_store["record_count"]);
		$smarty->assign("size", $collection_store["size"]);
		$smarty->assign("url", $ecs->url());
		$lang_list = array("UTF8" => $_LANG["charset"]["utf8"], "GB2312" => $_LANG["charset"]["zh_cn"], "BIG5" => $_LANG["charset"]["zh_tw"]);
		$smarty->assign("lang_list", $lang_list);
		$smarty->assign("user_id", $user_id);
		$smarty->display("user_clips.dwt");
	}
	else if ($action == "delete_collection") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$type = (isset($_GET["type"]) ? intval($_GET["type"]) : 0);
		$collection_id = (isset($_GET["collection_id"]) ? intval($_GET["collection_id"]) : 0);
		$sql = "SELECT count(*) FROM " . $ecs->table("collect_goods") . " WHERE rec_id='$collection_id' AND user_id ='$user_id'";

		if (0 < $db->getOne($sql)) {
			$db->query("DELETE FROM " . $ecs->table("collect_goods") . " WHERE rec_id='$collection_id' AND user_id ='$user_id'");
		}

		if ($type == 1) {
			ecs_header("Location: user.php?act=collection_list\n");
		}
		else {
			ecs_header("Location: user.php\n");
		}
	}
	else if ($action == "add_to_attention") {
		$rec_id = (int) $_GET["rec_id"];

		if ($rec_id) {
			$db->query("UPDATE " . $ecs->table("collect_goods") . "SET is_attention = 1 WHERE rec_id='$rec_id' AND user_id ='$user_id'");
		}

		ecs_header("Location: user.php?act=collection_list\n");
		exit();
	}
	else if ($action == "del_attention") {
		$rec_id = (int) $_GET["rec_id"];

		if ($rec_id) {
			$db->query("UPDATE " . $ecs->table("collect_goods") . "SET is_attention = 0 WHERE rec_id='$rec_id' AND user_id ='$user_id'");
		}

		ecs_header("Location: user.php?act=collection_list\n");
		exit();
	}
	else if ($action == "message_list") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$order_id = (empty($_GET["order_id"]) ? 0 : intval($_GET["order_id"]));
		$order_info = array();

		if ($order_id) {
			$sql = "SELECT COUNT(*) FROM " . $ecs->table("feedback") . " WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$user_id' AND msg_status = 0 ";
			$order_info = $db->getRow("SELECT * FROM " . $ecs->table("order_info") . " WHERE order_id = '$order_id' AND user_id = '$user_id'");
			$order_info["url"] = "user.php?act=order_detail&order_id=" . $order_id;
		}
		else {
			$sql = "SELECT COUNT(*) FROM " . $ecs->table("feedback") . " WHERE parent_id = 0 AND msg_status = 0  AND user_id = '$user_id' AND user_name = '" . $_SESSION["user_name"] . "' AND order_id=0";
		}

		$record_count = $db->getOne($sql);
		$act = array("act" => $action);

		if ($order_id != "") {
			$act["order_id"] = $order_id;
		}

		$pager = get_pager("user.php", $act, $record_count, $page, 5);
		$smarty->assign("message_list", get_message_list($user_id, $_SESSION["user_name"], $pager["size"], $pager["start"], $order_id));
		$smarty->assign("pager", $pager);
		$smarty->assign("order_info", $order_info);
		$smarty->display("user_clips.dwt");
	}
	else if ($action == "comment_list") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$sign = (isset($_REQUEST["sign"]) ? intval($_REQUEST["sign"]) : 0);
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$size = 10;
		$sql = "select id, comment_img, img_thumb from " . $ecs->table("comment_img") . " where user_id = '" . $_SESSION["user_id"] . "' AND comment_id = 0";
		$img_list = $db->getAll($sql);

		foreach ($img_list as $key => $val ) {
			get_oss_del_file(array($val["comment_img"], $val["img_thumb"]));
			@unlink(ROOT_PATH . $val["comment_img"]);
			@unlink(ROOT_PATH . $val["img_thumb"]);
		}

		$sql = "DELETE FROM " . $ecs->table("comment_img") . " WHERE user_id='{$_SESSION["user_id"]}' AND comment_id = 0";
		$db->query($sql);
		$record_count = get_user_order_comment_list($_SESSION["user_id"], 1, $sign);
		$pager = get_pager("user.php", array("act" => $action), $record_count, $page, $size);
		$comment_list = get_user_order_comment_list($_SESSION["user_id"], 0, $sign, 0, $size, $pager["start"]);
		$signNum0 = get_user_order_comment_list($_SESSION["user_id"], 1, 0);
		$signNum1 = get_user_order_comment_list($_SESSION["user_id"], 1, 1);
		$signNum2 = get_user_order_comment_list($_SESSION["user_id"], 1, 2);
		$smarty->assign("comment_list", $comment_list);
		$smarty->assign("pager", $pager);
		$smarty->assign("sign", $sign);
		$smarty->assign("signNum0", $signNum0);
		$smarty->assign("signNum1", $signNum1);
		$smarty->assign("signNum2", $signNum2);
		$smarty->assign("sessid", SESS_ID);
		$smarty->display("user_clips.dwt");
	}
	else if ($action == "commented_view") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$order_id = (isset($_REQUEST["order_id"]) ? intval($_REQUEST["order_id"]) : 0);
		$sign = (isset($_REQUEST["sign"]) ? intval($_REQUEST["sign"]) : 0);
		$sql = "DELETE FROM " . $ecs->table("comment_img") . " WHERE user_id='{$_SESSION["user_id"]}' AND comment_id = 0";
		$db->query($sql);
		$order_goods = get_user_order_comment_list($_SESSION["user_id"], 0, $sign, $order_id);
		$ru_id = (empty($order_goods[0]["ru_id"]) ? 0 : $order_goods[0]["ru_id"]);

		if ($ru_id) {
			$sql = "SELECT ru_id,logo_thumb,shop_name,kf_tel FROM " . $ecs->table("seller_shopinfo") . " WHERE ru_id = '$ru_id'";
			$shop_info = $db->getRow($sql);

			if ($shop_info["logo_thumb"]) {
				$shop_info["logo_thumb"] = substr($shop_info["logo_thumb"], 3);
			}

			$shop_info["logo_thumb"] = get_image_path($ru_id, $shop_info["logo_thumb"]);
			$shop_info["seller_score"] = 5;
			$sql = "SELECT SUM(service_rank) + SUM(desc_rank) + SUM(delivery_rank) + SUM(sender_rank) AS sum_rank, count(*) as num FROM " . $ecs->table("comment_seller") . " WHERE ru_id = '$ru_id'";
			$seller_row = $db->getRow($sql);

			if ($seller_row["num"]) {
				$shop_info["seller_score"] = $shop_info["sum_rank"] / $shop_info["num"] / 4;
			}
		}

		$sql = "SELECT COUNT(*) FROM " . $ecs->table("comment_seller") . " WHERE order_id = '$order_id' AND user_id = '{$_SESSION["user_id"]}'";
		$degree_count = $db->getOne($sql);
		$smarty->assign("order_goods", $order_goods);
		$smarty->assign("order_id", $order_id);
		$smarty->assign("degree_count", $degree_count);
		$smarty->assign("shop_info", $shop_info);
		$smarty->assign("sessid", SESS_ID);
		$smarty->display("user_clips.dwt");
	}
	else if ($action == "act_add_message") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$message = array("user_id" => $user_id, "user_name" => $_SESSION["user_name"], "user_email" => $_SESSION["email"], "msg_type" => isset($_POST["msg_type"]) ? intval($_POST["msg_type"]) : 0, "msg_title" => isset($_POST["msg_title"]) ? trim($_POST["msg_title"]) : "", "msg_content" => isset($_POST["msg_content"]) ? trim($_POST["msg_content"]) : "", "order_id" => empty($_POST["order_id"]) ? 0 : intval($_POST["order_id"]), "upload" => (isset($_FILES["message_img"]["error"]) && ($_FILES["message_img"]["error"] == 0)) || (!isset($_FILES["message_img"]["error"]) && isset($_FILES["message_img"]["tmp_name"]) && ($_FILES["message_img"]["tmp_name"] != "none")) ? $_FILES["message_img"] : array());

		if (add_message($message)) {
			show_message($_LANG["add_message_success"], $_LANG["message_list_lnk"], "user.php?act=message_list&order_id=" . $message["order_id"], "info");
		}
		else {
			$err->show($_LANG["message_list_lnk"], "user.php?act=message_list");
		}
	}
	else if ($action == "tag_list") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$good_id = (isset($_GET["id"]) ? intval($_GET["id"]) : 0);
		$smarty->assign("tags", get_user_tags($user_id));
		$smarty->assign("tags_from", "user");
		$smarty->display("user_clips.dwt");
	}
	else if ($action == "act_del_tag") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$tag_words = (isset($_GET["tag_words"]) ? trim($_GET["tag_words"]) : "");
		delete_tag($tag_words, $user_id);
		ecs_header("Location: user.php?act=tag_list\n");
		exit();
	}
	else if ($action == "booking_list") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$sql = "SELECT COUNT(*) FROM " . $ecs->table("booking_goods") . " AS bg, " . $ecs->table("goods") . " AS g WHERE bg.goods_id = g.goods_id AND bg.user_id = '$user_id'";
		$record_count = $db->getOne($sql);
		$pager = get_pager("user.php", array("act" => $action), $record_count, $page);
		$smarty->assign("booking_list", get_booking_list($user_id, $pager["size"], $pager["start"]));
		$smarty->assign("pager", $pager);
		$smarty->display("user_clips.dwt");
	}
	else if ($action == "add_booking") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$goods_id = (isset($_GET["id"]) ? intval($_GET["id"]) : 0);

		if ($goods_id == 0) {
			show_message($_LANG["no_goods_id"], $_LANG["back_page_up"], "", "error");
		}

		$goods_attr = "";

		if ($_GET["spec"] != "") {
			$goods_attr_id = $_GET["spec"];
			$attr_list = array();
			$sql = "SELECT a.attr_name, g.attr_value FROM " . $ecs->table("goods_attr") . " AS g, " . $ecs->table("attribute") . " AS a WHERE g.attr_id = a.attr_id AND g.goods_attr_id " . db_create_in($goods_attr_id);
			$res = $db->query($sql);

			while ($row = $db->fetchRow($res)) {
				$attr_list[] = $row["attr_name"] . ": " . $row["attr_value"];
			}

			$goods_attr = join(chr(13) . chr(10), $attr_list);
		}

		$smarty->assign("goods_attr", $goods_attr);
		$smarty->assign("info", get_goodsinfo($goods_id));
		$smarty->display("user_clips.dwt");
	}
	else if ($action == "act_add_booking") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$booking = array("goods_id" => isset($_POST["id"]) ? intval($_POST["id"]) : 0, "goods_amount" => isset($_POST["number"]) ? intval($_POST["number"]) : 0, "desc" => isset($_POST["desc"]) ? trim($_POST["desc"]) : "", "linkman" => isset($_POST["linkman"]) ? trim($_POST["linkman"]) : "", "email" => isset($_POST["email"]) ? trim($_POST["email"]) : "", "tel" => isset($_POST["tel"]) ? trim($_POST["tel"]) : "", "booking_id" => isset($_POST["rec_id"]) ? intval($_POST["rec_id"]) : 0);
		$rec_id = get_booking_rec($user_id, $booking["goods_id"]);

		if (0 < $rec_id) {
			show_message($_LANG["booking_rec_exist"], $_LANG["back_page_up"], "", "error");
		}

		if (add_booking($booking)) {
			show_message($_LANG["booking_success"], $_LANG["back_booking_list"], "user.php?act=booking_list", "info");
		}
		else {
			$err->show($_LANG["booking_list_lnk"], "user.php?act=booking_list");
		}
	}
	else if ($action == "act_del_booking") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$id = (isset($_GET["id"]) ? intval($_GET["id"]) : 0);
		if (($id == 0) || ($user_id == 0)) {
			ecs_header("Location: user.php?act=booking_list\n");
			exit();
		}

		$result = delete_booking($id, $user_id);

		if ($result) {
			ecs_header("Location: user.php?act=booking_list\n");
			exit();
		}
	}
	else if ($action == "affirm_received") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$order_id = (isset($_GET["order_id"]) ? intval($_GET["order_id"]) : 0);

		if (affirm_received($order_id, $user_id)) {
			ecs_header("Location: user.php?act=order_list\n");
			exit();
		}
		else {
			$err->show($_LANG["order_list_lnk"], "user.php?act=order_list");
		}
	}
	else if ($action == "account_raply") {
		$user_info = get_user_default($_SESSION["user_id"]);
		$smarty->assign("user_info", $user_info);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "account_deposit") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$surplus_id = (isset($_GET["id"]) ? intval($_GET["id"]) : 0);
		$account = get_surplus_info($surplus_id);
		$user_info = get_user_default($_SESSION["user_id"]);
		$smarty->assign("payment", get_online_payment_list(false));
		$smarty->assign("order", $account);
		$smarty->assign("user_info", $user_info);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "account_detail") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$account_type = "user_money";
		$sql = "SELECT COUNT(*) FROM " . $ecs->table("account_log") . " WHERE user_id = '$user_id' AND $account_type <> 0 ";
		$record_count = $db->getOne($sql);
		$pager = get_pager("user.php", array("act" => $action), $record_count, $page);
		$sql = "SELECT user_money FROM " . $GLOBALS["ecs"]->table("users") . " WHERE user_id = '$user_id'";
		$surplus_amount = $GLOBALS["db"]->getOne($sql);

		if (empty($surplus_amount)) {
			$surplus_amount = 0;
		}

		$account_log = array();
		$sql = "SELECT * FROM " . $ecs->table("account_log") . " WHERE user_id = '$user_id' AND $account_type <> 0  ORDER BY log_id DESC";
		$res = $GLOBALS["db"]->selectLimit($sql, $pager["size"], $pager["start"]);

		while ($row = $db->fetchRow($res)) {
			$row["change_time"] = local_date($_CFG["date_format"], $row["change_time"]);
			$row["type"] = (0 < $row[$account_type] ? $_LANG["account_inc"] : $_LANG["account_dec"]);
			$row["user_money"] = price_format(abs($row["user_money"]), false);
			$row["frozen_money"] = price_format(abs($row["frozen_money"]), false);
			$row["rank_points"] = abs($row["rank_points"]);
			$row["pay_points"] = abs($row["pay_points"]);
			$row["short_change_desc"] = sub_str($row["change_desc"], 60);
			$row["amount"] = $row[$account_type];
			$account_log[] = $row;
		}

		$smarty->assign("surplus_amount", price_format($surplus_amount, false));
		$smarty->assign("account_log", $account_log);
		$smarty->assign("pager", $pager);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "account_log") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$sql = "SELECT COUNT(*) FROM " . $ecs->table("user_account") . " WHERE user_id = '$user_id' AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN));
		$record_count = $db->getOne($sql);
		$pager = get_pager("user.php", array("act" => $action), $record_count, $page);
		$sql = "SELECT user_money FROM " . $GLOBALS["ecs"]->table("users") . " WHERE user_id = '$user_id'";
		$surplus_amount = $GLOBALS["db"]->getOne($sql);

		if (empty($surplus_amount)) {
			$surplus_amount = 0;
		}

		$account_log = get_account_log($user_id, $pager["size"], $pager["start"]);
		$smarty->assign("surplus_amount", price_format($surplus_amount, false));
		$smarty->assign("account_log", $account_log);
		$smarty->assign("pager", $pager);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "act_account") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		$amount = (isset($_POST["amount"]) ? floatval($_POST["amount"]) : 0);

		if ($amount <= 0) {
			show_message($_LANG["amount_gt_zero"]);
		}

		$surplus = array("user_id" => $user_id, "rec_id" => !empty($_POST["rec_id"]) ? intval($_POST["rec_id"]) : 0, "process_type" => isset($_POST["surplus_type"]) ? intval($_POST["surplus_type"]) : 0, "payment_id" => isset($_POST["payment_id"]) ? intval($_POST["payment_id"]) : 0, "user_note" => isset($_POST["user_note"]) ? trim($_POST["user_note"]) : "", "amount" => $amount);

		if ($surplus["process_type"] == 1) {
			$sur_amount = get_user_surplus($user_id);

			if ($sur_amount < $amount) {
				$content = $_LANG["surplus_amount_error"];
				show_message($content, $_LANG["back_page_up"], "", "info");
			}

			$amount = "-" . $amount;
			$surplus["payment"] = "";
			$surplus["rec_id"] = insert_user_account($surplus, $amount);

			if (0 < $surplus["rec_id"]) {
				$user_account_fields = array("user_id" => $surplus["user_id"], "account_id" => $surplus["rec_id"], "bank_number" => !empty($_POST["bank_number"]) ? trim($_POST["bank_number"]) : "", "real_name" => !empty($_POST["real_name"]) ? trim($_POST["real_name"]) : "");
				insert_user_account_fields($user_account_fields);
				$content = $_LANG["surplus_appl_submit"];
				show_message($content, $_LANG["back_account_log"], "user.php?act=account_log", "info");
			}
			else {
				$content = $_LANG["process_false"];
				show_message($content, $_LANG["back_page_up"], "", "info");
			}
		}
		else {
			if ($surplus["payment_id"] <= 0) {
				show_message($_LANG["select_payment_pls"]);
			}

			include_once (ROOT_PATH . "includes/lib_payment.php");
			$payment_info = array();
			$payment_info = payment_info($surplus["payment_id"]);
			$surplus["payment"] = $payment_info["pay_name"];

			if (0 < $surplus["rec_id"]) {
				$surplus["rec_id"] = update_user_account($surplus);
			}
			else {
				$surplus["rec_id"] = insert_user_account($surplus, $amount);
			}

			$payment = unserialize_config($payment_info["pay_config"]);
			$order = array();
			$order["order_sn"] = $surplus["rec_id"];
			$order["user_name"] = $_SESSION["user_name"];
			$order["surplus_amount"] = $amount;
			$payment_info["pay_fee"] = pay_fee($surplus["payment_id"], $order["surplus_amount"], 0);
			$order["order_amount"] = $amount + $payment_info["pay_fee"];
			$order["log_id"] = insert_pay_log($surplus["rec_id"], $order["order_amount"], $type = PAY_SURPLUS, 0);
			include_once (ROOT_PATH . "includes/modules/payment/" . $payment_info["pay_code"] . ".php");
			$pay_obj = new $payment_info["pay_code"]();
			$payment_info["pay_button"] = $pay_obj->get_code($order, $payment);
			$smarty->assign("payment", $payment_info);
			$smarty->assign("pay_fee", price_format($payment_info["pay_fee"], false));
			$smarty->assign("amount", price_format($amount, false));
			$smarty->assign("order", $order);
			$smarty->display("user_transaction.dwt");
		}
	}
	else if ($action == "cancel") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		$id = (isset($_GET["id"]) ? intval($_GET["id"]) : 0);
		if (($id == 0) || ($user_id == 0)) {
			ecs_header("Location: user.php?act=account_log\n");
			exit();
		}

		$result = del_user_account($id, $user_id);

		if ($result) {
			del_user_account_fields($id, $user_id);
			ecs_header("Location: user.php?act=account_log\n");
			exit();
		}
	}
	else if ($action == "pay") {
		include_once (ROOT_PATH . "includes/lib_clips.php");
		include_once (ROOT_PATH . "includes/lib_payment.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		$surplus_id = (isset($_GET["id"]) ? intval($_GET["id"]) : 0);
		$payment_id = (isset($_GET["pid"]) ? intval($_GET["pid"]) : 0);

		if ($surplus_id == 0) {
			ecs_header("Location: user.php?act=account_log\n");
			exit();
		}

		if ($payment_id == 0) {
			ecs_header("Location: user.php?act=account_deposit&id=" . $surplus_id . "\n");
			exit();
		}

		$order = array();
		$order = get_surplus_info($surplus_id);
		$payment_info = array();
		$payment_info = payment_info($payment_id);

		if (!empty($payment_info)) {
			$payment = unserialize_config($payment_info["pay_config"]);
			$order["order_sn"] = $surplus_id;
			$order["log_id"] = get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);
			$order["user_name"] = $_SESSION["user_name"];
			$order["surplus_amount"] = $order["amount"];
			$payment_info["pay_fee"] = pay_fee($payment_id, $order["surplus_amount"], 0);
			$order["order_amount"] = $order["surplus_amount"] + $payment_info["pay_fee"];
			$order_amount = $db->getOne("SELECT order_amount FROM " . $ecs->table("pay_log") . " WHERE log_id = '{$order["log_id"]}'");

			if ($order_amount != $order["order_amount"]) {
				$db->query("UPDATE " . $ecs->table("pay_log") . " SET order_amount = '{$order["order_amount"]}' WHERE log_id = '{$order["log_id"]}'");
			}

			include_once (ROOT_PATH . "includes/modules/payment/" . $payment_info["pay_code"] . ".php");
			$pay_obj = new $payment_info["pay_code"]();
			$payment_info["pay_button"] = $pay_obj->get_code($order, $payment);
			$smarty->assign("payment", $payment_info);
			$smarty->assign("order", $order);
			$smarty->assign("pay_fee", price_format($payment_info["pay_fee"], false));
			$smarty->assign("amount", price_format($order["surplus_amount"], false));
			$smarty->assign("action", "act_account");
			$smarty->display("user_transaction.dwt");
		}
		else {
			include_once (ROOT_PATH . "includes/lib_clips.php");
			$smarty->assign("payment", get_online_payment_list());
			$smarty->assign("order", $order);
			$smarty->assign("action", "account_deposit");
			$smarty->display("user_transaction.dwt");
		}
	}
	else if ($action == "add_tag") {
		include_once ("includes/cls_json.php");
		include_once ("includes/lib_clips.php");
		$result = array("error" => 0, "message" => "", "content" => "");
		$id = (isset($_POST["id"]) ? intval($_POST["id"]) : 0);
		$tag = (isset($_POST["tag"]) ? json_str_iconv(trim($_POST["tag"])) : "");

		if ($user_id == 0) {
			$result["error"] = 1;
			$result["message"] = $_LANG["tag_anonymous"];
		}
		else {
			add_tag($id, $tag);
			clear_cache_files("goods");
			$arr = get_tags($id);

			foreach ($arr as $row ) {
				$result["content"][] = array("word" => htmlspecialchars($row["tag_words"]), "count" => $row["tag_count"]);
			}
		}

		$json = new JSON();
		echo $json->encode($result);
		exit();
	}
	else if ($action == "collect") {
		include_once (ROOT_PATH . "includes/cls_json.php");
		$json = new JSON();
		$result = array("error" => 0, "message" => "", "url" => "");
		$goods_id = (isset($_GET["id"]) ? intval($_GET["id"]) : 0);
		$cat_id = (isset($_GET["cat_id"]) ? intval($_GET["cat_id"]) : 0);
		$merchant_id = (isset($_GET["merchant_id"]) ? intval($_GET["merchant_id"]) : 0);
		$script_name = (isset($_GET["script_name"]) ? htmlspecialchars(trim($_GET["script_name"])) : "");
		$keywords = (isset($_GET["keywords"]) ? htmlspecialchars(trim($_GET["keywords"])) : "");
		$cur_url = (isset($_GET["cur_url"]) ? htmlspecialchars(trim($_GET["cur_url"])) : "");
		if (!isset($_SESSION["user_id"]) || ($_SESSION["user_id"] == 0)) {
			if ($script_name != "") {
				if ($script_name == "category") {
					$result["url"] = get_return_category_url($cat_id);
				}
				else {
					if (($script_name == "search") || ($script_name == "merchants_shop")) {
						$result["url"] = $cur_url;
					}
					else if ($script_name == "merchants_store_shop") {
						$result["url"] = get_return_store_shop_url($merchant_id);
					}
				}
			}

			$result["error"] = 2;
			$result["message"] = $_LANG["login_please"];
			exit($json->encode($result));
		}
		else {
			$sql = "SELECT COUNT(*) FROM " . $GLOBALS["ecs"]->table("collect_goods") . " WHERE user_id='{$_SESSION["user_id"]}' AND goods_id = '$goods_id'";

			if (0 < $GLOBALS["db"]->GetOne($sql)) {
				$result["error"] = 1;
				$result["message"] = $GLOBALS["_LANG"]["collect_existed"];
				exit($json->encode($result));
			}
			else {
				$time = gmtime();
				$sql = "INSERT INTO " . $GLOBALS["ecs"]->table("collect_goods") . " (user_id, goods_id, add_time)VALUES ('{$_SESSION["user_id"]}', '$goods_id', '$time')";

				if ($GLOBALS["db"]->query($sql) === false) {
					$result["error"] = 1;
					$result["message"] = $GLOBALS["db"]->errorMsg();
					exit($json->encode($result));
				}
				else {
					$collect_count = get_collect_goods_user_count($goods_id);
					$result["collect_count"] = $collect_count;
					clear_all_files();
					$result["error"] = 0;
					$result["message"] = $GLOBALS["_LANG"]["collect_success"];
					exit($json->encode($result));
				}
			}
		}
	}
	else if ($action == "del_msg") {
		$id = (isset($_GET["id"]) ? intval($_GET["id"]) : 0);
		$order_id = (empty($_GET["order_id"]) ? 0 : intval($_GET["order_id"]));

		if (0 < $id) {
			$sql = "SELECT user_id, message_img FROM " . $ecs->table("feedback") . " WHERE msg_id = '$id' LIMIT 1";
			$row = $db->getRow($sql);
			if ($row && ($row["user_id"] == $user_id)) {
				if ($row["message_img"]) {
					@unlink(ROOT_PATH . DATA_DIR . "/feedbackimg/" . $row["message_img"]);
				}

				$sql = "DELETE FROM " . $ecs->table("feedback") . " WHERE msg_id = '$id' OR parent_id = '$id'";
				$db->query($sql);
			}
		}

		ecs_header("Location: user.php?act=message_list&order_id={$order_id}\n");
		exit();
	}
	else if ($action == "del_cmt") {
		$id = (isset($_GET["id"]) ? intval($_GET["id"]) : 0);

		if (0 < $id) {
			$sql = "DELETE FROM " . $ecs->table("comment") . " WHERE comment_id = '$id' AND user_id = '$user_id'";
			$db->query($sql);
		}

		ecs_header("Location: user.php?act=comment_list\n");
		exit();
	}
	else if ($action == "merge_order") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		$from_order = (isset($_POST["from_order"]) ? trim($_POST["from_order"]) : "");
		$to_order = (isset($_POST["to_order"]) ? trim($_POST["to_order"]) : "");

		if (merge_user_order($from_order, $to_order, $user_id)) {
			show_message($_LANG["merge_order_success"], $_LANG["order_list_lnk"], "user.php?act=order_list", "info");
		}
		else {
			$err->show($_LANG["order_list_lnk"]);
		}
	}
	else if ($action == "return_to_cart") {
		include_once (ROOT_PATH . "includes/cls_json.php");
		include_once (ROOT_PATH . "includes/lib_common.php");
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$json = new JSON();
		$result = array("error" => 0, "message" => "", "cart_info" => "");
		$order_id = (isset($_POST["order_id"]) ? intval($_POST["order_id"]) : 0);
		$recStr = (isset($_POST["rec_id"]) ? trim($_POST["rec_id"]) : "");
		$rec_id = array();

		if ($order_id == 0) {
			$result["error"] = 1;
			$result["message"] = $_LANG["order_id_empty"];
			exit($json->encode($result));
		}

		if ($user_id == 0) {
			$result["error"] = 1;
			$result["message"] = $_LANG["login_please"];
			exit($json->encode($result));
		}

		if (!empty($recStr)) {
			$rec_id = explode(",", $recStr);
		}

		$order_user = $db->getOne("SELECT user_id FROM " . $ecs->table("order_info") . " WHERE order_id = '$order_id'");

		if (empty($order_user)) {
			$result["error"] = 1;
			$result["message"] = $_LANG["order_exist"];
			exit($json->encode($result));
		}
		else if ($order_user != $user_id) {
			$result["error"] = 1;
			$result["message"] = $_LANG["no_priv"];
			exit($json->encode($result));
		}

		$message = return_to_cart($order_id, $rec_id);
		$cart_info = get_cart_info();
		$result["cart_info"] = $cart_info;

		if ($message === true) {
			$result["error"] = 0;
			$result["message"] = $_LANG["return_to_cart_success"];
			exit($json->encode($result));
		}
		else {
			$result["error"] = 1;
			$result["message"] = $_LANG["order_exist"];
			exit($json->encode($result));
		}
	}
	else if ($action == "act_edit_surplus") {
		if ($_SESSION["user_id"] <= 0) {
			ecs_header("Location: ./\n");
			exit();
		}

		$order_id = intval($_POST["order_id"]);

		if ($order_id <= 0) {
			ecs_header("Location: ./\n");
			exit();
		}

		$surplus = floatval($_POST["surplus"]);

		if ($surplus <= 0) {
			$err->add($_LANG["error_surplus_invalid"]);
			$err->show($_LANG["order_detail"], "user.php?act=order_detail&order_id=" . $order_id);
		}

		include_once (ROOT_PATH . "includes/lib_order.php");
		$order = order_info($order_id);

		if (empty($order)) {
			ecs_header("Location: ./\n");
			exit();
		}

		if ($_SESSION["user_id"] != $order["user_id"]) {
			ecs_header("Location: ./\n");
			exit();
		}

		if (($order["pay_status"] != PS_UNPAYED) || ($order["order_amount"] <= 0)) {
			$err->add($_LANG["error_order_is_paid"]);
			$err->show($_LANG["order_detail"], "user.php?act=order_detail&order_id=" . $order_id);
		}

		$order["order_amount"] -= $order["pay_fee"];

		if ($order["order_amount"] < $surplus) {
			$surplus = $order["order_amount"];
		}

		$user = user_info($_SESSION["user_id"]);

		if (($user["user_money"] + $user["credit_line"]) < $surplus) {
			$err->add($_LANG["error_surplus_not_enough"]);
			$err->show($_LANG["order_detail"], "user.php?act=order_detail&order_id=" . $order_id);
		}

		$order["surplus"] += $surplus;
		$order["order_amount"] -= $surplus;

		if (0 < $order["order_amount"]) {
			$cod_fee = 0;

			if (0 < $order["shipping_id"]) {
				$regions = array($order["country"], $order["province"], $order["city"], $order["district"]);
				$shipping = shipping_area_info($order["shipping_id"], $regions);

				if ($shipping["support_cod"] == "1") {
					$cod_fee = $shipping["pay_fee"];
				}
			}

			$pay_fee = 0;

			if (0 < $order["pay_id"]) {
				$pay_fee = pay_fee($order["pay_id"], $order["order_amount"], $cod_fee);
			}

			$order["pay_fee"] = $pay_fee;
			$order["order_amount"] += $pay_fee;
		}

		if ($order["order_amount"] == 0) {
			if ($order["order_status"] == OS_UNCONFIRMED) {
				$order["order_status"] = OS_CONFIRMED;
				$order["confirm_time"] = gmtime();
			}

			$order["pay_status"] = PS_PAYED;
			$order["pay_time"] = gmtime();
		}

		$order = addslashes_deep($order);
		update_order($order_id, $order);
		$change_desc = sprintf($_LANG["pay_order_by_surplus"], $order["order_sn"]);
		log_account_change($user["user_id"], -1 * $surplus, 0, 0, 0, $change_desc);
		ecs_header("Location: user.php?act=order_detail&order_id=" . $order_id . "\n");
		exit();
	}
	else if ($action == "act_edit_payment") {
		if ($_SESSION["user_id"] <= 0) {
			ecs_header("Location: ./\n");
			exit();
		}

		$pay_id = intval($_POST["pay_id"]);

		if ($pay_id <= 0) {
			ecs_header("Location: ./\n");
			exit();
		}

		include_once (ROOT_PATH . "includes/lib_order.php");
		$payment_info = payment_info($pay_id);

		if (empty($payment_info)) {
			ecs_header("Location: ./\n");
			exit();
		}

		$order_id = intval($_POST["order_id"]);

		if ($order_id <= 0) {
			ecs_header("Location: ./\n");
			exit();
		}

		$order = order_info($order_id);

		if (empty($order)) {
			ecs_header("Location: ./\n");
			exit();
		}

		if ($_SESSION["user_id"] != $order["user_id"]) {
			ecs_header("Location: ./\n");
			exit();
		}

		if ((($order["pay_status"] != PS_UNPAYED) && ($order["pay_status"] != PS_PAYED_PART)) || ($order["shipping_status"] != SS_UNSHIPPED) || ($order["order_amount"] <= 0) || ($order["pay_id"] == $pay_id)) {
			ecs_header("Location: user.php?act=order_detail&order_id={$order_id}\n");
			exit();
		}

		$order_amount = $order["order_amount"] - $order["pay_fee"];
		$pay_fee = pay_fee($pay_id, $order_amount);
		$order_amount += $pay_fee;
		$sql = "UPDATE " . $ecs->table("order_info") . " SET pay_id='$pay_id', pay_name='{$payment_info["pay_name"]}', pay_fee='$pay_fee', order_amount='$order_amount' WHERE order_id = '$order_id'";
		$db->query($sql);
		ecs_header("Location: user.php?act=order_detail&order_id={$order_id}\n");
		exit();
	}
	else if ($action == "save_order_address") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$address = array("consignee" => isset($_POST["consignee"]) ? compile_str(trim($_POST["consignee"])) : "", "email" => isset($_POST["email"]) ? compile_str(trim($_POST["email"])) : "", "address" => isset($_POST["address"]) ? compile_str(trim($_POST["address"])) : "", "zipcode" => isset($_POST["zipcode"]) ? compile_str(make_semiangle(trim($_POST["zipcode"]))) : "", "tel" => isset($_POST["tel"]) ? compile_str(trim($_POST["tel"])) : "", "mobile" => isset($_POST["mobile"]) ? compile_str(trim($_POST["mobile"])) : "", "sign_building" => isset($_POST["sign_building"]) ? compile_str(trim($_POST["sign_building"])) : "", "best_time" => isset($_POST["best_time"]) ? compile_str(trim($_POST["best_time"])) : "", "order_id" => isset($_POST["order_id"]) ? intval($_POST["order_id"]) : 0);

		if (save_order_address($address, $user_id)) {
			ecs_header("Location: user.php?act=order_detail&order_id=" . $address["order_id"] . "\n");
			exit();
		}
		else {
			$err->show($_LANG["order_list_lnk"], "user.php?act=order_list");
		}
	}
	else if ($action == "bonus") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$size = 10;
		$bonus = get_user_bouns_new_list($user_id, $page, 0, "bouns_available_gotoPage", 0, $size);
		$smarty->assign("bonus", $bonus);
		$bonus1 = get_user_bouns_new_list($user_id, $page, 1, "bouns_expire_gotoPage", 0, $size);
		$smarty->assign("bonus1", $bonus1);
		$bonus2 = get_user_bouns_new_list($user_id, $page, 2, "bouns_useup_gotoPage", 0, $size);
		$smarty->assign("bonus2", $bonus2);
		$smarty->assign("size", $size);
		$bonus3 = get_user_bouns_new_list($user_id, $page, 0, "", 1);
		$bouns_amount = get_bouns_amount_list($bonus3);
		$smarty->assign("bouns_amount", $bouns_amount);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "group_buy") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "group_buy_detail") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "affiliate") {
		$goodsid = intval(isset($_REQUEST["goodsid"]) ? $_REQUEST["goodsid"] : 0);

		if (empty($goodsid)) {
			$page = (!empty($_REQUEST["page"]) && (0 < intval($_REQUEST["page"])) ? intval($_REQUEST["page"]) : 1);
			$size = (!empty($_CFG["page_size"]) && (0 < intval($_CFG["page_size"])) ? intval($_CFG["page_size"]) : 10);
			empty($affiliate) && ($affiliate = array());

			if (empty($affiliate["config"]["separate_by"])) {
				$affdb = array();
				$num = count($affiliate["item"]);
				$up_uid = "'$user_id'";
				$all_uid = "'$user_id'";

				for ($i = 1; $i <= $num; $i++) {
					$count = 0;

					if ($up_uid) {
						$sql = "SELECT user_id FROM " . $ecs->table("users") . " WHERE parent_id IN($up_uid)";
						$query = $db->query($sql);
						$up_uid = "";

						while ($rt = $db->fetch_array($query)) {
							$up_uid .= ($up_uid ? ",'{$rt["user_id"]}'" : "'{$rt["user_id"]}'");

							if ($i < $num) {
								$all_uid .= ", '{$rt["user_id"]}'";
							}

							$count++;
						}
					}

					$affdb[$i]["num"] = $count;
					$affdb[$i]["point"] = $affiliate["item"][$i - 1]["level_point"];
					$affdb[$i]["money"] = $affiliate["item"][$i - 1]["level_money"];
				}

				$smarty->assign("affdb", $affdb);
				$sqlcount = "SELECT count(*) FROM " . $ecs->table("order_info") . " o LEFT JOIN" . $ecs->table("users") . " u ON o.user_id = u.user_id LEFT JOIN " . $ecs->table("affiliate_log") . " a ON o.order_id = a.order_id WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";
				$sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $ecs->table("order_info") . " o LEFT JOIN" . $ecs->table("users") . " u ON o.user_id = u.user_id LEFT JOIN " . $ecs->table("affiliate_log") . " a ON o.order_id = a.order_id WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0) ORDER BY order_id DESC";
				$affiliate_intro = nl2br(sprintf($_LANG["affiliate_intro"][$affiliate["config"]["separate_by"]], $affiliate["config"]["expire"], $_LANG["expire_unit"][$affiliate["config"]["expire_unit"]], $affiliate["config"]["level_register_all"], $affiliate["config"]["level_register_up"], $affiliate["config"]["level_money_all"], $affiliate["config"]["level_point_all"]));
			}
			else {
				$sqlcount = "SELECT count(*) FROM " . $ecs->table("order_info") . " o LEFT JOIN" . $ecs->table("users") . " u ON o.user_id = u.user_id LEFT JOIN " . $ecs->table("affiliate_log") . " a ON o.order_id = a.order_id WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";
				$sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $ecs->table("order_info") . " o LEFT JOIN" . $ecs->table("users") . " u ON o.user_id = u.user_id LEFT JOIN " . $ecs->table("affiliate_log") . " a ON o.order_id = a.order_id WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0) ORDER BY order_id DESC";
				$affiliate_intro = nl2br(sprintf($_LANG["affiliate_intro"][$affiliate["config"]["separate_by"]], $affiliate["config"]["expire"], $_LANG["expire_unit"][$affiliate["config"]["expire_unit"]], $affiliate["config"]["level_money_all"], $affiliate["config"]["level_point_all"]));
			}

			$count = $db->getOne($sqlcount);
			$max_page = (0 < $count ? ceil($count / $size) : 1);

			if ($max_page < $page) {
				$page = $max_page;
			}

			$res = $db->SelectLimit($sql, $size, ($page - 1) * $size);
			$logdb = array();

			while ($rt = $GLOBALS["db"]->fetchRow($res)) {
				if (!empty($rt["suid"])) {
					if (($rt["separate_type"] == -1) || ($rt["separate_type"] == -2)) {
						$rt["is_separate"] = 3;
					}
				}

				$rt["order_sn"] = substr($rt["order_sn"], 0, strlen($rt["order_sn"]) - 5) . "***" . substr($rt["order_sn"], -2, 2);
				$logdb[] = $rt;
			}

			$url_format = "user.php?act=affiliate&page=";
			$pager = array(
				"page"         => $page,
				"size"         => $size,
				"sort"         => "",
				"order"        => "",
				"record_count" => $count,
				"page_count"   => $max_page,
				"page_first"   => $url_format . "1",
				"page_prev"    => 1 < $page ? $url_format . ($page - 1) : "javascript:;",
				"page_next"    => $page < $max_page ? $url_format . ($page + 1) : "javascript:;",
				"page_last"    => $url_format . $max_page,
				"array"        => array()
				);

			for ($i = 1; $i <= $max_page; $i++) {
				$pager["array"][$i] = $i;
			}

			$smarty->assign("url_format", $url_format);
			$smarty->assign("pager", $pager);
			$smarty->assign("affiliate_intro", $affiliate_intro);
			$smarty->assign("affiliate_type", $affiliate["config"]["separate_by"]);
			$smarty->assign("logdb", $logdb);
		}
		else {
			$smarty->assign("userid", $user_id);
			$smarty->assign("goodsid", $goodsid);
			$types = array(1, 2, 3, 4, 5);
			$smarty->assign("types", $types);
			$goods = get_goods_info($goodsid);
			$shopurl = $ecs->url();
			$goods["goods_img"] = ((strpos($goods["goods_img"], "http://") === false) && (strpos($goods["goods_img"], "https://") === false) ? $shopurl . $goods["goods_img"] : $goods["goods_img"]);
			$goods["goods_thumb"] = ((strpos($goods["goods_thumb"], "http://") === false) && (strpos($goods["goods_thumb"], "https://") === false) ? $shopurl . $goods["goods_thumb"] : $goods["goods_thumb"]);
			$goods["shop_price"] = price_format($goods["shop_price"]);
			$smarty->assign("goods", $goods);
		}

		$smarty->assign("shopname", $_CFG["shop_name"]);
		$smarty->assign("userid", $user_id);
		$smarty->assign("shopurl", $ecs->url());
		$smarty->assign("logosrc", "themes/" . $_CFG["template"] . "/images/logo.gif");
		$smarty->display("user_clips.dwt");
	}
	else if ($action == "email_list") {
		$job = $_GET["job"];
		if (($job == "add") || ($job == "del")) {
			if (isset($_SESSION["last_email_query"])) {
				if ((time() - $_SESSION["last_email_query"]) <= 30) {
					exit($_LANG["order_query_toofast"]);
				}
			}

			$_SESSION["last_email_query"] = time();
		}

		$email = trim($_GET["email"]);
		$email = htmlspecialchars($email);

		if (!is_email($email)) {
			$info = sprintf($_LANG["email_invalid"], $email);
			exit($info);
		}

		$ck = $db->getRow("SELECT * FROM " . $ecs->table("email_list") . " WHERE email = '$email'");

		if ($job == "add") {
			if (empty($ck)) {
				$hash = substr(md5(time()), 1, 10);
				$sql = "INSERT INTO " . $ecs->table("email_list") . " (email, stat, hash) VALUES ('$email', 0, '$hash')";
				$db->query($sql);
				$info = $_LANG["email_check"];
				$url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
				send_mail("", $email, $_LANG["check_mail"], sprintf($_LANG["check_mail_content"], $email, $_CFG["shop_name"], $url, $url, $_CFG["shop_name"], local_date("Y-m-d")), 1);
			}
			else if ($ck["stat"] == 1) {
				$info = sprintf($_LANG["email_alreadyin_list"], $email);
			}
			else {
				$hash = substr(md5(time()), 1, 10);
				$sql = "UPDATE " . $ecs->table("email_list") . "SET hash = '$hash' WHERE email = '$email'";
				$db->query($sql);
				$info = $_LANG["email_re_check"];
				$url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
				send_mail("", $email, $_LANG["check_mail"], sprintf($_LANG["check_mail_content"], $email, $_CFG["shop_name"], $url, $url, $_CFG["shop_name"], local_date("Y-m-d")), 1);
			}

			exit($info);
		}
		else if ($job == "del") {
			if (empty($ck)) {
				$info = sprintf($_LANG["email_notin_list"], $email);
			}
			else if ($ck["stat"] == 1) {
				$hash = substr(md5(time()), 1, 10);
				$sql = "UPDATE " . $ecs->table("email_list") . "SET hash = '$hash' WHERE email = '$email'";
				$db->query($sql);
				$info = $_LANG["email_check"];
				$url = $ecs->url() . "user.php?act=email_list&job=del_check&hash=$hash&email=$email";
				send_mail("", $email, $_LANG["check_mail"], sprintf($_LANG["check_mail_content"], $email, $_CFG["shop_name"], $url, $url, $_CFG["shop_name"], local_date("Y-m-d")), 1);
			}
			else {
				$info = $_LANG["email_not_alive"];
			}

			exit($info);
		}
		else if ($job == "add_check") {
			if (empty($ck)) {
				$info = sprintf($_LANG["email_notin_list"], $email);
			}
			else if ($ck["stat"] == 1) {
				$info = $_LANG["email_checked"];
			}
			else if ($_GET["hash"] == $ck["hash"]) {
				$sql = "UPDATE " . $ecs->table("email_list") . "SET stat = 1 WHERE email = '$email'";
				$db->query($sql);
				$info = $_LANG["email_checked"];
			}
			else {
				$info = $_LANG["hash_wrong"];
			}

			show_message($info, $_LANG["back_home_lnk"], "index.php");
		}
		else if ($job == "del_check") {
			if (empty($ck)) {
				$info = sprintf($_LANG["email_invalid"], $email);
			}
			else if ($ck["stat"] == 1) {
				if ($_GET["hash"] == $ck["hash"]) {
					$sql = "DELETE FROM " . $ecs->table("email_list") . "WHERE email = '$email'";
					$db->query($sql);
					$info = $_LANG["email_canceled"];
				}
				else {
					$info = $_LANG["hash_wrong"];
				}
			}
			else {
				$info = $_LANG["email_not_alive"];
			}

			show_message($info, $_LANG["back_home_lnk"], "index.php");
		}
	}
	else if ($action == "send_hash_mail") {
		include_once (ROOT_PATH . "includes/cls_json.php");
		include_once (ROOT_PATH . "includes/lib_passport.php");
		$json = new JSON();
		$result = array("error" => 0, "message" => "", "content" => "");

		if ($user_id == 0) {
			$result["error"] = 1;
			$result["message"] = $_LANG["login_please"];
			exit($json->encode($result));
		}

		if (send_regiter_hash($user_id)) {
			$result["message"] = $_LANG["validate_mail_ok"];
			exit($json->encode($result));
		}
		else {
			$result["error"] = 1;
			$result["message"] = $GLOBALS["err"]->last_message();
		}

		exit($json->encode($result));
	}
	else if ($action == "track_packages") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$size = 3;
		$orders = array();
		$sql = "SELECT COUNT(*) FROM " . $ecs->table("order_info") . " as oi WHERE oi.user_id = '$user_id' AND oi.shipping_status IN ('" . SS_SHIPPED . "', '" . SS_RECEIVED . "') and (select count(*) from " . $GLOBALS["ecs"]->table("order_info") . " as oi_2 where oi_2.main_order_id = oi.order_id) = 0 ";
		$record_count = $GLOBALS["db"]->getOne($sql);
		$sql = "SELECT oi.order_id,oi.order_sn,oi.user_id,oi.invoice_no,oi.shipping_id,oi.shipping_name,oi.shipping_time,oi.shipping_status FROM " . $ecs->table("order_info") . " as oi WHERE oi.user_id = '$user_id' AND oi.shipping_status IN ('" . SS_SHIPPED . "', '" . SS_RECEIVED . "') and (select count(*) from " . $GLOBALS["ecs"]->table("order_info") . " as oi_2 where oi_2.main_order_id = oi.order_id) = 0 ";
		$res = $GLOBALS["db"]->selectLimit($sql, $size, ($page - 1) * $size);

		while ($item = $GLOBALS["db"]->fetchRow($res)) {
			$shipping = get_shipping_object($item["shipping_id"]);
			$sql = "SELECT address,consignee,mobile FROM " . $ecs->table("user_address") . " WHERE user_id = '{$item["user_id"]}'";
			$row = $db->getRow($sql);
			$item["address"] = $row["address"];
			$item["consignee"] = $row["consignee"];
			$item["mobile"] = $row["mobile"];
			$item["formated_shipping_time"] = local_date($GLOBALS["_CFG"]["time_format"], $item["shipping_time"]);
			$item["shipping_status"] = $GLOBALS["_LANG"]["ss"][$item["shipping_status"]];

			if (method_exists($shipping, "query")) {
				$query_link = $shipping->query($item["invoice_no"]);
			}
			else {
				$query_link = $item["invoice_no"];
			}

			if ($query_link != $item["invoice_no"]) {
				$item["query_link"] = $query_link;
				$orders[] = $item;
			}
		}

		$pager = get_pager("user.php", array("act" => $action), $record_count, $page, $size);
		$smarty->assign("pager", $pager);
		$smarty->assign("orders", $orders);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "order_query") {
		$_GET["order_sn"] = trim(substr($_GET["order_sn"], 1));
		$order_sn = (empty($_GET["order_sn"]) ? "" : addslashes($_GET["order_sn"]));
		include_once (ROOT_PATH . "includes/cls_json.php");
		$json = new JSON();
		$result = array("error" => 0, "message" => "", "content" => "");

		if (isset($_SESSION["last_order_query"])) {
			if ((time() - $_SESSION["last_order_query"]) <= 10) {
				$result["error"] = 1;
				$result["message"] = $_LANG["order_query_toofast"];
				exit($json->encode($result));
			}
		}

		$_SESSION["last_order_query"] = time();

		if (empty($order_sn)) {
			$result["error"] = 1;
			$result["message"] = $_LANG["invalid_order_sn"];
			exit($json->encode($result));
		}

		$sql = "SELECT order_id, order_status, shipping_status, pay_status,  shipping_time, shipping_id, invoice_no, user_id  FROM " . $ecs->table("order_info") . " WHERE order_sn = '$order_sn' LIMIT 1";
		$row = $db->getRow($sql);

		if (empty($row)) {
			$result["error"] = 1;
			$result["message"] = $_LANG["invalid_order_sn"];
			exit($json->encode($result));
		}

		$order_query = array();
		$order_query["order_sn"] = $order_sn;
		$order_query["order_id"] = $row["order_id"];
		$order_query["order_status"] = $_LANG["os"][$row["order_status"]] . "," . $_LANG["ps"][$row["pay_status"]] . "," . $_LANG["ss"][$row["shipping_status"]];
		if ($row["invoice_no"] && (0 < $row["shipping_id"])) {
			$sql = "SELECT shipping_code FROM " . $ecs->table("shipping") . " WHERE shipping_id = '{$row["shipping_id"]}'";
			$shipping_code = $db->getOne($sql);
			$plugin = ROOT_PATH . "includes/modules/shipping/" . $shipping_code . ".php";

			if (file_exists($plugin)) {
				include_once ($plugin);
				$shipping = new $shipping_code();
				$order_query["invoice_no"] = $shipping->query((string) $row["invoice_no"]);
			}
			else {
				$order_query["invoice_no"] = (string) $row["invoice_no"];
			}
		}

		$order_query["user_id"] = $row["user_id"];
		if (($row["user_id"] == 0) && (0 < $row["shipping_time"])) {
			$order_query["shipping_date"] = local_date($GLOBALS["_CFG"]["date_format"], $row["shipping_time"]);
		}

		$smarty->assign("order_query", $order_query);
		$result["content"] = $smarty->fetch("library/order_query.lbi");
		exit($json->encode($result));
	}
	else if ($action == "transform_points") {
		$rule = array();

		if (!empty($_CFG["points_rule"])) {
			$rule = unserialize($_CFG["points_rule"]);
		}

		$cfg = array();

		if (!empty($_CFG["integrate_config"])) {
			$cfg = unserialize($_CFG["integrate_config"]);
			$_LANG["exchange_points"][0] = (empty($cfg["uc_lang"]["credits"][0][0]) ? $_LANG["exchange_points"][0] : $cfg["uc_lang"]["credits"][0][0]);
			$_LANG["exchange_points"][1] = (empty($cfg["uc_lang"]["credits"][1][0]) ? $_LANG["exchange_points"][1] : $cfg["uc_lang"]["credits"][1][0]);
		}

		$sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table("users") . " WHERE user_id='$user_id'";
		$row = $db->getRow($sql);

		if ($_CFG["integrate_code"] == "ucenter") {
			$exchange_type = "ucenter";
			$to_credits_options = array();
			$out_exchange_allow = array();

			foreach ($rule as $credit ) {
				$out_exchange_allow[$credit["appiddesc"] . "|" . $credit["creditdesc"] . "|" . $credit["creditsrc"]] = $credit["ratio"];

				if (!array_key_exists($credit["appiddesc"] . "|" . $credit["creditdesc"], $to_credits_options)) {
					$to_credits_options[$credit["appiddesc"] . "|" . $credit["creditdesc"]] = $credit["title"];
				}
			}

			$smarty->assign("selected_org", $rule[0]["creditsrc"]);
			$smarty->assign("selected_dst", $rule[0]["appiddesc"] . "|" . $rule[0]["creditdesc"]);
			$smarty->assign("descreditunit", $rule[0]["unit"]);
			$smarty->assign("orgcredittitle", $_LANG["exchange_points"][$rule[0]["creditsrc"]]);
			$smarty->assign("descredittitle", $rule[0]["title"]);
			$smarty->assign("descreditamount", round(1 / $rule[0]["ratio"], 2));
			$smarty->assign("to_credits_options", $to_credits_options);
			$smarty->assign("out_exchange_allow", $out_exchange_allow);
		}
		else {
			$exchange_type = "other";
			$bbs_points_name = $user->get_points_name();
			$total_bbs_points = $user->get_points($row["user_name"]);
			$bbs_points = array();

			foreach ($bbs_points_name as $key => $val ) {
				$bbs_points[$key] = array("title" => $_LANG["bbs"] . $val["title"], "value" => $total_bbs_points[$key]);
			}

			$rule_list = array();

			foreach ($rule as $key => $val ) {
				$rule_key = substr($key, 0, 1);
				$bbs_key = substr($key, 1);
				$rule_list[$key]["rate"] = $val;

				switch ($rule_key) {
				case TO_P:
					$rule_list[$key]["from"] = $_LANG["bbs"] . $bbs_points_name[$bbs_key]["title"];
					$rule_list[$key]["to"] = $_LANG["pay_points"];
					break;

				case TO_R:
					$rule_list[$key]["from"] = $_LANG["bbs"] . $bbs_points_name[$bbs_key]["title"];
					$rule_list[$key]["to"] = $_LANG["rank_points"];
					break;

				case FROM_P:
					$rule_list[$key]["from"] = $_LANG["pay_points"];
					$_LANG["bbs"] . $bbs_points_name[$bbs_key]["title"];
					$rule_list[$key]["to"] = $_LANG["bbs"] . $bbs_points_name[$bbs_key]["title"];
					break;

				case FROM_R:
					$rule_list[$key]["from"] = $_LANG["rank_points"];
					$rule_list[$key]["to"] = $_LANG["bbs"] . $bbs_points_name[$bbs_key]["title"];
					break;
				}
			}

			$smarty->assign("bbs_points", $bbs_points);
			$smarty->assign("rule_list", $rule_list);
		}

		$smarty->assign("shop_points", $row);
		$smarty->assign("exchange_type", $exchange_type);
		$smarty->assign("action", $action);
		$smarty->assign("lang", $_LANG);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "act_transform_points") {
		$rule_index = (empty($_POST["rule_index"]) ? "" : trim($_POST["rule_index"]));
		$num = (empty($_POST["num"]) ? 0 : intval($_POST["num"]));
		if (($num <= 0) || ($num != floor($num))) {
			show_message($_LANG["invalid_points"], $_LANG["transform_points"], "user.php?act=transform_points");
		}

		$num = floor($num);
		$bbs_key = substr($rule_index, 1);
		$rule_key = substr($rule_index, 0, 1);
		$max_num = 0;
		$sql = "SELECT user_name, user_id, pay_points, rank_points FROM " . $ecs->table("users") . " WHERE user_id='$user_id'";
		$row = $db->getRow($sql);
		$bbs_points = $user->get_points($row["user_name"]);
		$points_name = $user->get_points_name();
		$rule = array();

		if ($_CFG["points_rule"]) {
			$rule = unserialize($_CFG["points_rule"]);
		}

		list($from, $to) = explode(":", $rule[$rule_index]);
		$max_points = 0;

		switch ($rule_key) {
		case TO_P:
			$max_points = $bbs_points[$bbs_key];
			break;

		case TO_R:
			$max_points = $bbs_points[$bbs_key];
			break;

		case FROM_P:
			$max_points = $row["pay_points"];
			break;

		case FROM_R:
			$max_points = $row["rank_points"];
		}

		if (($max_points <= 0) || ($max_points < $num)) {
			show_message($_LANG["overflow_points"], $_LANG["transform_points"], "user.php?act=transform_points");
		}

		switch ($rule_key) {
		case TO_P:
			$result_points = floor(($num * $to) / $from);
			$user->set_points($row["user_name"], array($bbs_key => -$num));
			log_account_change($row["user_id"], 0, 0, 0, $result_points, $_LANG["transform_points"], ACT_OTHER);
			show_message(sprintf($_LANG["to_pay_points"], $num, $points_name[$bbs_key]["title"], $result_points), $_LANG["transform_points"], "user.php?act=transform_points");
		case TO_R:
			$result_points = floor(($num * $to) / $from);
			$user->set_points($row["user_name"], array($bbs_key => -$num));
			log_account_change($row["user_id"], 0, 0, $result_points, 0, $_LANG["transform_points"], ACT_OTHER);
			show_message(sprintf($_LANG["to_rank_points"], $num, $points_name[$bbs_key]["title"], $result_points), $_LANG["transform_points"], "user.php?act=transform_points");
		case FROM_P:
			$result_points = floor(($num * $to) / $from);
			log_account_change($row["user_id"], 0, 0, 0, -$num, $_LANG["transform_points"], ACT_OTHER);
			$user->set_points($row["user_name"], array($bbs_key => $result_points));
			show_message(sprintf($_LANG["from_pay_points"], $num, $result_points, $points_name[$bbs_key]["title"]), $_LANG["transform_points"], "user.php?act=transform_points");
		case FROM_R:
			$result_points = floor(($num * $to) / $from);
			log_account_change($row["user_id"], 0, 0, -$num, 0, $_LANG["transform_points"], ACT_OTHER);
			$user->set_points($row["user_name"], array($bbs_key => $result_points));
			show_message(sprintf($_LANG["from_rank_points"], $num, $result_points, $points_name[$bbs_key]["title"]), $_LANG["transform_points"], "user.php?act=transform_points");
		}
	}
	else if ($action == "act_transform_ucenter_points") {
		$rule = array();

		if ($_CFG["points_rule"]) {
			$rule = unserialize($_CFG["points_rule"]);
		}

		$shop_points = array("rank_points", "pay_points");
		$sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table("users") . " WHERE user_id='$user_id'";
		$row = $db->getRow($sql);
		$exchange_amount = intval($_POST["amount"]);
		$fromcredits = intval($_POST["fromcredits"]);
		$tocredits = trim($_POST["tocredits"]);
		$cfg = unserialize($_CFG["integrate_config"]);

		if (!empty($cfg)) {
			$_LANG["exchange_points"][0] = (empty($cfg["uc_lang"]["credits"][0][0]) ? $_LANG["exchange_points"][0] : $cfg["uc_lang"]["credits"][0][0]);
			$_LANG["exchange_points"][1] = (empty($cfg["uc_lang"]["credits"][1][0]) ? $_LANG["exchange_points"][1] : $cfg["uc_lang"]["credits"][1][0]);
		}

		list($appiddesc, $creditdesc) = explode("|", $tocredits);
		$ratio = 0;

		if ($exchange_amount <= 0) {
			show_message($_LANG["invalid_points"], $_LANG["transform_points"], "user.php?act=transform_points");
		}

		if ($row[$shop_points[$fromcredits]] < $exchange_amount) {
			show_message($_LANG["overflow_points"], $_LANG["transform_points"], "user.php?act=transform_points");
		}

		foreach ($rule as $credit ) {
			if (($credit["appiddesc"] == $appiddesc) && ($credit["creditdesc"] == $creditdesc) && ($credit["creditsrc"] == $fromcredits)) {
				$ratio = $credit["ratio"];
				break;
			}
		}

		if ($ratio == 0) {
			show_message($_LANG["exchange_deny"], $_LANG["transform_points"], "user.php?act=transform_points");
		}

		$netamount = floor($exchange_amount / $ratio);
		include_once (ROOT_PATH . "./includes/lib_uc.php");
		$result = exchange_points($row["user_id"], $fromcredits, $creditdesc, $appiddesc, $netamount);

		if ($result === true) {
			$sql = "UPDATE " . $ecs->table("users") . " SET $shop_points[$fromcredits]=$shop_points[$fromcredits]-'$exchange_amount' WHERE user_id='{$row["user_id"]}'";
			$db->query($sql);
			$sql = "INSERT INTO " . $ecs->table("account_log") . "(user_id, $shop_points[$fromcredits], change_time, change_desc, change_type) VALUES ('{$row["user_id"]}', '-$exchange_amount', '" . gmtime() . "', '" . $cfg["uc_lang"]["exchange"] . "', '98')";
			$db->query($sql);
			show_message(sprintf($_LANG["exchange_success"], $exchange_amount, $_LANG["exchange_points"][$fromcredits], $netamount, $credit["title"]), $_LANG["transform_points"], "user.php?act=transform_points");
		}
		else {
			show_message($_LANG["exchange_error_1"], $_LANG["transform_points"], "user.php?act=transform_points");
		}
	}
	else if ($action == "clear_history") {
		setcookie("ECS[history]", "", 1);
		setcookie("ECS[list_history]", "", 1);
	}
	else if ($action == "goods_order") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");

		if (isset($_REQUEST["order_id"])) {
			$order_id = intval($_REQUEST["order_id"]);
		}
		else {
			exit("invalid parameter");
		}

		$goods_list = order_goods($order_id);

		foreach ($goods_list as $key => $value ) {
			$price[] = $value["subtotal"];
			$goods_list[$key]["market_price"] = price_format($value["market_price"], false);
			$goods_list[$key]["goods_price"] = price_format($value["goods_price"], false);
			$goods_list[$key]["subtotal"] = price_format($value["subtotal"], false);
			$goods_list[$key]["is_refound"] = get_is_refound($value["rec_id"]);
		}

		$formated_goods_amount = price_format(array_sum($price), false);
		$smarty->assign("formated_goods_amount", $formated_goods_amount);
		$smarty->assign("order_id", $order_id);
		$smarty->assign("goods_list", $goods_list);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "apply_return") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");

		if (isset($_REQUEST["rec_id"])) {
			$recr_id = intval($_REQUEST["rec_id"]);
		}
		else {
			exit("invalid parameter");
		}

		$goods_info = rec_goods($recr_id);
		$parent_cause = get_parent_cause();
		$consignee = get_consignee($_SESSION["user_id"]);
		$smarty->assign("consignee", $consignee);
		$smarty->assign("show_goods_thumb", $GLOBALS["_CFG"]["show_goods_in_cart"]);
		$smarty->assign("show_goods_attribute", $GLOBALS["_CFG"]["show_attr_in_cart"]);
		$smarty->assign("goods", $goods_info);
		$sql = "SELECT order_sn FROM " . $ecs->table("order_info") . " WHERE order_id =" . $_REQUEST["order_id"];
		$order_sn = $db->getOne($sql);
		$smarty->assign("order_id", $_REQUEST["order_id"]);
		$smarty->assign("cause_list", $parent_cause);
		$smarty->assign("order_sn", $order_sn);
		$country_list = get_regions_log(0, 0);
		$province_list = get_regions_log(1, $consignee["country"]);
		$city_list = get_regions_log(2, $consignee["province"]);
		$district_list = get_regions_log(3, $consignee["city"]);
		$sql = "select img_file from " . $ecs->table("return_images") . " where user_id = '$user_id' and rec_id = '$recr_id' order by id desc";
		$img_list = $db->getAll($sql);
		$smarty->assign("img_list", $img_list);
		$sn = 0;
		$smarty->assign("country_list", $country_list);
		$smarty->assign("province_list", $province_list);
		$smarty->assign("city_list", $city_list);
		$smarty->assign("district_list", $district_list);
		$smarty->assign("sn", $sn);
		$smarty->assign("sessid", SESS_ID);
		$smarty->assign("return_pictures", $GLOBALS["_CFG"]["return_pictures"]);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "apply_info") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");

		if (isset($_REQUEST["goods_id"])) {
			$goods_id = intval($_REQUEST["goods_id"]);
			$order_id = intval($_REQUEST["order_id"]);
		}
		else {
			exit("invalid parameter");
		}

		$goods_info = array();
		$sql = "SELECT o.*, IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, g.suppliers_id, IFNULL(b.brand_name, '') AS brand_name, p.product_sn\r\n            FROM " . $ecs->table("order_goods") . " AS o\r\n                LEFT JOIN " . $ecs->table("products") . " AS p\r\n                    ON p.product_id = o.product_id\r\n                LEFT JOIN " . $ecs->table("goods") . " AS g\r\n                    ON o.goods_id = g.goods_id\r\n                LEFT JOIN " . $ecs->table("brand") . " AS b\r\n                    ON g.brand_id = b.brand_id\r\n            WHERE g.goods_id = '$goods_id'";
		$goods_info = $db->getRow($sql);
		$sql = "SELECT consignee , tel , country , province , city , district , address   FROM " . $ecs->table("order_info") . "WHERE order_id = " . $order_id;
		$user_info = $db->getRow($sql);
		$smarty->assign("lang", $_LANG);
		$smarty->assign("country_list", get_regions());
		$consignee_list = get_consignee_list($_SESSION["user_id"]);
		$smarty->assign("consignee_list", $consignee_list);

		foreach ($consignee_list as $region_id => $consignee ) {
			$consignee["country"] = (isset($consignee["country"]) ? intval($consignee["country"]) : 0);
			$consignee["province"] = (isset($consignee["province"]) ? intval($consignee["province"]) : 0);
			$consignee["city"] = (isset($consignee["city"]) ? intval($consignee["city"]) : 0);
			$province_list[$region_id] = get_regions(1, $consignee["country"]);
			$city_list[$region_id] = get_regions(2, $consignee["province"]);
			$district_list[$region_id] = get_regions(3, $consignee["city"]);
		}

		$address_id = $db->getOne("SELECT address_id FROM " . $ecs->table("users") . " WHERE user_id='$user_id'");
		$smarty->assign("province_list", $province_list);
		$smarty->assign("address", $address_id);
		$smarty->assign("city_list", $city_list);
		$smarty->assign("district_list", $district_list);
		$smarty->assign("currency_format", $_CFG["currency_format"]);
		$smarty->assign("sn", 0);
		$smarty->assign("user_info", $user_info);
		$smarty->assign("goods_info", $goods_info);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "submit_return") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		$rec_id = (empty($_REQUEST["rec_id"]) ? 0 : intval($_REQUEST["rec_id"]));
		$last_option = (!isset($_REQUEST["last_option"]) ? $_REQUEST["parent_id"] : $_REQUEST["last_option"]);
		$return_remark = (!isset($_REQUEST["return_remark"]) ? "" : htmlspecialchars(trim($_REQUEST["return_remark"])));
		$return_brief = (!isset($_REQUEST["return_brief"]) ? "" : htmlspecialchars(trim($_REQUEST["return_brief"])));

		if (0 < $rec_id) {
			$sql = "SELECT COUNT(*) FROM " . $ecs->table("order_return") . " WHERE rec_id = " . $rec_id;
			$num = $db->getOne($sql);

			if (0 < $num) {
				show_message("同一订单的同一商品不能重复提交", "", "", "info", true);
			}
		}
		else {
			show_message("退换货提交出现异常，请稍后重试", "", "", "info", true);
		}

		$sql = "select g.goods_name, g.goods_sn,g.brand_id, og.order_id, og.goods_id, og.product_id, og.goods_attr, og.warehouse_id, og.area_id,  og.is_real, og.goods_attr_id, og.goods_price, og.goods_price, og.goods_number from " . $ecs->table("order_goods") . " as og  left join " . $ecs->table("goods") . " as g on og.goods_id = g.goods_id  where og.rec_id = '$rec_id'";
		$order_goods = $db->getRow($sql);
		$sql = " SELECT order_sn, country,province,city ,district FROM " . $GLOBALS["ecs"]->table("order_info") . " WHERE order_id =" . $order_goods["order_id"];
		$res = $GLOBALS["db"]->getRow($sql);
		$maintain_number = (empty($_REQUEST["maintain_number"]) ? 0 : intval($_REQUEST["maintain_number"]));
		$return_num = (empty($_REQUEST["return_num"]) ? 0 : intval($_REQUEST["return_num"]));
		$back_number = (empty($_REQUEST["attr_num"]) ? 0 : intval($_REQUEST["attr_num"]));
		$return_type = intval($_REQUEST["return_type"]);
		$maintain = 0;

		if ($return_type == 1) {
			$back = 1;
			$exchange = 0;
			$return_number = $return_num;
		}
		else if ($return_type == 2) {
			$back = 0;
			$exchange = 2;
			$return_number = $back_number;
		}
		else {
			$back = 0;
			$exchange = 0;
			$return_number = $maintain_number;
		}

		$attr_val = (isset($_REQUEST["attr_val"]) ? $_REQUEST["attr_val"] : array());
		$return_attr_id = (!empty($attr_val) ? implode(",", $attr_val) : "");
		$attr_val = get_goods_attr_info_new($attr_val, "pice", $order_goods["warehouse_id"], $order_goods["area_id"]);
		$order_return = array("rec_id" => $rec_id, "goods_id" => $order_goods["goods_id"], "order_id" => $order_goods["order_id"], "order_sn" => $order_goods["goods_sn"], "return_type" => $return_type, "maintain" => $maintain, "back" => $back, "exchange" => $exchange, "user_id" => $_SESSION["user_id"], "goods_attr" => $order_goods["goods_attr"], "attr_val" => $attr_val, "return_brief" => $return_brief, "remark" => $return_remark, "credentials" => !isset($_REQUEST["credentials"]) ? 0 : intval($_REQUEST["credentials"]), "country" => empty($_REQUEST["country"]) ? 0 : intval($_REQUEST["country"]), "province" => empty($_REQUEST["province"]) ? 0 : intval($_REQUEST["province"]), "city" => empty($_REQUEST["city"]) ? 0 : intval($_REQUEST["city"]), "district" => empty($_REQUEST["district"]) ? 0 : intval($_REQUEST["district"]), "cause_id" => $last_option, "apply_time" => gmtime(), "actual_return" => "", "address" => empty($_REQUEST["return_address"]) ? "" : htmlspecialchars(trim($_REQUEST["return_address"])), "zipcode" => empty($_REQUEST["code"]) ? "" : intval($_REQUEST["code"]), "addressee" => empty($_REQUEST["addressee"]) ? "" : htmlspecialchars(trim($_REQUEST["addressee"])), "phone" => empty($_REQUEST["mobile"]) ? "" : htmlspecialchars(trim($_REQUEST["mobile"])), "return_status" => "0");
		$order_return["should_return"] = get_return_refound($order_return["order_id"], $order_return["rec_id"], $return_number);
		$error_no = 0;

		do {
			$order_return["return_sn"] = get_order_sn();
			$query = $db->autoExecute($ecs->table("order_return"), $order_return, "INSERT", "", "SILENT");
			$error_no = $GLOBALS["db"]->errno();
			if ((0 < $error_no) && ($error_no != 1062)) {
				exit($GLOBALS["db"]->errorMsg());
			}
		} while ($error_no == 1062);

		if ($query) {
			$ret_id = $db->insert_id();
			return_action($ret_id, "申请退款（由用户寄回）", "", $order_return["remark"], "买家");
			$return_goods["rec_id"] = $order_return["rec_id"];
			$return_goods["ret_id"] = $ret_id;
			$return_goods["goods_id"] = $order_goods["goods_id"];
			$return_goods["goods_name"] = $order_goods["goods_name"];
			$return_goods["brand_name"] = $order_goods["brand_name"];
			$return_goods["product_id"] = $order_goods["product_id"];
			$return_goods["goods_sn"] = $order_goods["goods_sn"];
			$return_goods["is_real"] = $order_goods["is_real"];
			$return_goods["goods_attr"] = $order_goods["goods_attr"];
			$return_goods["attr_id"] = $order_goods["goods_attr_id"];
			$return_goods["refound"] = $order_goods["goods_price"];
			$return_goods["return_type"] = $return_type;
			$return_goods["return_number"] = $return_number;

			if ($return_type == 1) {
				$return_goods["out_attr"] = "";
			}
			else if ($return_type == 2) {
				$return_goods["out_attr"] = $order_return["attr_val"];
				$return_goods["return_attr_id"] = $return_attr_id;
			}
			else {
				$return_goods["out_attr"] = "";
			}

			$query = $db->autoExecute($ecs->table("return_goods"), $return_goods, "INSERT", "", "SILENT");
			$sql = "select count(*) from" . $ecs->table("return_images") . " where rec_id = '$rec_id' and user_id = '" . $_SESSION["user_id"] . "'";
			$images_count = $db->getOne($sql);

			if (0 < $images_count) {
				$images["rg_id"] = $order_goods["goods_id"];
				$db->autoExecute($ecs->table("return_images"), $images, "UPDATE", "rec_id = '$rec_id' and user_id = '" . $_SESSION["user_id"] . "'");
			}

			$address_detail = get_consignee_info($order_goods["order_id"], $order_return["address"]);
			$order_return["address_detail"] = $address_detail;
			$order_return["apply_time"] = local_date("Y-m-d H:i:s", $order_return["apply_time"]);
			show_message("申请提交成功，工作人员将尽快审核！", "查看退换货订单", "user.php?act=return_list", "info", true, $order_return);
		}
		else {
			show_message("申请提交出现了异常，请稍后重试", "", "", "info", true);
		}
	}
	else if ($action == "return_list") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		$page = (isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1);
		$record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table("order_return") . " WHERE user_id =" . $_SESSION["user_id"]);
		$pager = get_pager("user.php", array("act" => $action), $record_count, $page);
		$return_list = return_order();
		$consignee = get_consignee($_SESSION["user_id"]);
		$region = array($consignee["country"], $consignee["province"], $consignee["city"], $consignee["district"]);
		$shipping_list = available_shipping_list($region);

		foreach ($shipping_list as $key => $val ) {
			$shipping_cfg = unserialize_config($val["configure"]);
			$shipping_fee = (($shipping_count == 0) && ($cart_weight_price["free_shipping"] == 1) ? 0 : shipping_fee($val["shipping_code"], unserialize($val["configure"]), $cart_weight_price["weight"], $cart_weight_price["amount"], $cart_weight_price["number"]));
			$shipping_list[$key]["format_shipping_fee"] = price_format($shipping_fee, false);
			$shipping_list[$key]["shipping_fee"] = $shipping_fee;
			$shipping_list[$key]["free_money"] = price_format($shipping_cfg["free_money"], false);
			$shipping_list[$key]["insure_formated"] = (strpos($val["insure"], "%") === false ? price_format($val["insure"], false) : $val["insure"]);

			if ($val["shipping_id"] == $order["shipping_id"]) {
				$insure_disabled = $val["insure"] == 0;
				$cod_disabled = $val["support_cod"] == 0;
			}
		}

		$smarty->assign("shipping_list", $shipping_list);
		$smarty->assign("orders", $return_list);
		$smarty->assign("pager", $pager);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "cancel_return") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		$ret_id = (isset($_GET["ret_id"]) ? intval($_GET["ret_id"]) : 0);

		if (cancel_return($ret_id, $user_id)) {
			ecs_header("Location: user.php?act=return_list\n");
			exit();
		}
		else {
			$err->show($_LANG["return_list_lnk"], "user.php?act=return_list");
		}
	}
	else if ($action == "return_detail") {
		include_once (ROOT_PATH . "includes/lib_transaction.php");
		include_once (ROOT_PATH . "includes/lib_order.php");
		$ret_id = (isset($_GET["ret_id"]) ? intval($_GET["ret_id"]) : 0);
		$order = get_return_detail($ret_id);

		if ($order === false) {
			$err->show($_LANG["back_home_lnk"], "./");
			exit();
		}

		$region = array($order["country"], $order["province"], $order["city"], $order["district"]);
		$shipping_list = available_shipping_list($region);

		foreach ($shipping_list as $key => $val ) {
			$shipping_cfg = unserialize_config($val["configure"]);
			$shipping_fee = (($shipping_count == 0) && ($cart_weight_price["free_shipping"] == 1) ? 0 : shipping_fee($val["shipping_code"], unserialize($val["configure"]), $cart_weight_price["weight"], $cart_weight_price["amount"], $cart_weight_price["number"]));
			$shipping_list[$key]["format_shipping_fee"] = price_format($shipping_fee, false);
			$shipping_list[$key]["shipping_fee"] = $shipping_fee;
			$shipping_list[$key]["free_money"] = price_format($shipping_cfg["free_money"], false);
			$shipping_list[$key]["insure_formated"] = (strpos($val["insure"], "%") === false ? price_format($val["insure"], false) : $val["insure"]);

			if ($val["shipping_id"] == $order["shipping_id"]) {
				$insure_disabled = $val["insure"] == 0;
				$cod_disabled = $val["support_cod"] == 0;
			}
		}

		$smarty->assign("shipping_list", $shipping_list);
		$smarty->assign("goods", $order);
		$smarty->display("user_transaction.dwt");
	}
	else if ($action == "edit_express") {
		$ret_id = (empty($_REQUEST["ret_id"]) ? "" : intval($_REQUEST["ret_id"]));
		$order_id = (empty($_REQUEST["order_id"]) ? "" : intval($_REQUEST["order_id"]));
		$back_shipping_name = (empty($_REQUEST["express_name"]) ? "" : intval($_REQUEST["express_name"]));
		$back_other_shipping = (empty($_REQUEST["other_express"]) ? "" : $_REQUEST["other_express"]);
		$back_invoice_no = (empty($_REQUEST["express_sn"]) ? "" : $_REQUEST["express_sn"]);

		if ($ret_id) {
			$db->query("UPDATE " . $ecs->table("order_return") . "SET back_shipping_name = '$back_shipping_name' , back_other_shipping= '$back_other_shipping' , back_invoice_no='$back_invoice_no' WHERE ret_id = '$ret_id'");
		}

		show_message($_LANG["edit_shipping_success"], $_LANG["return_info"], "user.php?act=return_detail&order_id=" . $order_id . "&ret_id=" . $ret_id);
	}
	else if ($action == "ajax_select_cause") {
		require_once (ROOT_PATH . "includes/cls_json.php");
		$json = new JSON();
		$res = array("error" => 0, "message" => "", "option" => "", "rec_id" => 0);
		$c_id = intval($_REQUEST["c_id"]);
		$rec_id = intval($_REQUEST["rec_id"]);
		if (isset($c_id) && isset($rec_id)) {
			$sql = "SELECT * FROM " . $ecs->table("return_cause") . " WHERE parent_id = " . $c_id . " AND is_show = 1 order by sort_order ";
			$result = $db->getAll($sql);

			if ($result) {
				$select = "<select name=\"last_option\" id=\"last_option_" . $rec_id . "\">";

				foreach ($result as $var ) {
					$select .= "<option value=\"" . $var["cause_id"] . "\" ";
					$select .= ($selected == $var["cause_id"] ? "selected='ture'" : "");
					$select .= ">";

					if (0 < $var["level"]) {
						$select .= str_repeat("&nbsp;", $var["level"] * 4);
					}

					$select .= htmlspecialchars(addslashes($var["cause_name"]), ENT_QUOTES) . "</option>";
				}

				$select .= "</select>";
				$res["option"] = $select;
				$res["rec_id"] = $rec_id;
			}

			exit($json->encode($res));
		}
		else {
			$res["error"] = 100;
			$res["message"] = "";
			exit($json->encode($res));
		}
	}
}

?>
