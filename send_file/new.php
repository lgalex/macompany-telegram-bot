<?php
	require_once("/home/macompany/public_html/components/db_ma.php");

	$ip = $_POST['ip'];
	$login = $_POST['login'];
	$pass = $_POST['pass'];
	$folder = $_POST['folder'];

	$mysqli->query("INSERT INTO `ikey_send_server` (`id_user`, `ip`, `login`, `pass`, `folder`) values('0', '$ip', '$login', '$pass', '$folder')");

	header("location: /send_file/");
?>