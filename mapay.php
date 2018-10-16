<?php

//$chat_id = 0;
$date = new DateTime("now");
$date_str = $date->format('Y-m-d H:i:s');
$timestamp = $date->getTimestamp();

//require_once("components/bot_api_ma.php");
require_once("components/db_ma.php");
require_once("components/config_ma.php");
require_once("components/MySimplePayMerchant.class.php");

//$botAPI = new BotAPI();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!empty($_GET) && !empty($_GET["uid"]) && !empty($_GET["sid"]) && !empty($_GET["n"])) {
	$user_id = (int) $_GET["uid"];
	$sid = (int) $_GET["sid"];
	$months = (int) $_GET["n"];

	if (isset($services[$sid]) && isset($services[$sid]["prices"][$months])) {
		$res = $mysqli->query("SELECT * FROM users WHERE id = $user_id;");
		if ($res && $user_data = $res->fetch_assoc()) {

			$sum = $services[$sid]["prices"][$months];
			$res = $mysqli->query("INSERT INTO pays VALUES(0, $user_id, '$date_str', $sid,  $months, 0, '1999-00-00 00:00:00');");

			if ($res) {
				$pid = $mysqli->insert_id;
				if ($pid > 0) {
					$payment_data = new SimplePay_Payment;
					$payment_data->amount = $sum;
					$payment_data->order_id = $pid;

					$client_name = $user_data["name"];
					if ($user_data["lastname"] && strlen($user_data["lastname"]) > 0) {
						$client_name .= " " . $user_data["lastname"];
					}
					$payment_data->client_name = $client_name;
					$payment_data->description = $services[$sid]["title"] . ": $months мес.";

					$sp = new MySimplePayMerchant();
					$out = $sp->direct_payment($payment_data);
					$payment_link = $out['sp_redirect_url'];

					header("Location: $payment_link", true, 303);
					exit();
				}
			}
		}
	}
}

?>