<?
$date = new DateTime("now");

require_once("components/bot_api_ma.php");
require_once("components/db_ma.php");
require_once("components/config_ma.php");

$botAPI = new BotAPI();

if ((isset($_GET["p"]) && $_GET["p"] == "mewWgRtCiQ") || (isset($argv[1]) && $argv[1] == "mewWgRtCiQ")) {
	$date->add(new DateInterval("P1D"));
	$date_str = $date->format('Y-m-d'); //H:i:s

	$sent = 0;

	$res = $mysqli->query("SELECT * FROM users WHERE DATE(sdate) = '$date_str';");
	if ($res) while ($result = $res->fetch_assoc()) {
		$sid = (int) $result["service"];
		$stext = "ваша подписка";
		if (isset($services[$sid])) $stext = $services[$sid]["title"];

		$botAPI->sendMessage($result["id"], "Напоминаем, завтра истекает $stext.");
		$sent++;

		if ($sent % 25 == 0) sleep(1);
	}

	echo $sent . "\n";
}
?>