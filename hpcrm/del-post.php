<?php
	require_once("/home/macompany/public_html/components/db_ma.php");

	$post = $_GET['id'];
	$mysqli->query("DELETE FROM `ikey_post` WHERE `id` ='$post'");

	header('location: /hpcrm/index.php');
?>