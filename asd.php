<?php

function truncateMyValue($symbol, $length){
	$symbol_value_array = array('AUD/CAD' => 5,
		'XAU/USD' => 2,
		'AUD/CAD' => 5,
		'AUD/CHF' => 5,
		'AUD/JPY' => 3,
		'AUD/NZD' => 5,
		'AUD/USD' => 5,
		'CAD/CHF' => 5,
		'CAD/JPY' => 3,
		'CHF/JPY' => 3,
		'EUR/AUD' => 5,
		'EUR/CAD' => 5,
		'EUR/CHF' => 5,
		'EUR/CZK' => 5,
		'EUR/GBP' => 5,
		'EUR/HUF' => 3,
		'EUR/JPY' => 3,
		'EUR/NOK' => 5,
		'EUR/NZD' => 5,
		'EUR/SEK' => 5,
		'EUR/TRY' => 5,
		'EUR/USD' => 5,
		'GBP/AUD' => 5,
		'GBP/CAD' => 5,
		'GBP/CHF' => 5,
		'GBP/JPY' => 3,
		'GBP/NOK' => 5,
		'GBP/NZD' => 5,
		'GBP/USD' => 5,
		'NZD/CAD' => 5,
		'NZD/CHF' => 5,
		'NZD/JPY' => 3,
		'NZD/USD' => 5,
		'USD/CAD' => 5,
		'USD/CHF' => 5,
		'USD/CNH' => 5,
		'USD/CZK' => 5,
		'USD/HUF' => 3,
		'USD/ILS' => 5,
		'USD/JPY' => 3,
		'USD/MXN' => 5,
		'USD/NOK' => 5,
		'USD/PLN' => 5,
		'USD/RUB' => 5,
		'USD/SEK' => 5,
		'USD/SGD' => 5,
		'USD/TRY' => 5,
		'USD/ZAR' => 5,
		'XAG/USD' => 3,
		'asfafafasfsagagsaga' => 5 );

	if($symbol == "" || !isset($symbol)){
		return $length;
	}else{
		foreach ($symbol_value_array as $key => $value) {
			if($key == $symbol){
				$length = round($length, $value);
				if(strpos('.', $length) >= 0){
					$inner_length = str_replace('.', '', substr($length, (strpos($length, '.') + 1)));
					$plus_zeros = '';
					if(strlen($inner_length) < $value) {
						$zero_count = $value - strlen($inner_length);
						for ($i=0; $i < $zero_count; $i++) { 
							$plus_zeros = $plus_zeros.'0';
						}
					}
					$length = $length.$plus_zeros;
				}

			}
		}

		return $length;
	}
}

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
	if($symbol != 'XAU/USD'){
		$d3 = preg_replace('/[^0-9]/', '', $profit);
	}else{
		$d3 = truncateMyValue($symbol, $profit);
	}
	$pips = $minplus."".ltrim($d3, '0');

	return $pips;
}

echo pips(1203.43000, 1206.42000, 'XAU/USD', 'BUY');

?>