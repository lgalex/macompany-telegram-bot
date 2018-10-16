<?php
	unlink('cookies.txt');
	require_once("/home/macompany/public_html/components/bot_api_ma.php");
	require_once("/home/macompany/public_html/components/db_ma.php");
	require_once("/home/macompany/public_html/components/config_ma.php");
	$botAPI = new BotAPI();

	$bot_new_order = ""; $bot_new_count = 0;
	$bot_edit_order = ""; $bot_edit_count = 0;
	$bot_close_order = ""; $bot_close_count = 0;

	require_once 'lib.php';

	ini_set("display_errors","0"); // Показ ошибок
	ini_set("display_startup_errors","0");
	ini_set('error_reporting', 0);
	mb_internal_encoding('UTF-8'); // Кодировка по умолчанию

	$rows = $mysqli->query("SELECT * FROM `ikey_config` WHERE `id` = '1' LIMIT 1");
	$config  =  $rows->fetch_object();

	$username = $config->user;
	$password = $config->pass;
	$url = "https://admin.stforex.com/login";

	$id_post = $config->id_post;

	function login($url, $login, $passs){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);

		if (curl_errno($ch)) die(curl_error($ch));
		$dom = new DomDocument();
		$dom->loadHTML($response);
		$tokens = $dom->getElementsByTagName("meta");
		for ($i = 0; $i < $tokens->length; $i++)
		{
		    $meta = $tokens->item($i);
		    if($meta->getAttribute('name') == 'csrf-token')
		    $token = $meta->getAttribute('content');
		}
		$postinfo = "LoginForm[email]=".$login."&LoginForm[password]=".$passs."&_csrf-backend=".$token."";

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$html = curl_exec($ch);

		curl_close($ch);
	}
	login($url, $username, $password);

	function Read1($url){
	   $ch = curl_init();
	   curl_setopt($ch, CURLOPT_URL, $url);
	   curl_setopt($ch, CURLOPT_REFERER, 'https://admin.stforex.com/login');
	   curl_setopt($ch, CURLOPT_POST, 0);
	   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
	   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (Windows; U; Windows NT 5.0; En; rv:1.8.0.2) Gecko/20070306 Firefox/1.0.0.4");
	   $result = curl_exec($ch);
	   curl_close($ch);
	   return $result;
	}
	$page1 = Read1('https://admin.stforex.com/account/trade-operations?login='.$id_post);

	$html = str_get_html($page1);
	$body = $html->find('.table tr');

	$mail_close = "";
	$mail_new = "";

	$b = 0;
	foreach($body as $tr) {
		$b++;
		if ($b > 3) {
			$tr = str_replace('<td>', '', $tr);
			$arr = explode('</td>', $tr);
			
			$num = strtok($arr[0], '>');
			$num = preg_replace('/[^0-9]/', '', $num);
			$type = $arr[1];
			$symbol = $arr[2];
			$date_open = $arr[3];
			$date_close = $arr[4];
			$cena_open = $arr[5];
			$cena_close = $arr[6];
			$sl = $arr[7];
			$tp = $arr[8];
			$svop = $arr[9];
			$objem = $arr[10];
			$pribil = $arr[11];
			$komissiya = $arr[12];
			$spred = $arr[13];
			$komment = $arr[14];
			$mr = $arr[15];
			$oborot = $arr[16];

		if ($date_close == "" AND $komment != "cancelled") {
			echo "Номер: <b>".$num."</b><br>";
			echo "Тип: <b>".$type."</b><br>";
			echo "Символ: <b>".$symbol."</b><br>";
			echo "Дата открытия: <b>".$date_open."</b><br>";
			echo "Дата закрытия: <b>".$date_close."</b><br>";
			echo "Цена открытия: <b>".$cena_open."</b><br>";
			echo "Цена закрытия: <b>".$cena_close."</b><br>";
			echo "SL: <b>".$sl."</b><br>";
			echo "TP: <b>".$tp."</b><br>";
			echo "Своп: <b>".$svop."</b><br>";
			echo "Объём: <b>".$objem."</b><br>";
			echo "Прибыль: <b>".$pribil."</b><br>";
			echo "Комиссия: <b>".$komissiya."</b><br>";
			echo "Спред: <b>".$spred."</b><br>";
			echo "Комментарий: <b>".$komment."</b><br>";
			echo "Margin Rate: <b>".$mr."</b><br>";
			echo "Оборот: <b>".$oborot."</b><br>";
			echo "<hr>";
		}

		if ($type == "Покупка")$type = "BUY";
		if ($type == "Продажа")$type = "SELL";

		if (strpos($symbol, '.pro') !== false) {
			$symbol = str_replace('.pro', '', $symbol);
			$symbol = substr($symbol, 0, 3)."/".substr($symbol, 3, 3);
		}

		//добавляем только новые открытые сделки
		$deal = $mysqli->query("SELECT * FROM `deal` WHERE `id_post` = '$id_post' AND `num` = '$num' LIMIT 1");
		$deal = $deal->fetch_object();
		if ($deal->id == NULL AND $date_close == "") {
			$mysqli->query("INSERT INTO `deal` (`id_post`, `num`, `type`, `symbol`, `date_open`, `date_close`, `cena_open`, `cena_close`, `sl`, `tp`, `svop`, `objem`, `pribil`, `komissiya`, `spred`, `komment`, `mr`, `oborot`, `status`) 
			values('$id_post', '$num', '$type', '$symbol', '$date_open', '$date_close', '$cena_open', '$cena_close', '$sl', '$tp', '$svop', '$objem', '$pribil', '$komissiya', '$spred', '$komment', '$mr', '$oborot', '0')");

			if ($komment != "cancelled") {
				$mail_new .= "В БД добавлена новая открытая сделка: ".$num.", id счета: ".$id_post."\r\n";

				$bot_new_count++;
				$bot_new_order .= "НОВАЯ ПОЗИЦИЯ\r\n";
				$bot_new_order .= "Номер ".$num."\r\n";
				$bot_new_order .= "Тип: ".$type."\r\n";
				$bot_new_order .= "Символ: ".$symbol."\r\n";
				$bot_new_order .= "Дата открытия: ".$date_open."\r\n";
				$bot_new_order .= "Цена открытия: ".$cena_open."\r\n";

				if ($tp != 0) {
					if ($sl == 0) {
						$sl = ($cena_open-$tp)/3;
						$sl = $cena_open+$sl;
						$sl = number_format($sl, 4, '.', '');
					}
					$bot_new_order .= "SL: ".$sl."\r\n";
					$bot_new_order .= "TP: ".$tp."\r\n";
				}else{
					$bot_new_order .= "SL: Без ограничений\r\n";
					$bot_new_order .= "TP: По сигналу\r\n";
				}


				$bot_new_order .= "-------------------------------\r\n\r\n";
			}

		}

		//обновляем данные о сделке
		if ($deal->id != NULL AND $date_close == "") {
				$mysqli->query("UPDATE `deal` SET `date_close` = '$date_close' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `cena_close` = '$cena_close' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `sl` = '$sl' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `tp` = '$tp' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `svop` = '$svop' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `objem` = '$objem' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `pribil` = '$pribil' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `komissiya` = '$komissiya' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `spred` = '$spred' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `komment` = '$komment' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `mr` = '$mr' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `oborot` = '$oborot' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `status` = '0' WHERE `id` = '$deal->id'");
		}

		//проверяем изменилась ли сделка
		if ($deal->id != NULL) {
			if ($deal->date_close == "0000-00-00 00:00:00" AND $date_close != "") {
				$mail_close .= "Сделка ".$num.", (id счета: ".$id_post.") закрылась. Дата закрытия: ".$date_close." Цена закрытия: ".$cena_close."";

				$mysqli->query("UPDATE `deal` SET `date_close` = '$date_close' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `cena_close` = '$cena_close' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `sl` = '$sl' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `tp` = '$tp' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `svop` = '$svop' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `objem` = '$objem' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `pribil` = '$pribil' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `komissiya` = '$komissiya' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `spred` = '$spred' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `komment` = '$komment' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `mr` = '$mr' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `oborot` = '$oborot' WHERE `id` = '$deal->id'");
				$mysqli->query("UPDATE `deal` SET `status` = '1' WHERE `id` = '$deal->id'");
			}

			//изменение сделки для телеграм бота
			if ($deal->id != NULL) {
				if ($deal->date_close == "0000-00-00 00:00:00") {
					if ($deal->tp != $tp OR $deal->sl != $sl OR $deal->komment != $komment OR $deal->type != $type) {
						if ($komment != "cancelled" AND $date_close == "") {
							$bot_edit_count++;
							$bot_edit_order .= "ИЗМЕНЕНА ПОЗИЦИЯ\r\n";
							$bot_edit_order .= "Номер ".$num."\r\n";
							$bot_edit_order .= "Тип: ".$type."\r\n";
							$bot_edit_order .= "Символ: ".$symbol."\r\n";
							$bot_edit_order .= "Дата открытия: ".$date_open."\r\n";
							$bot_edit_order .= "Цена открытия: ".$cena_open."\r\n";

							if ($tp != 0) {
								if ($sl == 0) {
									$sl = ($cena_open-$tp)/3;
									$sl = $cena_open+$sl;
									$sl = number_format($sl, 4, '.', '');
								}
								$bot_edit_order .= "SL: ".$sl."\r\n";
								$bot_edit_order .= "TP: ".$tp."\r\n";
							}else{
								$bot_edit_order .= "SL: Без ограничений\r\n";
								$bot_edit_order .= "TP: По сигналу\r\n";
							}

							$bot_edit_order .= "-------------------------------\r\n\r\n";
						}
					}
				}
			}


			//сделка закрылась
			if ($deal->id != NULL) {
				if ($deal->date_close == "0000-00-00 00:00:00") {
					if ($deal->tp != $tp OR $deal->sl != $sl OR $deal->cena_close != $cena_close OR $deal->komment != $komment) {
						if ($komment != "cancelled" AND $date_close != "") {
							$bot_close_count++;
							$bot_close_order .= "ЗАКРЫТА ПОЗИЦИЯ\r\n";
							$bot_close_order .= "Номер ".$num."\r\n";
							$bot_close_order .= "Тип: ".$type."\r\n";
							$bot_close_order .= "Символ: ".$symbol."\r\n";
							$bot_close_order .= "Дата открытия: ".$date_open."\r\n";
							$bot_close_order .= "Цена открытия: ".$cena_open."\r\n";
							$bot_close_order .= "Дата закрытия: ".$date_close."\r\n";
							$bot_close_order .= "Цена закрытия: ".$cena_close."\r\n";
							
							if ($tp != 0) {
								if ($sl == 0) {
									$sl = ($cena_open-$tp)/3;
									$sl = $cena_open+$sl;
									$sl = number_format($sl, 4, '.', '');
								}
								$bot_new_order .= "SL: ".$sl."\r\n";
								$bot_new_order .= "TP: ".$tp."\r\n";
							}else{
								$bot_new_order .= "SL: Без ограничений\r\n";
								$bot_new_order .= "TP: По сигналу\r\n";
							}

							$bot_close_order .= "-------------------------------\r\n\r\n";
						}
					}
				}
			}


		}



		}
	}

	if ($mail_new != "") {
		mail('ma@macompany.ru', "Добавлены новые сделки в БД", $mail_new);
	}

	if ($mail_close != "") {
		mail('ma@macompany.ru', "Оповещение о закрытых сделках", $mail_close);
	}


	if ($bot_new_count > 0) {
		$res = $mysqli->query("SELECT * FROM `users` WHERE `service` = '1' ");
		if ($res) while ($result = $res->fetch_assoc()) {
			$botAPI->sendMessage($result["id"], $bot_new_order);
		}
	}

	if ($bot_edit_count > 0) {
		$res = $mysqli->query("SELECT * FROM `users` WHERE `service` = '1' ");
		if ($res) while ($result = $res->fetch_assoc()) {
			$botAPI->sendMessage($result["id"], $bot_edit_order);
		}
	}

	if ($bot_close_count > 0) {
		$res = $mysqli->query("SELECT * FROM `users` WHERE `service` = '1' ");
		if ($res) while ($result = $res->fetch_assoc()) {
			$botAPI->sendMessage($result["id"], $bot_close_order);
		}
	}

	echo $bot_new_count."<br>".$bot_edit_count."<br>".$bot_close_count;