<?php

	$cena_open = 1.1636;
	$cena_close = 1.1628;

	$profit = $cena_open-$cena_close;

	/*if (substr($profit, 0, 1) === '0') {
		$profit = preg_replace('/[^0-9]/', '', $profit);
		$profit = ltrim($profit, '0');
	}*/

	echo $profit;