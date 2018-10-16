<?php
	require_once("/home/macompany/public_html/components/db_ma.php");

	$period = 14; //days
	$dateBack = date('Y-m-d', strtotime('-'.$period.' days', strtotime(date('Y-m-d'))));

	$preGroup = array();
	$db = $mysqli->query("SELECT * FROM `deal` WHERE DATE(date_open) BETWEEN '".$dateBack."' AND '".date('Y-m-d')."' ORDER BY `date_open`"); // date_close
	if ($db) while ($deal = $db->fetch_assoc()) {
		if ($deal['type'] == "SELL" OR $deal['type'] == "BUY") {
			$date = explode(" ", $deal['date_open']);
			$date = $date[0];
			//$preGroup[] = array("date_open" => $date, "id" => $deal['id'], "type" => $deal['type']);
			$preGroup[] = $deal['id'];
		}
	}

	echo "<pre>";
		var_export($preGroup);
	echo "</pre>";