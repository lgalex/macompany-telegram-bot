<?php

$chat_id = 0;
$date = new DateTime("now");
$date_str = $date->format('Y-m-d H:i:s');
$timestamp = $date->getTimestamp();

require_once("components/bot_api_ma.php");
require_once("components/db_ma.php");
require_once("components/config_ma.php");
require_once("components/MySimplePayMerchant.class.php");

function curl_post_async($url, $params) {
    foreach ($params as $key => &$val) {
      if (is_array($val)) $val = implode(',', $val);
        $post_params[] = $key.'='.urlencode($val);
    }
    $post_string = implode('&', $post_params);

    $parts=parse_url($url);

    $fp = fsockopen("ssl://" . $parts['host'], 443, $errno, $errstr, 30);

    $out = "POST ".$parts['path']." HTTP/1.1\r\n";
    $out.= "Host: ".$parts['host']."\r\n";
    $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
    $out.= "Content-Length: ".strlen($post_string)."\r\n";
    $out.= "Connection: Close\r\n\r\n";
    if (isset($post_string)) $out.= $post_string;

    fwrite($fp, $out);
    fclose($fp);
}

$botAPI = new BotAPI();

$req_body = file_get_contents('php://input');
$output = json_decode($req_body, TRUE);

if (!isset($output['callback_query'])) $user_id = (int) $output['message']['from']['id'];
else $user_id = (int) $output['callback_query']['from']['id'];

$message = false;
$callback_query = false;
$chat_state = "";
$user_data = null;
$isnewuser = false;
$user_active_service = 0;
$active_service_date = null;

$res = $mysqli->query("SELECT * FROM users WHERE id = $user_id;");
if ($res) if ($result = $res->fetch_assoc()) {
	$chat_state = $result["chat_state"];
	$user_data = $result;

	if ($user_data["sdate"] && strlen($user_data["sdate"])) {
		$sdate = new DateTime($user_data["sdate"]);
		$sdate_timestamp = $sdate->getTimestamp();
		if ($sdate_timestamp > $timestamp) {
			$user_active_service = (int) $user_data["service"];
			$active_service_date = $sdate;
		}
	}
} else if ($res->num_rows == 0) {
	$isnewuser = true;
}

$main_keyboard = keyboardMarkup([["Подписки", "О сервисе"], ["Связаться с нами", "Настройки"], ["Партнерская программа"]]);

function servicesInlineKeyboard($user_active_service, $trial = false){
	global $services;
	$keyboard = [];
	foreach ($services as $key => $value) {
		array_push($keyboard, [['text' => ($user_active_service == $key ? "✓ " : "") . $value["title"], 'callback_data' => "s$key"]]);
	}
	if ($trial) {
		//array_push($keyboard, [['text' => 'Попробовать бесплатно!', 'callback_data' => "ps1"]]);
	}
	return ['inline_keyboard' => $keyboard];
}

$services_text = "Мы предоставляем следующие подписки:";

function symbolsGroupsInlineKeyboard() {
	global $allsymbols;
	$keyboard = [];
	foreach ($allsymbols as $key => $value) {
		array_push($keyboard, [['text' => $value["title"], 'callback_data' => "gr$key" . "_0"]]);
	}
	return ['inline_keyboard' => $keyboard];
}

function symbolsSubgroupsInlineKeyboard($i = 1) {
	global $allsymbols;
	$myallsymbols = $allsymbols[$i]["groups"];
	$keyboard = [];

	$row = 0;
	$col = 0;
	foreach ($myallsymbols as $key => $value) {
		if ($col == 0) array_push($keyboard, []);

		array_push($keyboard[$row], ['text' => $value["title"], 'callback_data' => "gr$i:$key" . "_0"]);
		
		$col++;
		if ($col == 2) {
			$row++;
			$col = 0;
		}
	}
	array_push($keyboard, [['text' => "Вернуться к группам", 'callback_data' => "2grlist"]]);
	return ['inline_keyboard' => $keyboard];
}

$symbols_groups_text = "Вы можете выбрать интересующую вас информацию в следующих группах:";

function symbolsInlineKeyboard($gid, $filter, $page = 0) {
	global $allsymbols;
	$keyboard = [];
	$gr = $allsymbols[$gid]["data"];
	$spp = 6;  // symbols per page

	$si = $page * $spp;
	$n = count($gr);
	$li = $si;
	$even = false;
	for ($j = 0; $j < $spp; $j++) {
		$i = $si + $j;
		if ($i >= $n) break;

		$symbol = $gr[$i];

		if ($even === false) {
			$even = [];
			array_push($even, ['text' => (strpos($filter, "*$symbol*") === false ? "✓ $symbol" : $symbol), 'callback_data' => "gr$gid" . "_$page" . "_$i"]);
		} else {
			array_push($even, ['text' => (strpos($filter, "*$symbol*") === false ? "✓ $symbol" : $symbol), 'callback_data' => "gr$gid" . "_$page" . "_$i"]);

			array_push($keyboard, $even);
			$even = false;
		}
		$li = $i;
	}
	if ($even !== false) array_push($keyboard, $even);
	if ($si > 0 || $li < $n-1) {
		$arrows = [];
		if ($si > 0) array_push($arrows, ['text' => "⬅️", 'callback_data' => "gr$gid" . "_" . ($page -1)]);
		if ($li < $n-1) array_push($arrows, ['text' => "➡️", 'callback_data' => "gr$gid" . "_" . ($page +1)]);
		array_push($keyboard, $arrows);
	}

	array_push($keyboard, [['text' => "Вернуться к группам", 'callback_data' => "2grlist"]]);
	return ['inline_keyboard' => $keyboard];
}

function symbolsInlineKeyboard2($gid, $sgid, $filter, $page = 0) {
	global $allsymbols;
	$keyboard = [];
	$gr = $allsymbols[$gid]["groups"][$sgid]["data"];
	$spp = 6;  // symbols per page

	$si = $page * $spp;
	$n = count($gr);
	$li = $si;
	$even = false;
	for ($j = 0; $j < $spp; $j++) {
		$i = $si + $j;
		if ($i >= $n) break;

		$symbol = $gr[$i];

		if ($even === false) {
			$even = [];
			array_push($even, ['text' => (strpos($filter, "*$symbol*") === false ? "✓ $symbol" : $symbol), 'callback_data' => "gr$gid:$sgid" . "_$page" . "_$i"]);
		} else {
			array_push($even, ['text' => (strpos($filter, "*$symbol*") === false ? "✓ $symbol" : $symbol), 'callback_data' => "gr$gid:$sgid" . "_$page" . "_$i"]);

			array_push($keyboard, $even);
			$even = false;
		}
		$li = $i;
	}
	if ($even !== false) array_push($keyboard, $even);
	if ($si > 0 || $li < $n-1) {
		$arrows = [];
		if ($si > 0) array_push($arrows, ['text' => "⬅️", 'callback_data' => "gr$gid:$sgid" . "_" . ($page -1)]);
		if ($li < $n-1) array_push($arrows, ['text' => "➡️", 'callback_data' => "gr$gid:$sgid" . "_" . ($page +1)]);
		array_push($keyboard, $arrows);
	}

	array_push($keyboard, [['text' => "Вернуться к подгруппам", 'callback_data' => "2grlist$gid"]]);
	return ['inline_keyboard' => $keyboard];
}

function sendPaysExcelFile($chat_id, $d = false) {
	global $services, $mysqli, $botAPI;

	$dstr = $d ? $d : "0";

	set_include_path(get_include_path() . PATH_SEPARATOR . './Classes/');
	include 'report/PHPExcel/IOFactory.php';
	include 'report/fnc.php';

	$excelFileName = "components/tempreport_pays$dstr.xlsx";
	$phpexcel = new PHPExcel();
	$page = $phpexcel->setActiveSheetIndex(0);

	function writeRow($page, $i, $data) {
		$alphabet = "ABCDEFGHIJKLMNOP";
		foreach ($data as $key => $value) {
			$page->setCellValue($alphabet[$key].$i, $value);
		}
	}

	$currow = 1;

	$res = false;
	if ($d == false) {
		$res = $mysqli->query("SELECT pays.id as pay, pays.user as user, users.name, users.lastname, users.username, users.agent, users.regdate, pays.date, pays.service, pays.months, pays.done_date FROM pays, users WHERE pays.state > 0 AND users.id = pays.user ORDER BY pays.done_date DESC;");
	} else {
		$date = new DateTime("now");
		$date->sub(new DateInterval("P".$d."D"));
		$date_str = $date->format('Y-m-d H:i:s');

		$res = $mysqli->query("SELECT pays.id as pay, pays.user as user, users.name, users.lastname, users.username, users.agent, users.regdate, pays.date, pays.service, pays.months, pays.done_date FROM pays, users WHERE pays.done_date > '$date_str' AND pays.state > 0 AND users.id = pays.user ORDER BY pays.done_date DESC;");
	}

	if ($res) {
		if ($res->num_rows == 0) $botAPI->sendMessage($chat_id, "Пусто");
		else {
			/*$csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');

			$myfunc = function($val) {
				return htmlentities(iconv("utf-8", "windows-1251", $val),ENT_QUOTES, "cp1251");
			};

			fputcsv($csv, array_map($myfunc, ["ID платежа", "ID пользователя", "Имя фамилия (username)", "ID агента", "Дата регистрации", "Дата создания платежа", "Подписка", "Кол-во месяцев", "Дата совершения платежа"]), ";");
			*/

			writeRow($page, $currow, ["ID платежа", "ID пользователя", "Имя фамилия (username)", "ID агента", "Дата регистрации", "Дата создания платежа", "Подписка", "Кол-во месяцев", "Дата совершения платежа"]);
			$currow++;

			while ($result = $res->fetch_assoc()) {
				$row = [];
				array_push($row, $result["pay"]);
				array_push($row, $result["user"]);
				array_push($row, $result["name"] . (empty($result["lastname"])?"":" ".$result["lastname"]) . (empty($result["username"])?"":" (".$result["username"].")"));
				array_push($row, $result["agent"]);
				array_push($row, $result["regdate"]);
				array_push($row, $result["date"]);

				$sid = (int) $result["service"];
				array_push($row, (isset($services[$sid])?$services[$sid]["title"]:""));
				array_push($row, $result["months"]);
				array_push($row, $result["done_date"]);

				//fputcsv($csv, array_map($myfunc, $row), ";");
				writeRow($page, $currow, $row);
				$currow++;
			}

			//rewind($csv);
			//$file_data = stream_get_contents($csv);

			$phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(12);
			$phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
			$phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
			$phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
			$phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
			$phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
			$phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
			$phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
			$phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(25);

			$objWriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
			$objWriter->save($excelFileName);

			$botAPI->sendExcel($chat_id, "pays.xlsx", file_get_contents($excelFileName));
		}
	}
}

function sendUsersExcelFile($chat_id, $d = false) {
	global $services, $mysqli, $botAPI;

	set_include_path(get_include_path() . PATH_SEPARATOR . './Classes/');
	include 'report/PHPExcel/IOFactory.php';
	include 'report/fnc.php';

	$dstr = $d ? $d : "0";

	$excelFileName = "components/tempreport_users$dstr.xlsx";
	$phpexcel = new PHPExcel();
	$page = $phpexcel->setActiveSheetIndex(0);

	function writeRow($page, $i, $data) {
		$alphabet = "ABCDEFGHIJKLMNOP";
		foreach ($data as $key => $value) {
			$page->setCellValue($alphabet[$key].$i, $value);
		}
	}

	$currow = 1;

	$datenow = new DateTime("now");
	$datenow_t = $datenow->getTimestamp();

	$res = false;
	if ($d == false) {
		$res = $mysqli->query("SELECT * FROM users ORDER BY regdate DESC;");
	} else {
		$date = new DateTime("now");
		$date->sub(new DateInterval("P".$d."D"));
		$date_str = $date->format('Y-m-d H:i:s');

		$res = $mysqli->query("SELECT * FROM users WHERE regdate > '$date_str' ORDER BY regdate DESC;");
	}

	if ($res) {
		if ($res->num_rows == 0) $botAPI->sendMessage($chat_id, "Пусто");
		else {
			//$csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');

			/*$myfunc = function($val) {
				return htmlentities(iconv("utf-8", "windows-1251", $val),ENT_QUOTES, "cp1251");
			};*/

			//fputcsv($csv, array_map($myfunc, ["ID пользователя", "Имя фамилия (username)", "ID агента", "Дата регистрации", "Активная подписка", "Истекает"]), ";");

			writeRow($page, $currow, ["ID пользователя", "Имя фамилия (username)", "ID агента", "Дата регистрации", "Активная подписка", "Истекает", "Статус"]);
			$currow++;

			while ($result = $res->fetch_assoc()) {
				$row = [];
				array_push($row, $result["id"]);
				array_push($row, $result["name"] . (empty($result["lastname"])?"":" ".$result["lastname"]) . (empty($result["username"])?"":" (".$result["username"].")"));
				array_push($row, $result["agent"]);
				array_push($row, $result["regdate"]);
				$sid = (int) $result["service"];
				array_push($row, (isset($services[$sid])?$services[$sid]["title"]:""));
				array_push($row, $result["sdate"]);

				$cur_row_status = "Неактивна";
				try {
					$cur_row_date = new DateTime($result["sdate"]);
					if ($cur_row_date) {
						$cur_row_date_t = $cur_row_date->getTimestamp();
						if ($cur_row_date_t > $datenow_t) {
							$cur_row_status = "Активна";
						}
					}
				} catch (Exception $error) {

				}

				array_push($row, $cur_row_status);

				//fputcsv($csv, array_map($myfunc, $row), ";");

				writeRow($page, $currow, $row);
				$currow++;
			}

			//rewind($csv);
			//$file_data = stream_get_contents($csv);

			$phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
			$phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
			$phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
			$phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
			$phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
			$phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
			$phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);

			$objWriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
			$objWriter->save($excelFileName);

			$botAPI->sendExcel($chat_id, "users.xlsx", file_get_contents($excelFileName));
		}
	}
}

if (isset($output['message'])) {
	$message = $output['message'];
	$chat_id = (int) $output['message']['chat']['id'];
	$text = isset($output['message']['text']) ? $output['message']['text'] : "";
	$message_id = $output['message']['message_id'];
	
	$lastname = "";
	$username = "";
	$name = $output['message']['from']['first_name'];
	if (isset($output['message']['from']['last_name'])) $lastname = $output['message']['from']['last_name'];
	if (isset($output['message']['from']['username'])) $username = $output['message']['from']['username'];

	if ($text == "Отмена") {
		$mysqli->query("UPDATE users SET chat_state = '' WHERE id = $user_id;");
		$botAPI->sendMessage($chat_id, "Отменено", $main_keyboard);
		exit();
	}

	if ($chat_state == "contact") {
		$mysqli->query("UPDATE users SET chat_state = '' WHERE id = $user_id;");
		if (strlen($text) > 0) {
			$answ = "Сообщение от:\n" . $user_data["name"];
			if (!empty($user_data["lastname"])) $answ .= " " . $user_data["lastname"];
			if (!empty($user_data["username"])) $answ .= " (" . $user_data["username"] . ")";
			$answ .= "\nUSER ID: $user_id";
			if (!empty($user_data["service"]) && isset($services[(int)$user_data["service"]])) {
				$answ .= "\nПоследняя подписка: " . $services[(int)$user_data["service"]]["title"];
				$answ .= "\nАктивирована до: " . $user_data["sdate"];
			}
			$msgfor = $admins[0];
			$inline_keyboard = ['inline_keyboard' => [[['text' => 'Ответить', 'callback_data' => "sendto$user_id"]],[['text' => 'Активировать подписку', 'callback_data' => "setservice$user_id"]]]];
			//шлем админу а не агенту
			//if (!empty($user_data["agent"])) {
//				$answ .= "\nAgent ID: " . $user_data["agent"];
//				$msgfor = $user_data["agent"];
//				$inline_keyboard = ['inline_keyboard' => [[['text' => 'Ответить', 'callback_data' => "sendto$user_id"]],[['text' => 'Переслать админу', 'callback_data' => "helpadmin$user_id"]]]];
//			}
			$answ .= "\n\n$text";
			$botAPI->sendMessage($msgfor, $answ, $inline_keyboard);
			$botAPI->sendMessage($chat_id, "Отправлено", $main_keyboard);
			exit();
		}
		$botAPI->sendMessage($chat_id, "Ошибка", $main_keyboard);
		exit();
	}

	if (strlen($chat_state) > 0 && userIsAdmin($user_id)) {

		if ($chat_state == "wait4mail0") {
			$file_id = "";
			$caption = "";

			if (isset($message["document"])) $file_id = $message["document"]["file_id"];
			if (isset($message["caption"])) $caption = $message["caption"];

			$res = mysqli_query_stmt("INSERT INTO send VALUES(0, ?, ?, ?, 0, '$date_str', 0);", [$text, $file_id, $caption], "sss");
			if ($res) {
				$send_id = $mysqli->insert_id;
				if ($send_id > 0 && (strlen($text) > 0 || strlen($file_id) > 0)) {
					$keyboard = keyboardMarkup([["Отмена", "Готово"]]);
					$answ = "Текст принят, вы можете добавить файл или перейти к отправке.";

					if (strlen($file_id) > 0) {
						$answ = "Файл принят, вы можете добавить текст или перейти к отправке.";
					}

					$res = $mysqli->query("UPDATE users SET chat_state = 'wait4mail_$send_id' WHERE id = $user_id;");
					if ($res) {
						$botAPI->sendMessage($chat_id, $answ, $keyboard);
						exit();
					}
				}
			}
		}

		if (preg_match('#^wait4mail_([0-9]+)$#', $chat_state, $matches)) {
			$send_id = (int) $matches[1];

			$file_id = "";
			$caption = "";
			if (isset($message["document"])) $file_id = $message["document"]["file_id"];
			if (isset($message["caption"])) $caption = $message["caption"];

			if ($text != "Готово" && (strlen($text) > 0 || strlen($file_id) > 0)) {

				if (strlen($file_id)) {
					mysqli_query_stmt("UPDATE send SET file = ?, caption = ? WHERE id = $send_id;", [$file_id, $caption], "ss");
				} else {
					mysqli_query_stmt("UPDATE send SET `text` = ? WHERE id = $send_id;", [$text], "s");
				}
			}

			$res = $mysqli->query("SELECT * FROM send WHERE id = $send_id;");
			if ($res && $result = $res->fetch_assoc()) {
				if (strlen($result["text"]) > 0) $botAPI->sendMessage($chat_id, $result["text"], $main_keyboard);
				if (strlen($result["file"]) > 0) $botAPI->sendDocument($chat_id, $result["file"], $result["caption"], $main_keyboard);

				$answ = "Выберите подписку чтобы запустить рассылку:";
				$inline_keyboard = [];
				//добавляет массив с кнопкой
				$rowrs = [['text' => "Не подписанные", 'callback_data' => "send$send_id" . "_0"]];
				$rowt = [['text' => "Закончилась подписка", 'callback_data' => "sendout$send_id"]];
				//------------------------------------
				foreach ($services as $key => $value) {
					$row = [['text' => $value["title"], 'callback_data' => "send$send_id" . "_$key"]];
					array_push($inline_keyboard, $row);
				}
				//------------------------------------
				array_push($inline_keyboard, $rowrs);
				array_push($inline_keyboard, $rowt);
				
				
				$mysqli->query("UPDATE users SET chat_state = '' WHERE id = $user_id;");
				$botAPI->sendMessage($chat_id, $answ, ['inline_keyboard' => $inline_keyboard]);
				exit();
			}
		}

		if (preg_match('#^setservice([0-9]+)_([0-9]+)$#', $chat_state, $matches)) {
			$uid = (int) $matches[1];
			$sid = (int) $matches[2];

			if (preg_match('#^([0-9]{4})-([0-9]{2})-([0-9]{2})\s([0-9]{2}):([0-9]{2}):([0-9]{2})$#', $text, $matches)) {

				$res = $mysqli->query("UPDATE users SET service = $sid, sdate = '$text', notification_send='2' WHERE id = $uid;");
                if ($res && $mysqli->affected_rows == 1) {
                	
                	$mysqli->query("UPDATE users SET chat_state = '' WHERE id = $user_id;");
                	$answ = $services[$sid]['title'] . " активирована до $text для пользователя $uid";
                	$botAPI->sendMessage($chat_id, $answ);
                	exit();
                }

                $botAPI->sendMessage($chat_id, "Что-то пошло не так");
				exit();
			}
		}
	}

	if (preg_match('#^sendto([0-9]+)$#', $chat_state, $matches)) {
		$to = (int) $matches[1];

		$sendto_access = true;

		if (!userIsAdmin($user_id)) {
			$sendto_access = false;

			$res = $mysqli->query("SELECT agent FROM users WHERE id = $to");
			if ($res && $result = $res->fetch_assoc()) {
				if ((int) $result["agent"] == $user_id) {
					$sendto_access = true;
				}
			}
		}

		if ($sendto_access) {

			$file_id = "";
			$caption = "";

			if (isset($message["document"])) $file_id = $message["document"]["file_id"];
			if (isset($message["caption"])) $caption = $message["caption"];

			$sent = false;
			if (strlen($file_id) > 0) {
				$botAPI->sendDocument($to, $file_id, $caption);
				$sent = true;
			} else if (strlen($text) > 0) {
				$botAPI->sendMessage($to, $text);
				$sent = true;
			}

			if ($sent) {
				$mysqli->query("UPDATE users SET chat_state = '' WHERE id = $user_id;");
				$botAPI->sendMessage($chat_id, "Отправлено", $main_keyboard);
				exit();
			}
		}
	}

	if (strlen($chat_state) > 0) {
		$mysqli->query("UPDATE users SET chat_state = '' WHERE id = $user_id;");
		$botAPI->sendMessage($chat_id, "Неизвестная ошибка", $main_keyboard);
		exit();
	}

	if (preg_match('#^/start(\s[0-9]+)?$#s', $text, $matches)) {
		$agent = 0;
		if ($matches[1]) {
			$x = (int) $matches[1];
			if ($x > 0 && $x != $user_id) $agent = $x;
		}

		if ($isnewuser) {
		
		$new_sdat = new DateTime("now");
		$new_sdat->add(new DateInterval("P12M"));
		$new_sdat_str = $new_sdat->format('Y-m-d H:i:s');
		
		//mysqli_query_stmt("INSERT INTO users VALUES($user_id, ?, ?, ?, $agent, '$date_str', 0, NULL, '', '')", [$name, $lastname, $username], "sss");
		//регаем нового с датой окончания +1год	
		$mysqli->query("INSERT INTO users VALUES($user_id, '$name', '$lastname', '$username', $agent, '$date_str', 0, '$new_sdat_str', '', '', '')");
		//
		} else if (isset($user_data["agent"]) && (int) $user_data["agent"] == 0 && $agent > 0) {
			$mysqli->query("UPDATE users SET agent = $agent, chat_state = '' WHERE id = $user_id;");
		} else if (strlen($chat_state) > 0) {
			$mysqli->query("UPDATE users SET chat_state = '' WHERE id = $user_id;");
		}

		$botAPI->sendMessage($chat_id, "Привет, $name! Здесь вы можете подписаться на наши рассылки:", $main_keyboard);
		exit();
	}

	if ($text == "Подписки") {
		$trial = false;
		if ($user_active_service == 0) {
			$res = $mysqli->query("SELECT * FROM trial WHERE user = $user_id;");
			if ($res && $res->num_rows == 0) $trial = true;
		}
		$botAPI->sendMessage($chat_id, $services_text, servicesInlineKeyboard($user_active_service, $trial));
		exit();
	}
//---------------партнерка---	
	if ($text == "Партнерская программа") {
		$botAPI->sendMessage($chat_id, "Партнерская программа - macompany.", $main_keyboard);
		$botAPI->sendMessage($chat_id, "Ваша партнерская ссылка: https://t.me/$bot_username?start=$user_id");
		
		exit();
	}
//----------------------
	if ($text == "О сервисе") {
		$botAPI->sendMessage($chat_id, "Консультационно-аналитический сервис ФИНАНСИСТ предоставляет доступ к торговым идеям, видению и анализу рынка, которые подготовлены аналитиками и специалистами macompany.", $main_keyboard);
		exit();
	}

	if ($text == "Связаться с нами") {
		$mysqli->query("UPDATE users SET chat_state = 'contact' WHERE id = $user_id;");
		$botAPI->sendMessage($chat_id, "Напишите сообщение:", keyboardMarkup([["Отмена"]]));
		exit();
	}

	if ($text == "Настройки") {
		$botAPI->sendMessage($chat_id, $symbols_groups_text, symbolsGroupsInlineKeyboard());
		exit();
	}

	if ($text == "/admin" && userIsAdmin($user_id)) {
		$botAPI->sendMessage($chat_id, "...", ['inline_keyboard' => [[['text' => 'Сделать рассылку', 'callback_data' => "newmail"]],[['text' => "Статистика", 'callback_data' => "stats"]]]]);
		exit();
	}

	if ($text == "/getreflink") {
		$botAPI->sendMessage($chat_id, "https://t.me/$bot_username?start=$user_id");
		exit();
	}

	/*
	if ($text == "/deletemyservice") {
		$res = $mysqli->query("UPDATE users SET service = 0, sdate = '1999-00-00 00:00:00' WHERE id = $user_id;");
		if ($res) $botAPI->sendMessage($chat_id, "deleted");
		exit();
	}
	*/

	$botAPI->sendMessage($chat_id, "Неизвестная команда", $main_keyboard);
	exit();
}

if (isset($output['callback_query'])) {
	$callback_query = $output['callback_query'];
	$callback_query_id = $callback_query['id'];

	if (isset($callback_query['message']) && isset($callback_query['data'])) {
		$message = $callback_query['message'];
		$chat_id = (int) $message['chat']['id'];
		$message_id = $message['message_id'];
		$text = $callback_query['data'];

		if (!userIsAdmin($user_id) && preg_match('#^sendto([0-9]+)$#', $text, $matches)) {
			$to = (int) $matches[1];
			$botAPI->sendAnswerQuery($callback_query['id']);
			$mysqli->query("UPDATE users SET chat_state = 'sendto$to' WHERE id = $user_id;");
			$botAPI->sendMessage($chat_id, "Напишите сообщение:", keyboardMarkup([["Отмена"]]));
			exit();
		}

		if (preg_match('#^helpadmin([0-9]+)$#', $text, $matches)) {
			$uid = (int) $matches[1];

			$access = false;

			$res = $mysqli->query("SELECT agent FROM users WHERE id = $uid");
			if ($res && $result = $res->fetch_assoc()) {
				if ((int) $result["agent"] == $user_id) {
					$access = true;
				}
			}

			if ($access) {

				$botAPI->sendAnswerQuery($callback_query['id'], "Отправлено!");

				$answ = $message["text"];
				$inline_keyboard = ['inline_keyboard' => [[['text' => 'Ответить', 'callback_data' => "sendto$uid"]],[['text' => 'Активировать подписку', 'callback_data' => "setservice$uid"]]]];
				//572861229
				$botAPI->sendMessage($admins[0], $answ, $inline_keyboard);

			} else {
				$botAPI->sendAnswerQuery($callback_query['id'], "Ошибка!");
			}

			
			exit();
		}

		if ($text == "2grlist") {
			$botAPI->sendAnswerQuery($callback_query['id']);
			$botAPI->editMessageText($chat_id, $message_id, $symbols_groups_text, symbolsGroupsInlineKeyboard());
			exit();
		}

		if (preg_match('#^2grlist([0-9]+)$#', $text, $matches)) {
			$gid = (int) $matches[1];
			$botAPI->sendAnswerQuery($callback_query['id']);
			$botAPI->editMessageText($chat_id, $message_id, "<b>" . $allsymbols[$gid]["title"] . "</b>", symbolsSubgroupsInlineKeyboard($gid), "html");
			exit();
		}

		if (preg_match('#^gr([0-9]+)_([0-9]+)$#', $text, $matches)) {
			$gid = (int) $matches[1];
			$page = (int) $matches[2];

			$botAPI->sendAnswerQuery($callback_query['id']);

			if (isset($allsymbols[$gid])) {

				if ($page == 0 && isset($allsymbols[$gid]["groups"])) {

					$answ = "<b>" . $allsymbols[$gid]["title"] . "</b>";
					$botAPI->editMessageText($chat_id, $message_id, $answ, symbolsSubgroupsInlineKeyboard($gid), "html");

				} else {

					$answ = "<b>" . $allsymbols[$gid]["title"] . "</b>\n\n" . $allsymbols[$gid]["description"];
					$botAPI->editMessageText($chat_id, $message_id, $answ, symbolsInlineKeyboard($gid, $user_data["filter"], $page), "html");
				}
			}

			exit();
		}

		if (preg_match('#^gr([0-9]+):([0-9]+)_([0-9]+)$#', $text, $matches)) {
			$gid = (int) $matches[1];
			$sgid = (int) $matches[2];
			$page = (int) $matches[3];

			$botAPI->sendAnswerQuery($callback_query['id']);

			if (isset($allsymbols[$gid]["groups"][$sgid])) {
				$answ = "<b>" . $allsymbols[$gid]["title"] . " - " . $allsymbols[$gid]["groups"][$sgid]["title"] . "</b>";
				$botAPI->editMessageText($chat_id, $message_id, $answ, symbolsInlineKeyboard2($gid, $sgid, $user_data["filter"], $page), "html");
			}

			exit();
		}

		if (preg_match('#^gr([0-9]+)_([0-9]+)_([0-9]+)$#', $text, $matches)) {
			$gid = (int) $matches[1];
			$page = (int) $matches[2];
			$syid = (int) $matches[3];

			$botAPI->sendAnswerQuery($callback_query['id']);

			if (isset($allsymbols[$gid]) && isset($allsymbols[$gid]["data"][$syid])) {
				$sy = $allsymbols[$gid]["data"][$syid];
				$sypos = strpos($user_data["filter"], "*$sy*");
				$new_filter = $user_data["filter"];

				if ($sypos === false) {
					$new_filter .= "*$sy*";
				} else {
					//delete from filter
					$sylen = strlen("*$sy*");
					$new_filter = substr($new_filter, 0, $sypos) . substr($new_filter, $sypos + $sylen);
				}
				$res = mysqli_query_stmt("UPDATE users SET filter = ? WHERE id = $user_id;", [$new_filter], "s");
				if ($res) {
					$answ = "<b>" . $allsymbols[$gid]["title"] . "</b>\n\n" . $allsymbols[$gid]["description"];
					$botAPI->editMessageText($chat_id, $message_id, $answ, symbolsInlineKeyboard($gid, $new_filter, $page), "html");
				}
			}

			exit();
		}

		if (preg_match('#^gr([0-9]+):([0-9]+)_([0-9]+)_([0-9]+)$#', $text, $matches)) {
			$gid = (int) $matches[1];
			$sgid = (int) $matches[2];
			$page = (int) $matches[3];
			$syid = (int) $matches[4];

			$botAPI->sendAnswerQuery($callback_query['id']);

			if (isset($allsymbols[$gid]) && isset($allsymbols[$gid]["groups"][$sgid]) && isset($allsymbols[$gid]["groups"][$sgid]["data"][$syid])) {

				$sy = $allsymbols[$gid]["groups"][$sgid]["data"][$syid];
				$sypos = strpos($user_data["filter"], "*$sy*");
				$new_filter = $user_data["filter"];

				if ($sypos === false) {
					$new_filter .= "*$sy*";
				} else {
					//delete from filter
					$sylen = strlen("*$sy*");
					$new_filter = substr($new_filter, 0, $sypos) . substr($new_filter, $sypos + $sylen);
				}
				$res = mysqli_query_stmt("UPDATE users SET filter = ? WHERE id = $user_id;", [$new_filter], "s");
				if ($res) {
					$answ = "<b>" . $allsymbols[$gid]["title"] . " - " . $allsymbols[$gid]["groups"][$sgid]["title"] . "</b>";
					$botAPI->editMessageText($chat_id, $message_id, $answ, symbolsInlineKeyboard2($gid, $sgid, $new_filter, $page), "html");
				}
			}

			exit();
		}

		if (preg_match('#^s([0-9]+)$#', $text, $matches)) {
			$sid = (int) $matches[1];

			$botAPI->sendAnswerQuery($callback_query['id']);

			if (isset($services[$sid])) {
				
				$answ = $services[$sid]["title"] . "\n";
				$answ .= strprice($services[$sid]["prices"][1]) . " / мес.";

				$pay_text = "Приобрести";

				if ($sid == $user_active_service) {
					$answ .= "\n\nАктивировано.\nИстекает: " . $active_service_date->format("d.m.y в H:i");
					$pay_text = "Продлить";
				}

				$botAPI->editMessageText($chat_id, $message_id, $answ, ['inline_keyboard' => [[['text' => $pay_text, 'callback_data' => "ps$sid"], ['text' => "Подробнее", 'callback_data' => "a$sid"]],[['text' => "Вернуться к подпискам", 'callback_data' => "2slist"]]]]);
			}
			exit();
		}

		if (preg_match('#^a([0-9]+)$#', $text, $matches)) {
			$botAPI->sendAnswerQuery($callback_query['id']);
			$sid = (int) $matches[1];
			if (isset($services[$sid])) {
				$keyboard = [
					[
						['text' => "Отчёт за 7 дней", 'callback_data' => "myreport7"],
						['text' => "Отчёт за 30 дней", 'callback_data' => "myreport30"]
					],
					[
						['text' => "Отчёт за 90 дней", 'callback_data' => "myreport90"],
						['text' => "Отчёт за год", 'callback_data' => "myreport365"]
					],
					[['text' => "Назад", 'callback_data' => "s$sid"]]
				];

				$answ = $services[$sid]["description"];
				$botAPI->editMessageText($chat_id, $message_id, $answ, ['inline_keyboard' => $keyboard]);
			}
			exit();
		}

		if (preg_match('#^myreport([0-9]+)$#', $text, $matches)) {
			$period = (int) $matches[1];

			$botAPI->sendAnswerQuery($callback_query['id']);
			
			if ($period == 7 || $period == 30 || $period == 90 || $period == 365) {
				//$botAPI->sendMessage($chat_id, "https://macompany.ru/report/test.txt");
				//$botAPI->sendDocument($chat_id, "https://macompany.ru/report/test.zip"); // myreport$period.xlsx
				file_get_contents('https://macompany.ru/report/41.php?period='.$period);
				$botAPI->sendExcel($chat_id, "report$period.xlsx", file_get_contents("report/myreport$period.xlsx"));
			}
			exit();
		}

		if ($text == "2slist") {
			$botAPI->sendAnswerQuery($callback_query['id']);

			$trial = false;
			if ($user_active_service == 0) {
				$res = $mysqli->query("SELECT * FROM trial WHERE user = $user_id;");
				if ($res && $res->num_rows == 0) $trial = true;
			}

			$botAPI->editMessageText($chat_id, $message_id, $services_text, servicesInlineKeyboard($user_active_service, $trial));
			exit();
		}

		if (preg_match('#^ps([0-9]+)$#', $text, $matches)) {
			$sid = (int) $matches[1];

			if (isset($services[$sid])) {
				//if ($sid == 1) {
//					if ($user_active_service > 0) {
//						$answ = "Ошибка, у вас уже активирована "  . $services[$user_active_service]["title"];
//						$botAPI->sendAnswerQuery($callback_query['id'], $answ, true);
//						exit();
//					}
//
//					$botAPI->sendAnswerQuery($callback_query['id']);
//
//					$new_sdate = new DateTime("now");
//					$new_sdate->add(new DateInterval("P12M"));
//					$new_sdate_str = $new_sdate->format('Y-m-d H:i:s');
//
//					$res = $mysqli->query("UPDATE users SET service = $sid, sdate = '$new_sdate_str' WHERE id = $user_id;");
//                    if ($res && $mysqli->affected_rows == 1) {
//                        $answ =  $services[$sid]["title"] . " активирована";// на 12 мес.\n";
//                        //$answ .= "Истекает " . $new_sdate->format("d.m.y в H:i");
//
//                        $botAPI->sendMessage($user_id, $answ);
//                        exit();
//                    }
//
//                    $botAPI->sendMessage($user_id, "Ошибка");
//                    exit();
//				}

				$keyboard = true;
				$inline_keyboard = [];
				$answ = 'Вы оплачиваете "' . $services[$sid]["title"] . '"';
				if ($user_active_service > 0) {
					//новы пользователи без подписки имеют сервис - 0
					if ($user_active_service < $sid) {
						$answ .= "\n\nВнимание! У вас активирована \"" . $services[$user_active_service]["title"] . '"';
						if ($active_service_date && isset($services[$user_active_service])) {
							$dt = $active_service_date->getTimestamp() - $timestamp;
							$ratio = $services[$user_active_service]["prices"][1] / $services[$sid]["prices"][1];
							$dt *= $ratio;
							$secperday = 24 * 60 * 60;
							$secperhour = 60 * 60;
							$rest = $dt % $secperday;
							$days = (int) (($dt - $rest) / $secperday);
							$hours = floor($rest / $secperhour);
							
							$answ .= "\nПосле оплаты к вашей новой подписке будет дополнительно добавлено $days дней и $hours часов за счёт старой подписки.";
						}
					} else if ($sid < $user_active_service) {
						$answ = 'Вы не можете приобрести "' . $services[$sid]["title"] . '", так как у вас активирована "' . $services[$user_active_service]["title"] . '"';
						$keyboard = false;

						$botAPI->sendAnswerQuery($callback_query['id'], $answ, true);
						exit();
					}
				}

				$botAPI->sendAnswerQuery($callback_query['id']);

				if ($keyboard) {
					if ($sid == 2  && $user_active_service == 0) {
						$res = $mysqli->query("SELECT * FROM trial WHERE user = $user_id;");
						if ($res && $res->num_rows == 0) {
							$btn_text = "14 дней бесплатно!";
							$callback = "gettrial";
							array_push($inline_keyboard, [["text" => $btn_text, "callback_data" => $callback]]);
						}
					}
					foreach ($services[$sid]["prices"] as $key => $value) {
						$btn_text = "Оплатить за $key мес. - " . strprice($value);
						//$btn_data = "pps$sid" . "_$key";
						$btn_url = $init_payment_url . "?uid=$user_id&sid=$sid&n=$key";
						array_push($inline_keyboard, [["text" => $btn_text, "url" => $btn_url]]);
					}
				}
				$btn_text = "Назад";
				$btn_data = "s$sid";
				array_push($inline_keyboard, [["text" => $btn_text, "callback_data" => $btn_data]]);
				$keyboard = ['inline_keyboard' => $inline_keyboard];

				$botAPI->editMessageText($chat_id, $message_id, $answ, $keyboard);
			}
			exit();
		}

		if ($text == "gettrial") {
			$botAPI->sendAnswerQuery($callback_query['id']);
			$res = $mysqli->begin_transaction();
			if ($res && $user_active_service == 0) {
				$res = $mysqli->query("SELECT * FROM trial WHERE user = $user_id;");
				if ($res && $res->num_rows == 0) {
					$res = $mysqli->query("INSERT INTO trial VALUES($user_id);");
					if ($res && $mysqli->affected_rows == 1) {
						$new_sdate = new DateTime("now");
						$new_sdate->add(new DateInterval("P14D"));
						$new_sdate_str = $new_sdate->format('Y-m-d H:i:s');

						$res = $mysqli->query("UPDATE users SET service = 2, sdate = '$new_sdate_str', notification_send='2' WHERE id = $user_id");
						if ($res && $mysqli->affected_rows == 1) {
							$res = $mysqli->commit();
                    		if ($res) {
                    			$answ =  $services[2]["title"] . " активирована\n";
		                        $answ .= "Истекает " . $new_sdate->format("d.m.y в H:i");

		                        $botAPI = new BotAPI();
		                        $botAPI->sendMessage($user_id, $answ);
                    		}
                    		exit();
						}
					}
				}
			}
			$mysqli->rollback();
			exit();
		}

		if (userIsAdmin($user_id)) {

			if ($text == "newmail") {
				$botAPI->sendAnswerQuery($callback_query['id']);
				$res = $mysqli->query("UPDATE users SET chat_state = 'wait4mail0' WHERE id = $user_id");
				if ($res) {
					$answ = "Отправьте сообщение с текстом или файлом (документом):";
					$botAPI->sendMessage($chat_id, $answ, keyboardMarkup([["Отмена"]]));
				}
				exit();
			}

			if (preg_match('#^send([0-9]+)_([0-9]+)$#', $text, $matches)) {
				$send_id = (int) $matches[1];
				$sid = (int) $matches[2];

				$res = $mysqli->query("UPDATE send SET service = $sid WHERE id = $send_id AND service = 0;");
				if ($res && $mysqli->affected_rows == 1) {
					$res = $mysqli->query("INSERT INTO usend (SELECT id, $send_id, 0 FROM users WHERE service >= $sid AND sdate > '$date_str');");
					if ($res) {
						$nprepared = $mysqli->affected_rows;
						if ($nprepared > 0) {
							curl_post_async($send_script_url, [
								"sendid" => $send_id,
								"p" => "4kEpHXCa6iWo",
								"adminid" => $user_id
							]);
							$botAPI->sendAnswerQuery($callback_query['id'], "Рассылка запущена");
							exit();
						} else {
							$botAPI->sendAnswerQuery($callback_query['id'], "Пользователи не найдены");
							exit();
						}
					}
				}
				$botAPI->sendAnswerQuery($callback_query['id'], "Рассылка уже была произведена или что-то пошло не так", true);
				exit();
			}
//-------------------------------------------истекла подписка			
			if (preg_match('#^sendout([0-9]+)$#', $text, $matches)) {
				$send_id = (int) $matches[1];

			//	$res = $mysqli->query("UPDATE send SET service = 0 WHERE id = $send_id;");
			//	if ($res && $mysqli->affected_rows == 1) {
				
			//$res = $mysqli->query("INSERT INTO usend (SELECT id, $send_id, 0 FROM users WHERE service >= $sid AND sdate > '$date_str');");
				$res = $mysqli->query("INSERT INTO usend (SELECT id, $send_id, 0 FROM users WHERE (service = 1 OR service = 2 OR service = 3) AND sdate < NOW());");
				
					if ($res) {
						$nprepared = $mysqli->affected_rows;
						if ($nprepared > 0) {
							curl_post_async($send_script_url, [
								"sendid" => $send_id,
								"p" => "4kEpHXCa6iWo",
								"adminid" => $user_id
							]);
							$botAPI->sendAnswerQuery($callback_query['id'], "Рассылка запущена");
							exit();
						} else {
							$botAPI->sendAnswerQuery($callback_query['id'], "Пользователи не найдены");
							exit();
						}
					}
			//	}
				$botAPI->sendAnswerQuery($callback_query['id'], "Рассылка уже была произведена или что-то пошло не так", true);
				exit();
			}
//----------------------------------------------------------
			if (preg_match('#^resend([0-9]+)$#', $text, $matches)) {
				$send_id = (int) $matches[1];
				curl_post_async($send_script_url, [
					"sendid" => $send_id,
					"p" => "4kEpHXCa6iWo",
					"adminid" => $user_id
				]);
				$botAPI->sendAnswerQuery($callback_query['id'], "Повторная рассылка запущена");
				exit();
			}

			if (preg_match('#^sendto([0-9]+)$#', $text, $matches)) {
				$to = (int) $matches[1];
				$botAPI->sendAnswerQuery($callback_query['id']);
				$mysqli->query("UPDATE users SET chat_state = 'sendto$to' WHERE id = $user_id;");
				$botAPI->sendMessage($chat_id, "Напишите сообщение:", keyboardMarkup([["Отмена"]]));
				exit();
			}

			if (preg_match('#^setservice([0-9]+)$#', $text, $matches)) {
				$uid = (int) $matches[1];
				$botAPI->sendAnswerQuery($callback_query['id']);

				$inline_keyboard = [];
				foreach ($services as $key => $value) {
					$row = [['text' => $value["title"], 'callback_data' => "set$key" . "_" . $uid]];
					array_push($inline_keyboard, $row);
				}

				$botAPI->sendMessage($chat_id, "Выберите подписку:", ['inline_keyboard' => $inline_keyboard]);
				exit();
			}

			if (preg_match('#^set([0-9]+)_([0-9]+)$#', $text, $matches)) {
				$sid = (int) $matches[1];
				$uid = (int) $matches[2];

				$botAPI->sendAnswerQuery($callback_query['id']);

				if (isset($services[$sid])) {

					$newchatstate = "setservice$uid" . "_" . $sid;
					$mysqli->query("UPDATE users SET chat_state = '$newchatstate' WHERE id = $user_id;");

					$res = $mysqli->query("SELECT * FROM users WHERE id = $uid;");
					if ($res && $result = $res->fetch_assoc()) {

						$answ = "Пользователь: " . $result["name"];
						if (!empty($result["lastname"])) $answ .= " " . $result["lastname"];
						if (!empty($result["username"])) $answ .= " (" . $result["username"] . ")";
						$answ .= "\n";

						$answ .= "User ID: " . $result["id"] . "\n";

						if ($result["sdate"] && strlen($result["sdate"]) > 0) {
							$sdate = new DateTime($result["sdate"]);
							$sdate_timestamp = $sdate->getTimestamp();
							if ($sdate_timestamp > $timestamp) {
								$user_active_service = (int) $result["service"];
								$active_service_date = $sdate;

								if (isset($services[$user_active_service])) {
									$answ .= "Активная подписка: " . $services[$user_active_service]["title"] . "\n";
									$answ .= "Истекает: " . $result["sdate"] . "\n";
								}
							}
						}

						$answ .= "\n";
						$answ .= "Чтобы активировать подписку \"" . $services[$sid]["title"] . "\" выберите срок активации или отправьте дату окончания в формате ГГГГ-ММ-ДД ЧЧ:ММ:СС";

						$inline_keyboard = [];
						$btn_text = "Активировать на 2 недели";
						$callback_data = "set$sid" . "_" . $uid . "_" . 99;
						array_push($inline_keyboard, [["text" => $btn_text, "callback_data" => $callback_data]]);
						foreach ($services[$sid]["prices"] as $key => $value) {
							$btn_text = "Активировать на $key мес.";
							$callback_data = "set$sid" . "_" . $uid . "_" . $key;
							array_push($inline_keyboard, [["text" => $btn_text, "callback_data" => $callback_data]]);
						}

						$botAPI->sendMessage($chat_id, $answ, ['inline_keyboard' => $inline_keyboard]);
						exit();

					}

				}

				$botAPI->sendMessage($chat_id, "Что-то пошло не так");
				exit();
			}

			if (preg_match('#^set([0-9]+)_([0-9]+)_([0-9]+)$#', $text, $matches)) {
				$sid = (int) $matches[1];
				$uid = (int) $matches[2];
				$months = (int) $matches[3];

				$botAPI->sendAnswerQuery($callback_query['id']);

				$new_sdate = new DateTime("now");
				if ($months == 99) $new_sdate->add(new DateInterval("P14D"));
				else $new_sdate->add(new DateInterval("P".$months."M"));
				$new_sdate_str = $new_sdate->format('Y-m-d H:i:s');

				$res = $mysqli->query("UPDATE users SET service = $sid, sdate = '$new_sdate_str', notification_send='2' WHERE id = $uid;");
                if ($res && $mysqli->affected_rows == 1) {
                	
                	$mysqli->query("UPDATE users SET chat_state = '' WHERE id = $user_id;");
                	$answ = $services[$sid]['title'] . " активирована до $new_sdate_str для пользователя $uid";
                	$botAPI->sendMessage($chat_id, $answ);
                	exit();
                }

                $botAPI->sendMessage($chat_id, "Что-то пошло не так");
				exit();
			}

			if ($text == "stats") {
				$botAPI->sendAnswerQuery($callback_query['id']);
				$inline_keyboard = [];
				array_push($inline_keyboard, [['text' => 'За последние 7 дней', 'callback_data' => 'last_pays_7']]);
				array_push($inline_keyboard, [['text' => 'За последние 30 дней', 'callback_data' => 'last_pays_30']]);
				array_push($inline_keyboard, [['text' => 'За все время', 'callback_data' => 'all_pays']]);
				$botAPI->sendMessage($chat_id, "Данные по совершённым платежам:", ['inline_keyboard' => $inline_keyboard]);

				$inline_keyboard = [];
				array_push($inline_keyboard, [['text' => 'За последние 7 дней', 'callback_data' => 'last_users_7']]);
				array_push($inline_keyboard, [['text' => 'За последние 30 дней', 'callback_data' => 'last_users_30']]);
				array_push($inline_keyboard, [['text' => 'За все время', 'callback_data' => 'all_users']]);
				$botAPI->sendMessage($chat_id, "Данные по новым пользователям:", ['inline_keyboard' => $inline_keyboard]);
				exit();
			}

			if ($text == "all_pays") {
				$botAPI->sendAnswerQuery($callback_query['id']);
				sendPaysExcelFile($chat_id);
				exit();
			}

			if ($text == "last_pays_30") {
				$botAPI->sendAnswerQuery($callback_query['id']);
				sendPaysExcelFile($chat_id, 30);
				exit();
			}

			if ($text == "last_pays_7") {
				$botAPI->sendAnswerQuery($callback_query['id']);
				sendPaysExcelFile($chat_id, 7);
				exit();
			}

			if ($text == "all_users") {
				$botAPI->sendAnswerQuery($callback_query['id']);
				sendUsersExcelFile($chat_id);
				exit();
			}

			if ($text == "last_users_30") {
				$botAPI->sendAnswerQuery($callback_query['id']);
				sendUsersExcelFile($chat_id, 30);
				exit();
			}

			if ($text == "last_users_7") {
				$botAPI->sendAnswerQuery($callback_query['id']);
				sendUsersExcelFile($chat_id, 7);
				exit();
			}
		}
	}

	$botAPI->sendAnswerQuery($callback_query['id']);
	exit();
}

?>