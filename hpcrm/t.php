<?php

	$symbol = "GBP/USD";


	function pips($cena_open, $cena_close, $symbol, $type) {
		if ($type == "SELL") {
			$d1 = $cena_open-$cena_close;
		}else{
			$d1 = $cena_close-$cena_open;
		}
		if ($d1 == 0) { return 0; exit(); }

		if (stristr($symbol, 'JPY') AND stristr($symbol, '/')) {
			$profit = number_format($d1, 3, '.', '');
		}else if (!stristr($symbol, 'JPY') AND stristr($symbol, '/')) {
			$profit = number_format($d1, 5, '.', '');
		}else{
			$profit = number_format($d1, 2, '.', '');
		}

		if (stristr($profit, '-')) { $minplus = "-"; }else{ $minplus = ""; }
		$d3 = preg_replace('/[^0-9]/', '', $profit);
		$pips = ltrim($d3, '0');

		return $pips;
	}
	echo "Profit: ".pips('1.296', '1.296', $symbol, 'SELL')." pips\r\n";
	exit();

	
	/*if (isset($_POST['go'])) {
		$symbol = $_POST['symbol'];
		$open = $_POST['open'];
		$close = $_POST['close'];

		$d1 = $open-$close;
		echo "<b>1).</b> ".$open." - ".$close." = ".$d1;

		if (stristr($symbol, 'JPY') AND stristr($symbol, '/')) {
			$profit = number_format($d1, 3, '.', '');
			echo "<br><br><b>2).</b> Так как в символе <u>есть</u> JPY и /: выводим 3 знака после точки.";
			echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			Получаем: <b>".$profit."</b>";
		}else if (!stristr($symbol, 'JPY') AND stristr($symbol, '/')) {
			$profit = number_format($d1, 5, '.', '');
			echo "<br><br><b>2).</b> Так как в символе <u>нету</u> JPY а / есть: выводим 5 знаков после точки.";
			echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			Получаем: <b>".$profit."</b>";
		}else{
			$profit = number_format($d1, 2, '.', '');
			echo "<br><br><b>2).</b> Так как в символе <u>нету</u> JPY и /: выводим 2 знаков после точки.";
			echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			Получаем: <b>".$profit."</b>";
		}

		if (stristr($profit, '-')) { $minplus = "-"; }else{ $minplus = ""; }
		$d3 = preg_replace('/[^0-9]/', '', $profit);
		echo "<br><br><b>3).</b> Удаляем точку: <b>".$d3."</b>";

		$d4 = ltrim($d3, '0');
		echo "<br><br><b>4).</b> Удаляем нули =  <b>".$minplus."".$d4." pips</b>";

		echo "<hr><br><br>";

		pips($_POST['open'], $_POST['close'], $_POST['symbol'], "SELL");

	}*/


	$n = 1.256;
	$n = number_format($n, 5, '.', '');

	echo $n;