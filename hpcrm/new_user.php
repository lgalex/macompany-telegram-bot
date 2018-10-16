<?php
	require_once("/home/macompany/public_html/components/db_ma.php");
	require_once 'lib.php';
	require_once 'function.php';

	$user = $_POST['user'];
	$pass = $_POST['pass'];
	$mysqli->query("INSERT INTO `ikey_accounts` (`user`, `pass`) values('$user', '$pass')");

	$cookie = $folderCookie.$user.".txt";
	login($user, $pass, $cookie); //авторизация юзера

	header('location: /hpcrm/index.php');
?>