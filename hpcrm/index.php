<?php 
	require_once("../components/db_ma.php");
	$rows = $mysqli->query("SELECT * FROM `ikey_config` WHERE `id` = '1' LIMIT 1");
	$config  =  $rows->fetch_object();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru">

	<head lang="ru">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Проект для: Михаил Абызов</title>
		<link rel="stylesheet" href="style.css">
	</head>

	<body>

		<div class="admin-panel">
			<form method="post" action="new_user.php">
				<div class="item">
					<div class="a">Email <input type="email" name="user" required autocomplete="off"></div>
				</div>

				<div class="item">
					<div class="a">Пароль <input type="text" name="pass" required autocomplete="off"></div>
				</div>

				<div class="item">
					<input class="go" type="submit" value="Добавить аккаунт">
					<?/*<a target="_blank" href="test.php">Проверить работу</a>*/?>
				</div>
			</form>
		</div>
		<br><br>


		<div class="admin-panel">
			<? $f=0; 
			$res = $mysqli->query("SELECT * FROM `ikey_accounts`");
			if ($res) while ($result = $res->fetch_assoc()) { $f++;
				?>
				<b>[<?=$f;?>]</b> [<a href="del-acc.php?id=<?=$result['id'];?>">X</a>] Email: <?=$result['user'];?>
				<ul>
						<?
						$res2 = $mysqli->query("SELECT * FROM `ikey_post` WHERE `id_account` = '$result[id]'");
						if ($res2) while ($post = $res2->fetch_assoc()) {
							echo "<li>[<a href='del-post.php?id=".$post['id']."'>x</a>] ".$post['post']."</li>";
						}
						?>
						<li>
							<form action="p-post.php?id=<?=$result['id'];?>" method="post">
								<input style="float:left;width:150px;" type="number" name="post" required autocomplete="off"> <input type="submit" value="add">
							</form>
						</li>
				</ul>
				<?
			}
			?>
		</div>


		<footer>
			<div class="developed">
				Developed by iKey - <a target="_blank" href="https://www.weblancer.net/users/iKey/">open profile</a>
			</div>
		</footer>

	</body>

</html>
