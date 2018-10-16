<?php
	require_once("../components/db_ma.php");

	$mysqli->query("UPDATE `ikey_config` SET `user` = '$_POST[user]' WHERE `id` = '1'");
	$mysqli->query("UPDATE `ikey_config` SET `pass` = '$_POST[pass]' WHERE `id` = '1'");
	$mysqli->query("UPDATE `ikey_config` SET `id_post` = '$_POST[id_post]' WHERE `id` = '1'");

	unlink('cookies.txt');

	header('location: /hpcrm/index.php');
?>