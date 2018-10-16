<?php
	require_once("/home/macompany/public_html/components/db_ma.php");

	$file = '/home/macompany/public_html/send_file/file/'.date('d-m-Y').'.txt';
	$text = "";

	//формирование файла отчета
	$deal_db = $mysqli->query("SELECT * FROM `deal` ORDER BY `date_close`");
	if ($deal_db) while ($deal = $deal_db->fetch_assoc()) {

		if ($deal['date_close'] != "0000-00-00 00:00:00") {
			$what = "Закрытая";
		}else{
			$what = "Открытая";
		}

		$text .= $what." сделка №:".$deal['num']."\r\n";
		$text .= "Символ: ".$deal['symbol']."\r\n";
		$text .= "Тип: ".$deal['type']."\r\n";
		$text .= "Дата открытия: ".$deal['date_open']."\r\n";
		$text .= "Цена открытия: ".$deal['cena_open']."\r\n";

		if ($deal['date_close'] != "0000-00-00 00:00:00") {
			$text .= "Дата закрытия: ".$deal['date_close']."\r\n";
			$text .= "Цена закрытия: ".$deal['cena_close']."\r\n";
		}

		$text .= "SL: ".$deal['sl']."\r\n";
		$text .= "TP: ".$deal['tp']."\r\n";
		$text .= "---------------------------------\r\n\r\n";

	}

	$of = fopen($file, "w");
	fwrite($of, $text);
	fclose($of);

	$res = $mysqli->query("SELECT * FROM `ikey_send_server`");
	if ($res) while ($result = $res->fetch_assoc()) {
		$connection = ssh2_connect($result['ip'], 22);
		ssh2_auth_password($connection, $result['login'], $result['pass']);
		ssh2_scp_send($connection, $file,  $result['folder'], 0644);
		unset($connection);
	}