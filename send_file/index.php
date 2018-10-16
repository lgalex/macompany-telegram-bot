<?php
	require_once("/home/macompany/public_html/components/db_ma.php");
?>

	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">

	<head lang="ru">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Отправка отчётов на сервер</title>
		<link rel="stylesheet" href="https://macompany.ru/myparserCRM/style.css">
	</head>

	<body>

		<div class="admin-panel">
			<form method="post" action="new.php">

				<div class="item">
					<div class="a">IP <input type="text" name="ip" required autocomplete="off"></div>
				</div>
				<div class="item">
					<div class="a">Логин <input type="text" name="login" required autocomplete="off"></div>
				</div>
				<div class="item">
					<div class="a">Пароль <input type="text" name="pass" required autocomplete="off"></div>
				</div>
				<div class="item">
					<div class="a">Папка <input type="text" name="folder" required autocomplete="off"></div>
				</div>

				<div class="item">
					<input class="go" type="submit" value="Добавить сервер">
				</div>
			</form>
		</div>
		<br><br>

		<div class="admin-panel">
			<? $f=0; 
			$res = $mysqli->query("SELECT * FROM `ikey_send_server`");
			if ($res) while ($result = $res->fetch_assoc()) { $f++;
				?>
				<b>[<?=$f;?>]</b> [<a href="del.php?id=<?=$result['id'];?>">X</a>] ip: <?=$result['ip'];?> - <small><?=$result['folder'];?></small>
				<?
			}
			?>
		</div>