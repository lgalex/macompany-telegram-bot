<?php
	
	/*define ('DB_HOST', 'localhost');
	define ('DB_LOGIN', 'root');
	define ('DB_PASSWORD', 'Mc37LokH');
	define ('DB_NAME', 'mp');
	mysql_connect(DB_HOST, DB_LOGIN, DB_PASSWORD) or die ("MySQL Error: " . mysql_error());
	mysql_query("set names utf8") or die ("<br>Invalid query: " . mysql_error());
	mysql_select_db(DB_NAME) or die ("<br>Invalid query: " . mysql_error());

	define('H', $_SERVER['DOCUMENT_ROOT']."/");

	$error[0] = 'UNKNOWN';
	$error[1] = 'Необходимо включить куки';
	$error[2] = 'STOP';*/

	ini_set("display_errors","0"); // Показ ошибок
	ini_set("display_startup_errors","0");
	ini_set('error_reporting', 0);
	mb_internal_encoding('UTF-8'); // Кодировка по умолчанию


	$mysqli = new mysqli('localhost', 'root', 'Mc37LokH', 'mp');
	if (mysqli_connect_errno()) {
		echo  "Ошибка базы данных";
		exit("false");
	}
	$mysqli->set_charset("utf8");