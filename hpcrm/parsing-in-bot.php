<?php
require_once("/home/macompany/public_html/components/bot_api_ma.php");
require_once("/home/macompany/public_html/components/db_ma.php");
require_once("/home/macompany/public_html/components/config_ma.php");
require_once 'lib.php';
require_once 'function.php';

	ini_set("display_errors","0"); // Показ ошибок
	ini_set("display_startup_errors","0");
	ini_set('error_reporting', 0);
	mb_internal_encoding('UTF-8'); // Кодировка по умолчанию


//Функция для выбора метода округления
	/*function truncateMyValue($symbol, $length){
		$symbol_value_array = array('AUD/CAD' => 5,
			'XAU/USD' => 2,
			'AUD/CAD' => 5,
			'AUD/CHF' => 5,
			'AUD/JPY' => 3,
			'AUD/NZD' => 5,
			'AUD/USD' => 5,
			'CAD/CHF' => 5,
			'CAD/JPY' => 3,
			'CHF/JPY' => 3,
			'EUR/AUD' => 5,
			'EUR/CAD' => 5,
			'EUR/CHF' => 5,
			'EUR/CZK' => 5,
			'EUR/GBP' => 5,
			'EUR/HUF' => 3,
			'EUR/JPY' => 3,
			'EUR/NOK' => 5,
			'EUR/NZD' => 5,
			'EUR/SEK' => 5,
			'EUR/TRY' => 5,
			'EUR/USD' => 5,
			'GBP/AUD' => 5,
			'GBP/CAD' => 5,
			'GBP/CHF' => 5,
			'GBP/JPY' => 3,
			'GBP/NOK' => 5,
			'GBP/NZD' => 5,
			'GBP/USD' => 5,
			'NZD/CAD' => 5,
			'NZD/CHF' => 5,
			'NZD/JPY' => 3,
			'NZD/USD' => 5,
			'USD/CAD' => 5,
			'USD/CHF' => 5,
			'USD/CNH' => 5,
			'USD/CZK' => 5,
			'USD/HUF' => 3,
			'USD/ILS' => 5,
			'USD/JPY' => 3,
			'USD/MXN' => 5,
			'USD/NOK' => 5,
			'USD/PLN' => 5,
			'USD/RUB' => 5,
			'USD/SEK' => 5,
			'USD/SGD' => 5,
			'USD/TRY' => 5,
			'USD/ZAR' => 5,
			'XAG/USD' => 3,
			'asfafafasfsagagsaga' => 5 );

		if($symbol == "" || !isset($symbol)){
			return $length;
		}else{
			foreach ($symbol_value_array as $key => $value) {
				if($key == $symbol){
					$length = round($length, $value);
					if(strpos('.', $length) >= 0){
						$inner_length = str_replace('.', '', substr($length, (strpos($length, '.') + 1)));
						$plus_zeros = '';
						if(strlen($inner_length) < $value) {
							$zero_count = $value - strlen($inner_length);
							for ($i=0; $i < $zero_count; $i++) { 
								$plus_zeros = $plus_zeros.'0';
							}
						}
						$length = $length.$plus_zeros;
					}

				}
			}

			return $length;
		}
	}*/


	$bot_new_order = ""; $bot_new_count = 0;
	$bot_edit_order = ""; $bot_edit_count = 0;
	$bot_close_order = ""; $bot_close_count = 0;
	$mail_close = "";
	$mail_new = "";

	$botAPI = new BotAPI();
	$folderCookie = 'cookie/';

	$res = $mysqli->query("SELECT * FROM `ikey_accounts`");
	if ($res) while ($result = $res->fetch_assoc()) { //все аккаунты

		$cookie = $folderCookie.$result['user'].".txt";
		login($result['user'], $result['pass'], $cookie); //авторизация юзера

		$res2 = $mysqli->query("SELECT * FROM `ikey_post` WHERE `id_account` = '$result[id]'");
		if ($res2) while ($post = $res2->fetch_assoc()) { //все счета
			$page = Read1($post['post'], $cookie);
			$html = str_get_html($page);
			$body = $html->find('.table tr');
			$id_post = $post['post'];

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

					$cena_open = number_format($cena_open, 5, '.', '');
					$cena_close = number_format($cena_close, 5, '.', '');
					$tp = number_format($tp, 5, '.', '');
					$sl = number_format($sl, 5, '.', '');

					$interval = diff(new DateTime($date_open));
					$ago_min = $interval->i; //сколько минут прошло
					if ($ago_min > 30) {
						$min30 = 1;
					}else{
						$min30 = 0;
					}

					if ($type == "Покупка")$type = "BUY";
					if ($type == "Продажа")$type = "SELL";

					if (strpos($symbol, '.pro') !== false) {
						$symbol = str_replace('.pro', '', $symbol);
						$symbol = substr($symbol, 0, 3)."/".substr($symbol, 3, 3);
					}else if (iconv_strlen($symbol) == 6) {
						$symbol = substr($symbol, 0, 3)."/".substr($symbol, 3, 3);
					}

					//добавляем только новые открытые сделки
					$deal = $mysqli->query("SELECT * FROM `deal` WHERE `id_post` = '$id_post' AND `num` = '$num' LIMIT 1");
					$deal = $deal->fetch_object();
					if ($deal->id == NULL AND $date_close == "") {
						$mysqli->query("INSERT INTO `deal` (`id_post`, `num`, `type`, `symbol`, `date_open`, `date_close`, `cena_open`, `cena_close`, `sl`, `tp`, `svop`, `objem`, `pribil`, `komissiya`, `spred`, `komment`, `mr`, `oborot`, `status`, `min30`) 
							values('$id_post', '$num', '$type', '$symbol', '$date_open', '$date_close', '$cena_open', '$cena_close', '$sl', '$tp', '$svop', '$objem', '$pribil', '$komissiya', '$spred', '$komment', '$mr', '$oborot', '0', '$min30')");

						if ($komment != "cancelled" AND $min30 == 0) {
							$mail_new .= "В БД добавлена новая открытая сделка: ".$num.", id счета: ".$id_post."\r\n";

							$bot_new_count++;
							$bot_new_order .= "НОВАЯ ПОЗИЦИЯ\r\n";
							$bot_new_order .= "Номер ".$num."\r\n";
							$bot_new_order .= "Тип: ".$type."\r\n";
							$bot_new_order .= "Символ: ".$symbol."\r\n";
							$bot_new_order .= "Дата открытия: ".$date_open."\r\n";
							$bot_new_order .= "Цена открытия: ".truncateMyValue($symbol, $cena_open)."\r\n";

							if ($tp != 0 && $sl != 0) {
								/*if ($sl == 0) {
									$sl = ($cena_open-$tp)/3;
									$sl = $cena_open+$sl;
									$sl = number_format($sl, 5, '.', '');
								}*///this
								$bot_new_order .= "SL: ".$sl."\r\n";
								$bot_new_order .= "TP: ".$tp."\r\n";
							}elseif($tp == 0 && $sl != 0){
								$bot_new_order .= "SL: ".$sl."\r\n";
								$bot_new_order .= "TP: По сигналу\r\n";
							}elseif($tp != 0 && $sl == 0){
								$bot_new_order .= "SL: Без ограничений\r\n";
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
						$mysqli->query("UPDATE `deal` SET `type` = '$type' WHERE `id` = '$deal->id'");
					}

					//проверяем изменилась ли сделка
					if ($deal->id != NULL) {
						if ($deal->date_close == "0000-00-00 00:00:00" AND $date_close != "" AND $deal->min30 == 0) {
							$mail_close .= "Сделка ".$num.", (id счета: ".$id_post.") закрылась. Дата закрытия: ".$date_close." Цена закрытия: ".truncateMyValue($symbol, $cena_close)."";

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
							if ($deal->date_close == "0000-00-00 00:00:00" AND $deal->min30 == 0) {
								if ($deal->tp != $tp OR $deal->sl != $sl OR $deal->komment != $komment OR $deal->type != $type) {
									if ($komment != "cancelled" AND $date_close == "") {
										$bot_edit_count++;
										$bot_edit_order .= "ИЗМЕНЕНА ПОЗИЦИЯ\r\n";
										$bot_edit_order .= "Номер ".$num."\r\n";
										$bot_edit_order .= "Тип: ".$type."\r\n";
										$bot_edit_order .= "Символ: ".$symbol."\r\n";
										$bot_edit_order .= "Дата открытия: ".$date_open."\r\n";
										$bot_edit_order .= "Цена открытия: ".truncateMyValue($symbol, $cena_open)."\r\n";

										if ($tp != 0 && $sl != 0) {
											/*if ($sl == 0) {
												$sl = ($cena_open-$tp)/3;
												$sl = $cena_open+$sl;
												$sl = number_format($sl, 5, '.', '');
											}*///this
											$bot_edit_order .= "SL: ".$sl."\r\n";
											$bot_edit_order .= "TP: ".$tp."\r\n";
										}elseif($tp == 0 && $sl != 0){
											$bot_edit_order .= "SL: ".$sl."\r\n";
											$bot_edit_order .= "TP: По сигналу\r\n";
										}elseif($tp != 0 && $sl == 0){
											$bot_edit_order .= "SL: Без ограничений\r\n";
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
							if ($deal->date_close == "0000-00-00 00:00:00" AND $deal->min30 == 0) {
								if ($deal->tp != $tp OR $deal->sl != $sl OR $deal->cena_close != $cena_close OR $deal->komment != $komment) {
									if ($komment != "cancelled" AND $date_close != "") {
										$bot_close_count++;
										$bot_close_order .= "ЗАКРЫТА ПОЗИЦИЯ\r\n";
										$bot_close_order .= "Номер ".$num."\r\n";
										$bot_close_order .= "Тип: ".$type."\r\n";
										$bot_close_order .= "Символ: ".$symbol."\r\n";
										$bot_close_order .= "Дата открытия: ".$date_open."\r\n";
										$bot_close_order .= "Цена открытия: ".truncateMyValue($symbol, $cena_open)."\r\n";
										$bot_close_order .= "Дата закрытия: ".$date_close."\r\n";
										$bot_close_order .= "Цена закрытия: ".truncateMyValue($symbol, $cena_close)."\r\n";

										if ($tp != 0 && $sl != 0) {
											/*if ($sl == 0) {
												$sl = ($cena_open-$tp)/3;
												$sl = $cena_open+$sl;
												$sl = number_format($sl, 5, '.', '');
											}*///this
											$bot_close_order .= "SL: ".$sl."\r\n";
											$bot_close_order .= "TP: ".$tp."\r\n";
										}elseif($tp == 0 && $sl != 0){
											$bot_close_order .= "SL: ".$sl."\r\n";
											$bot_close_order .= "TP: По сигналу\r\n";
										}elseif($tp != 0 && $sl == 0){
											$bot_close_order .= "SL: Без ограничений\r\n";
											$bot_close_order .= "TP: ".$tp."\r\n";
										}else{
											$bot_close_order .= "SL: Без ограничений\r\n";
											$bot_close_order .= "TP: По сигналу\r\n";
										}

										
										if ($type == "BUY" OR $type == "SELL") {
											$bot_close_order .= "Profit: ".pips($cena_open, $cena_close, $symbol, $type)." pips\r\n";
										}

										$bot_close_order .= "-------------------------------\r\n\r\n";
									}
								}
							}
						}


					}

				} //foreach


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
		$res = $mysqli->query("SELECT * FROM `users` WHERE `service` > '0' ");
		if ($res) while ($result = $res->fetch_assoc()) {
			if(strtotime($result['sdate']) < strtotime(date('Y-m-d H:i:s'))){
				if($result['notification_send'] != 1){
					$mysqli->query("UPDATE `users` SET notification_send='1' WHERE id='".$result['id']."'");
					$botAPI->sendMessage($result["id"], 'Уважаемый пользователь, срок Вашей подписки истёк, Вы можете продлить ее через основное меню, нажав «Подписки → Выбрав одну из трех подписок → Приобрести»');
				}
			}else{
				if($result['filter'] != ""){
					$result['filter'] = str_replace('**', '*', $result['filter']);
					$filter_array = explode('*', $result['filter']);
					if(in_array($symbol, $filter_array)){
						$botAPI->sendMessage($result["id"], $bot_new_order);
					}
				}else{
					$botAPI->sendMessage($result["id"], $bot_new_order);
				}
			}
		}
	}

	if ($bot_edit_count > 0) {
		$res = $mysqli->query("SELECT * FROM `users` WHERE `service` > '0' ");
		if ($res) while ($result = $res->fetch_assoc()) {
			if(strtotime($result['sdate']) < strtotime(date('Y-m-d H:i:s'))){
				if($result['notification_send'] != 1){
					$mysqli->query("UPDATE `users` SET notification_send='1' WHERE id='".$result['id']."'");
					$botAPI->sendMessage($result["id"], 'Уважаемый пользователь, срок Вашей подписки истёк, Вы можете продлить ее через основное меню, нажав «Подписки → Выбрав одну из трех подписок → Приобрести»');
				}
			}else{
				if($result['filter'] != ""){
					$result['filter'] = str_replace('**', '*', $result['filter']);
					$filter_array = explode('*', $result['filter']);
					if(in_array($symbol, $filter_array)){
						$botAPI->sendMessage($result["id"], $bot_edit_order);
					}
				}else{
					$botAPI->sendMessage($result["id"], $bot_edit_order);
				}
			}
		}
	}

	if ($bot_close_count > 0) {
		$res = $mysqli->query("SELECT * FROM `users` WHERE `service` > '0' ");
		if ($res) while ($result = $res->fetch_assoc()) {
			if(strtotime($result['sdate']) < strtotime(date('Y-m-d H:i:s'))){
				if($result['notification_send'] != 1){
					$mysqli->query("UPDATE `users` SET notification_send='1' WHERE id='".$result['id']."'");
					$botAPI->sendMessage($result["id"], 'Уважаемый пользователь, срок Вашей подписки истёк, Вы можете продлить ее через основное меню, нажав «Подписки → Выбрав одну из трех подписок → Приобрести»');
				}
			}else{
				if($result['filter'] != ""){
					$result['filter'] = str_replace('**', '*', $result['filter']);
					$filter_array = explode('*', $result['filter']);
					if(in_array($symbol, $filter_array)){
						$botAPI->sendMessage($result["id"], $bot_close_order);
					}
				}else{
					$botAPI->sendMessage($result["id"], $bot_close_order);
				}
			}
		}
	}

	echo $bot_new_count."<br>".$bot_edit_count."<br>".$bot_close_count;

	?>