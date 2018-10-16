<?
$date = new DateTime("now");

require_once("../components/bot_api_ma.php");
require_once("../components/db_ma.php");
require_once("../components/config_ma.php");

$botAPI = new BotAPI();

	$res = $mysqli->query("SELECT * FROM `users` WHERE `service` = '1' ");
	if ($res) while ($result = $res->fetch_assoc()) {
		echo $result['id']."<br>";
		$botAPI->sendMessage($result["id"], "Тестирование2\r\nтест.");
	}