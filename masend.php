<?
ignore_user_abort(true);

$date = new DateTime("now");
$date_str = $date->format('Y-m-d H:i:s');
$timestamp = $date->getTimestamp();

require_once("components/bot_api_ma.php");
require_once("components/db_ma.php");
require_once("components/config_ma.php");

function getCurrentTimestamp() {
	$date = new DateTime("now");
	return $date->getTimestamp();
}

$botAPI = new BotAPI();

if (!empty($_POST) && !empty($_POST["sendid"]) && !empty($_POST["p"]) && $_POST["p"] == "4kEpHXCa6iWo" && !empty($_POST["adminid"])) {
	$send_id = (int) $_POST["sendid"];
	$admin_id = (int) $_POST["adminid"];

	$res = $mysqli->query("SELECT * FROM send WHERE id = $send_id;");
	if ($res && $result = $res->fetch_assoc()) {
		$service = (int) $result["service"];
		$text = $result["text"];
		$file_id = $result["file"];
		$caption = $result["caption"];


		$res = $mysqli->query("SELECT * FROM usend WHERE send = $send_id AND state = 0");
		if ($res) {
			$n_users = $res->num_rows;
			$botAPI->sendMessage($admin_id, "Найдено $n_users пользователей с подпиской \"" . $services[$service]["title"] . "\" и выше. Производится рассылка...");

			$sent = 0;
			$sent_msgs = 0;

			while ($result = $res->fetch_assoc()) {

				if ($sent_msgs > 0 && $sent_msgs % 25 == 0) sleep(1);

				$user_id = (int) $result["user"];
				$needsent = 0;
				$sent4user = 0;

				if (strlen($text) > 0) {
					$needsent++;
					if ($botAPI->sendMessageRes($user_id, $text)) $sent4user++;
				}
				if (strlen($file_id) > 0) {
					$needsent++;
					if ($botAPI->sendDocumentRes($user_id, $file_id, $caption)) $sent4user++;
				}

				if ($needsent == $sent4user) {
					$mysqli->query("UPDATE usend SET state = 1 WHERE user = $user_id AND send = $send_id;");
					$sent++;
				}

				$sent_msgs += $needsent;
			}

			sleep(1);
			
			$botAPI->sendMessage($admin_id, "Рассылка завершена.\nДоставка подтверждена для $sent пользователей.", ['inline_keyboard' => [[['text' => 'Повторить', 'callback_data' => "resend$send_id"]]]]);
			$mysqli->query("DELETE FROM usend WHERE state = 1;");
		}

		/*
		$res = $mysqli->query("SELECT * FROM users WHERE service >= $service AND sdate > '$date_str';");
		if ($res) {
			$n_users = $res->num_rows;
			$botAPI->sendMessage($admin_id, "Найдено $n_users пользователей с подпиской \"" . $services[$service]["title"] . "\" и выше. Производится рассылка...");

			//sleep(60);

			$sent = 0;

			while ($result = $res->fetch_assoc()) {
				$user_id = (int) $result["id"];

				if (strlen($text) > 0) $botAPI->sendMessage($user_id, $text);
				if (strlen($file_id) > 0) $botAPI->sendDocument($user_id, $file_id, $caption);

				$sent++;
			}

			$botAPI->sendMessage($admin_id, "Рассылка завершена. ($sent)");
		}
		*/
	}
}
?>