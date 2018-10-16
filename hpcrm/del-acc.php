<?php
	require_once("/home/macompany/public_html/components/db_ma.php");

	$acc = $_GET['id'];

	$res2 = $mysqli->query("SELECT * FROM `ikey_post` WHERE `id_account` = '$acc'");
	if ($res2) while ($post = $res2->fetch_assoc()) {
		$mysqli->query("DELETE FROM `ikey_post` WHERE `id` ='$post[id]'");
	}
	$mysqli->query("DELETE FROM `ikey_accounts` WHERE `id` ='$acc'");

	header('location: /hpcrm/index.php');
?>