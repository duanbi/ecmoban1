<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

function cut_str($string, $sublen, $start = 0, $code = "gbk")
{
	if ($code == "utf-8") {
		$pa = "/[\001-]|[Â-ß][€-¿]|à[ -¿][€-¿]|[á-ï][€-¿][€-¿]|ğ[-¿][€-¿][€-¿]|[ñ-÷][€-¿][€-¿][€-¿]/";
		preg_match_all($pa, $string, $t_string);

		if ($sublen < (count($t_string[0]) - $start)) {
			return join("", array_slice($t_string[0], $start, $sublen)) . "...";
		}

		return join("", array_slice($t_string[0], $start, $sublen));
	}
	else {
		$start = $start * 2;
		$sublen = $sublen * 2;
		$strlen = strlen($string);
		$tmpstr = "";

		for ($i = 0; $i < $strlen; $i++) {
			if (($start <= $i) && ($i < ($start + $sublen))) {
				if (129 < ord(substr($string, $i, 1))) {
					$tmpstr .= substr($string, $i, 2);
				}
				else {
					$tmpstr .= substr($string, $i, 1);
				}
			}

			if (129 < ord(substr($string, $i, 1))) {
				$i++;
			}
		}

		if (strlen($tmpstr) < $strlen) {
			$tmpstr .= "";
		}

		return $tmpstr;
	}
}

define("IN_ECS", true);

if (!function_exists("htmlspecialchars_decode")) {
	function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT)
	{
		return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
	}
}

require (dirname(__FILE__) . "/includes/init.php");
require_once (dirname(__FILE__) . "/includes/cls_json.php");
$json = new JSON();
$keyword = (empty($_POST["keyword"]) ? "" : trim($_POST["keyword"]));
$category = (empty($_POST["category"]) ? 0 : trim($_POST["category"]));

if ($category == "å…¨éƒ¨") {
	$children = "";
	$parent = "";
}
else if ($category == "æ¨¡æ¿") {
	$children = get_children(9);
	$children = str_replace("g.", " AND ", $children);
	$parent = " AND parent_id = 9";
}
else if ($category == "æ’ä»¶") {
	$children = get_children(23);
	$children = str_replace("g.", " AND ", $children);
	$parent = " AND parent_id = 23";
}
else {
	$children = "";
	$parent = "";
}

if (empty($keyword)) {
	echo "";
	exit();
}
else {
	$sql = "SELECT distinct keyword FROM " . $ecs->table("search_keyword") . "WHERE keyword LIKE '%" . mysql_like_quote($keyword) . "%' OR pinyin_keyword LIKE '%" . mysql_like_quote($keyword) . "%' ORDER BY count DESC";
	$result = $db->selectLimit($sql, 10);
	$sql = "SELECT cat_id, cat_name, parent_id FROM " . $ecs->table("category") . " WHERE cat_name LIKE '%" . mysql_like_quote($keyword) . "%' OR pinyin_keyword LIKE '%" . mysql_like_quote($keyword) . "%' $children limit 0,4";
	$cate_res = $db->getAll($sql);
	$cat_html = "";

	foreach ($cate_res as $key => $row ) {
		if (0 < $row["parent_id"]) {
			$sql_1 = "SELECT cat_name FROM " . $ecs->table("category") . "WHERE cat_id=" . $row["parent_id"];
			$parent_res = $db->getRow($sql_1);
			$url = build_uri("category", array("cid" => $row["cat_id"]));

			if ($url == "") {
				$url = "#";
			}

			$cat_html .= "<li onmouseover=\"_over(this);\" onmouseout=\"_out(this);\">&nbsp;&nbsp;&nbsp;åœ¨<a class='cate_user' href=" . $url . " style='color:#ec5151;'>" . $parent_res["cat_name"] . ">" . $row["cat_name"] . "</a>åˆ†ç±»ä¸‹æœç´¢</li>";
		}
	}

	$html = "<ul id=\"suggestions_list_id\"><input type=\"hidden\" value=\"1\" name=\"selectKeyOne\" id=\"keyOne\" />";
	$res_num = 0;
	$exist_keyword = array();

	while ($row = $db->FetchRow($result)) {
		$scws_res = scws($row["keyword"]);
		$arr = explode(",", $scws_res);
		$operator = " AND ";
		$keywords = "AND (";
		$goods_ids = array();

		foreach ($arr as $key => $val ) {
			if ((0 < $key) && ($key < count($arr)) && (1 < count($arr))) {
				$keywords .= $operator;
			}

			$val = mysql_like_quote(trim($val));
			$keywords .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%' $sc_dsad)";
			$sql = "SELECT DISTINCT goods_id FROM " . $ecs->table("tag") . " WHERE tag_words LIKE '%$val%' ";
			$res = $db->query($sql);

			while ($rows = $db->FetchRow($res)) {
				$goods_ids[] = $rows["goods_id"];
			}
		}

		$keywords .= ")";
		$count = $db->getOne("SELECT count(*) FROM " . $ecs->table("goods") . " WHERE is_delete=0 AND is_on_sale=1 AND is_alone_sale=1  $keywords");

		if ($count <= 0) {
			continue;
		}

		$keyword = preg_quote($keyword);
		$keyword_style = preg_replace("/($keyword)/i", "<font style='font-weight:normal;color:#ec5151;'>\$1</font>", $row["keyword"]);
		$keyword_string = "<font style='font-weight:;'>" . $keyword . "</font>";
		$keyword_name = str_replace($keyword, $keyword_string, $weight_keyword);
		$html .= "<li onmouseover=\"_over(this);\" title=\"" . $row["keyword"] . "\" onmouseout=\"_out(this);\" onClick=\"javascript:fill('" . $row["keyword"] . "');\"><div class=\"left-span\">&nbsp;" . $keyword_style . "</div><div class=\"suggest_span\">çº¦" . $count . "ä¸ªå•†å“</div></li>";
		$res_num++;
		$exist_keyword[] = $row["keyword"];
	}

	if (isset($cat_html) && ($cat_html != "")) {
		$html .= $cat_html;
		$html .= "<li style=\"height:1px; overflow:hidden; border-bottom:1px #eee solid; margin-top:-1px;\"></li>";
		unset($cat_html);
	}

	if ($res_num < 10) {
		$sql = "SELECT distinct goods_name FROM " . $ecs->table("goods") . " WHERE goods_name like '%$keyword%' OR pinyin_keyword LIKE '%$keyword%' AND is_delete=0 AND is_on_sale=1 AND is_alone_sale=1";
		$keyword_res = $db->getAll($sql);
		$res_count = count($keyword_res);

		if ($res_count <= 0) {
			$html .= "</ul>";

			if ($html == "<ul id=\"suggestions_list_id\"><input type=\"hidden\" value=\"1\" name=\"selectKeyOne\" id=\"keyOne\" /></ul>") {
				$html = "";
			}

			echo $html;
			exit();
		}

		$len = 10 - $res_num;

		for ($i = 0; $i < $len; $i++) {
			if ($res_count == $i) {
				break;
			}

			$scws_res = scws($keyword_res[$i]["goods_name"]);
			$arr = explode(",", $scws_res);
			$operator = " AND ";
			$keywords = "AND (";
			$goods_ids = array();

			foreach ($arr as $key => $val ) {
				if ((0 < $key) && ($key < count($arr)) && (1 < count($arr))) {
					$keywords .= $operator;
				}

				$val = mysql_like_quote(trim($val));
				$keywords .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%' $sc_dsad)";
				$sql = "SELECT DISTINCT goods_id FROM " . $ecs->table("tag") . " WHERE tag_words LIKE '%$val%' ";
				$res = $db->query($sql);

				while ($rows = $db->FetchRow($res)) {
					$goods_ids[] = $rows["goods_id"];
				}
			}

			$keywords .= ")";
			$count = $db->getOne("SELECT count(*) FROM " . $ecs->table("goods") . " WHERE is_delete=0 AND is_on_sale=1 AND is_alone_sale=1 $keywords");

			if ($count <= 0) {
				continue;
			}

			if (in_array($keyword_res[$i]["goods_name"], $exist_keyword)) {
				continue;
			}

			$keyword_new_name = $keyword_res[$i]["goods_name"];
			cut_str($keyword_new_name, 25);
			$keyword_style = preg_replace("/($keyword)/i", "<font style='font-weight:normal;color:#ec5151;'>\$1</font>", $keyword_new_name);
			$html .= "<li onmouseover=\"_over(this);\" onmouseout=\"_out(this);\" title=\"" . $keyword_new_name . "\" onClick=\"javascript:fill('" . $keyword_new_name . "');\"><div class=\"left-span\">&nbsp;" . $keyword_style . "</div>&nbsp;<b></b><div class=\"suggest_span\">çº¦" . $count . "ä¸ªå•†å“</div></li>";
		}
	}

	$html .= "</ul>";

	if ($html == "<ul id=\"suggestions_list_id\"><input type=\"hidden\" value=\"1\" name=\"selectKeyOne\" id=\"keyOne\" /></ul>") {
		$html = "";
	}

	echo $html;
	exit();
}

?>
