<?php
//zend53   
//Decode by www.dephp.cn  QQ 2859470
?>
<?php

define("IN_ECS", true);
require (dirname(__FILE__) . "/includes/init.php");
if (empty($_SESSION["user_id"]) || ($_CFG["integrate_code"] == "ecshop")) {
	ecs_header("Location:./");
}

uc_call("uc_pm_location", array($_SESSION["user_id"]));

?>
