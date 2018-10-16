<?php
	require_once("/home/macompany/public_html/components/db_ma.php");

	$acc = $_GET['id'];
	$post = $_POST['post'];
	$mysqli->query("INSERT INTO `ikey_post` (`id_account`, `post`) values('$acc', '$post')");

	header('location: /hpcrm/index.php');
?>