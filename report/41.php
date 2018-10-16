<?php
date_default_timezone_set('Europe/London');
set_include_path(get_include_path() . PATH_SEPARATOR . './Classes/');

require_once("/home/macompany/public_html/components/bot_api_ma.php");
require_once("/home/macompany/public_html/components/db_ma.php");
require_once("/home/macompany/public_html/components/config_ma.php");

include 'PHPExcel/IOFactory.php';
include 'fnc.php';

$symbolData = array();

$botAPI = new BotAPI();

	$period = 7; //days

	if (!empty($_GET)) {
		if (!empty($_GET["period"])) {
			$period = (int) $_GET["period"];
			if ($period < 1) $period = 1;
		}
	}

	$excelFileName = "myreport$period.xlsx";

	$dateBack = date('Y-m-d', strtotime('-'.$period.' days', strtotime(date('Y-m-d'))));

	$phpexcel = new PHPExcel();
	$page = $phpexcel->setActiveSheetIndex(0);
	$page->setCellValue("A1", "Дата")->setCellValue("B1", "Актив")->setCellValue("C1", "Тип")->setCellValue("D1", "Цена входа")->setCellValue("E1", "TakeProfit")->setCellValue("F1", "StopLoss")->setCellValue("G1", "Цена закрытия")->setCellValue("H1", "ИТОГ");

	cellColor('A1', 'FFFF00'); cellColor('B1', 'FFFF00'); cellColor('C1', 'FFFF00'); cellColor('D1', 'FFFF00'); cellColor('E1', 'FFFF00'); cellColor('F1', 'FFFF00'); cellColor('G1', 'FFFF00'); cellColor('H1', 'FFFF00');

	$border_style_r = array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => '7f8c8d'),)));
	$border_style_b = array('borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => '7f8c8d'),)));
	$border_style_l = array('borders' => array('left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => '7f8c8d'),)));
	$sheet = $phpexcel->getActiveSheet();


	$preGroup = array();
	$db = $mysqli->query("SELECT * FROM `deal` WHERE DATE(date_close) BETWEEN '".$dateBack."' AND '".date('Y-m-d')."' ORDER BY `date_close`"); // date_close
	if ($db) while ($deal = $db->fetch_assoc()) {
		if ($deal['type'] == "SELL" OR $deal['type'] == "BUY") {
			$date = explode(" ", $deal['date_close']);//Делим date_close на доту и время
			$date = $date[0];
			$preGroup[] = array("date_close" => $date, "id" => $deal['id'], "type" => $deal['type']);
		}
	}

	$preGroupTwo = array();
	foreach ($preGroup as $value) {
		$preGroupTwo[$value['date_close']][] = $value['id']; //В preGroupTwo ключ это дата а значения это ид
	}
	$preGroupTwo = array_map(function($v) { return '' . implode(',', $v) . ''; }, $preGroupTwo);//Теперь в preGroupTwo каждому ключу-дате соответствует строка с ид, относящимися к этой дате


	$startRowDate = 2;
	$startRowSymbol = 1;
	?>
	<table>
		<th>Дата</th>
		<th>Актив</th>
		<th>Тип</th>
		<th>Цена входа</th>
		<th>TakeProfit</th>
		<th>StopLoss</th>
		<th>Цена закрытия</th>
		<th>ИТОГ</th>
		<?
		foreach ($preGroupTwo as $dateGroup => $idDeal) {

		$page->setCellValue("A".$startRowDate, datePrePandBack($dateGroup)); //записываем дату
		

		?>
		<tr>
			<td><?=$dateGroup;?></td>
			<td>
				<?
				$idDeal = explode(',', $idDeal);
				$preSymbol = array();

				foreach ($idDeal as $id) {
					$dealNum = $mysqli->query("SELECT * FROM `deal` WHERE `id` = '$id'");
					$dealNum = $dealNum->fetch_assoc();
					$symbol = $dealNum['symbol'];
					if (iconv_strlen($symbol) == 6) {
						$symbol = substr($symbol, 0, 3)."/".substr($symbol, 3, 3);
					}
					$preSymbol[] = array("symbol" => $symbol, "id" => $dealNum['id'], "type" => $dealNum['type']);//В $preSymbol находятся массивы с инфой о $dealNum текущей даты в цикле
				}

				$preSymbolTwo = array();
				foreach ($preSymbol as $valueSy) {
					$preSymbolTwo[][$valueSy['symbol']][$valueSy['type']] = $valueSy['id'];
				}







				$output = [];
				$i = 0;
				foreach(call_user_func_array('array_merge_recursive', $preSymbolTwo) as $cur => $a) {
					foreach($a as $k => $v) {//$cur это символ, $a - тип, $v - ид сделки текущего символа текущей даты
						$output[$i++][$cur][$k] = implode(',',(array)$v);
					}
				}

				$colSym = 0;
				$output = array_values($output);
				foreach ($output as $value) {
					//$startRowSymbol++;
					//$colSym++;

					$key = key($value);
					$key2 = key($value[$key]);

					echo $key.":";
					echo "<ul>
					<li>".$key2."</li>
					<li>".$value[$key][$key2]."</li>
					</ul>";

					echo "<hr>";

					$ids = explode(', ', $value[$key][$key2]);
   						$p1 = array(); //цена входа
   						$p2 = array(); //профит
   						$p3 = array(); //стоп лосс
   						$p4 = array(); //цена закрытия
   						$p5 = array(); //take profit

   						foreach ($ids as $ids_data_out) {
   							$ids_data_out = explode(',', $ids_data_out);
   							foreach ($ids_data_out as $ids_data) {
   								$startRowSymbol++;
								$colSym++;
   								
   								$isDeal = $mysqli->query("SELECT * FROM `deal` WHERE `id` = '$ids_data'");
	   							$isDeal = $isDeal->fetch_assoc();
								//$prof = $isDeal['cena_open']-$isDeal['cena_close'];
	   							$prof = pips($isDeal['cena_open'], $isDeal['cena_close'], $isDeal['symbol'], $isDeal['type']);
	   							$p1[] = $isDeal['cena_open'];
	   							$p2[] = $prof;
	   							$p3[] = $isDeal['sl'];
	   							$p4[] = $isDeal['cena_close'];
	   							$p5[] = $isDeal['tp'];

	   							$cena_open = $isDeal['cena_open'];
		   						$sl = $isDeal['sl'];
		   						$cena_close = $isDeal['cena_close'];
		   						$tp = $isDeal['tp'];
		   						$profit = $prof;
		   						if (substr($profit, 0, 1) === '0') {
		   							$profit = preg_replace('/[^0-9]/', '', $profit);
		   							$profit = ltrim($profit, '0');
		   						}
		   						if($key2 == 'SELL'){
		   							$profit = $profit * (-1);
		   						}
		   						$page->setCellValue("B".$startRowSymbol, $key); //записываем символ
		   						$page->setCellValue("C".$startRowSymbol, $key2); //записываем тип
		   						$page->setCellValue("D".$startRowSymbol, $cena_open); //записываем цену входа
		   						if($key2 == 'SELL' && strpos('-', $profit) < 0){
									$page->setCellValue("H".$startRowSymbol, '-'.$profit); //Profit
								}elseif($key2 == 'SELL'){
									$page->setCellValue("H".$startRowSymbol, str_replace('-', '', $profit)); //Profit
								}else{
									$page->setCellValue("H".$startRowSymbol, $profit); //Profit
								}
		   						$page->setCellValue("F".$startRowSymbol, $sl); //записываем стоп лосс
		   						$page->setCellValue("G".$startRowSymbol, $cena_close); //цена закрытия
								$page->setCellValue("E".$startRowSymbol, $tp);

								if(isset($symbolData[$key])){
									$current_pribil = $symbolData[$key][0];
									$current_ubitok = $symbolData[$key][1];
									if($prof < 0){
										$current_ubitok = $current_ubitok + $prof;
									}else{
										$current_pribil = $current_pribil + $prof;
									}
									$symbolData[$key] = array($current_pribil, $current_ubitok);
								}else{
									$current_pribil = 0;
									$current_ubitok = 0;
									if($prof < 0){
										$current_ubitok = $current_ubitok + $prof;
									}else{
										$current_pribil = $current_pribil + $prof;
									}
									$symbolData[$key] = array($current_pribil, $current_ubitok);
								}
								
   							}
   						}

   						/*$cena_open = array_sum($p1);
   						$sl = array_sum($p3);
   						$cena_close = array_sum($p4);
   						$tp = array_sum($p5);
   						$profit = array_sum($p2);
   						if (substr($profit, 0, 1) === '0') {
   							$profit = preg_replace('/[^0-9]/', '', $profit);
   							$profit = ltrim($profit, '0');
   						}
   						if($key2 == 'SELL'){
   							$profit = $profit * (-1);
   						}

   						$page->setCellValue("B".$startRowSymbol, $key); //записываем символ
   						$page->setCellValue("C".$startRowSymbol, $key2); //записываем тип
   						$page->setCellValue("D".$startRowSymbol, $cena_open); //записываем цену входа
   						if($key2 == 'SELL' && strpos('-', $profit) < 0){
							$page->setCellValue("H".$startRowSymbol, '-'.$profit); //Profit
						}elseif($key2 == 'SELL'){
							$page->setCellValue("H".$startRowSymbol, str_replace('-', '', $profit)); //Profit
						}else{
							$page->setCellValue("H".$startRowSymbol, $profit); //Profit
						}
   						$page->setCellValue("F".$startRowSymbol, $sl); //записываем стоп лосс
   						$page->setCellValue("G".$startRowSymbol, $cena_close); //цена закрытия
						$page->setCellValue("E".$startRowSymbol, $tp);*/

					}







?>
</td>
</tr>
<?

$endMerg =  $startRowDate+$colSym-1;
$page->mergeCells('A'.$startRowDate.':A'.$endMerg);
$startRowDate = $startRowDate+$colSym;

$sheet->getStyle('A'.$endMerg.':H'.$endMerg)->applyFromArray($border_style_b);

}
?></table><?
//Добовляем итоговую таблицу
$page->setCellValue("K1", "Актив")->setCellValue("L1", "Прибыль")->setCellValue("M1", "Убыток")->setCellValue("N1", "Итог");
cellColor("K1", 'FFFF00'); cellColor("L1", 'c8ff7c'); cellColor("M1", 'ffa0a0'); cellColor("N1", 'FFFF00');
$sheet->getStyle('K1')->applyFromArray($border_style_r);
$sheet->getStyle('L1')->applyFromArray($border_style_r);
$sheet->getStyle('M1')->applyFromArray($border_style_r);
$sheet->getStyle('N1')->applyFromArray($border_style_r);
$sheet->getStyle('K1')->applyFromArray($border_style_b);
$sheet->getStyle('L1')->applyFromArray($border_style_b);
$sheet->getStyle('M1')->applyFromArray($border_style_b);
$sheet->getStyle('N1')->applyFromArray($border_style_b);
$sheet->getStyle('K1')->applyFromArray($border_style_l);
$sheet->getStyle('L1')->applyFromArray($border_style_l);
$sheet->getStyle('M1')->applyFromArray($border_style_l);
$sheet->getStyle('N1')->applyFromArray($border_style_l);

$result_pribil = 0;
$result_ubitok = 0;
$iteration_counter = 2;
foreach ($symbolData as $key => $value) {
	$page->setCellValue("K".$iteration_counter, $key)->setCellValue("L".$iteration_counter, $value[0])->setCellValue("M".$iteration_counter, $value[1])->setCellValue("N".$iteration_counter, ($value[1] + $value[0]));

	if(($value[1] + $value[0]) < 0){
		$profit_color = 'ffa0a0';
	}else{
		$profit_color = 'c8ff7c';
	}

	cellColor("K".$iteration_counter, 'FFFF00'); cellColor("L".$iteration_counter, 'c8ff7c'); cellColor("M".$iteration_counter, 'ffa0a0'); cellColor("N".$iteration_counter, $profit_color);
	$sheet->getStyle('K'.$iteration_counter)->applyFromArray($border_style_r);
	$sheet->getStyle('L'.$iteration_counter)->applyFromArray($border_style_r);
	$sheet->getStyle('M'.$iteration_counter)->applyFromArray($border_style_r);
	$sheet->getStyle('N'.$iteration_counter)->applyFromArray($border_style_r);
	$sheet->getStyle('K'.$iteration_counter)->applyFromArray($border_style_b);
	$sheet->getStyle('L'.$iteration_counter)->applyFromArray($border_style_b);
	$sheet->getStyle('M'.$iteration_counter)->applyFromArray($border_style_b);
	$sheet->getStyle('N'.$iteration_counter)->applyFromArray($border_style_b);
	$sheet->getStyle('K'.$iteration_counter)->applyFromArray($border_style_l);
	$sheet->getStyle('L'.$iteration_counter)->applyFromArray($border_style_l);
	$sheet->getStyle('M'.$iteration_counter)->applyFromArray($border_style_l);
	$sheet->getStyle('N'.$iteration_counter)->applyFromArray($border_style_l);

	$result_pribil += $value[0];
	$result_ubitok += $value[1];

	$iteration_counter ++;
}
	$page->setCellValue("K".$iteration_counter, 'Итог:')->setCellValue("L".$iteration_counter, $result_pribil)->setCellValue("M".$iteration_counter, $result_ubitok)->setCellValue("N".$iteration_counter, ($result_pribil + $result_ubitok));

$phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(11);
$phpexcel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

for ($i=1; $i < $startRowDate; $i++) {
	$sheet->getStyle('A'.$i)->applyFromArray($style);
	cellColor('A'.$i, 'FFFF00');
	$sheet->getStyle('A'.$i)->applyFromArray($border_style_r);
	$sheet->getStyle('B'.$i)->applyFromArray($border_style_r);
	$sheet->getStyle('C'.$i)->applyFromArray($border_style_r);
	$sheet->getStyle('D'.$i)->applyFromArray($border_style_r);
	$sheet->getStyle('E'.$i)->applyFromArray($border_style_r);
	$sheet->getStyle('F'.$i)->applyFromArray($border_style_r);
	$sheet->getStyle('G'.$i)->applyFromArray($border_style_r);
	$sheet->getStyle('H'.$i)->applyFromArray($border_style_r);
}

$sheet->getStyle('A1:H1')->applyFromArray($border_style_b);
foreach(array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'K', 'L', 'M', 'N') as $columnRow) {
	$phpexcel->getActiveSheet()->getColumnDimension($columnRow)->setWidth(16);
	$phpexcel->getActiveSheet()->getStyle($columnRow.'1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}

$page->setTitle("MAcompany Report");
$objWriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
$objWriter->save($excelFileName);