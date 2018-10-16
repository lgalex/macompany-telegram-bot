<?php
	date_default_timezone_set('Europe/London');
	set_include_path(get_include_path() . PATH_SEPARATOR . './Classes/');

	require_once("/home/macompany/public_html/components/bot_api_ma.php");
	require_once("/home/macompany/public_html/components/db_ma.php");
	require_once("/home/macompany/public_html/components/config_ma.php");

	include 'PHPExcel/IOFactory.php';
	include 'fnc.php';

	$botAPI = new BotAPI();

	$period = 14; //days
	$dateBack = date('Y-m-d', strtotime('-'.$period.' days', strtotime(date('Y-m-d'))));

	$phpexcel = new PHPExcel();
	$page = $phpexcel->setActiveSheetIndex(0);
	$page->setCellValue("A1", "Дата")->setCellValue("B1", "Актив")->setCellValue("C1", "Тип")->setCellValue("D1", "Цена входа")->setCellValue("E1", "TakeProfit")->setCellValue("F1", "StopLoss")->setCellValue("G1", "Цена закрытия")->setCellValue("H1", "ИТОГ");

	cellColor('A1', 'FFFF00'); cellColor('B1', 'FFFF00'); cellColor('C1', 'FFFF00'); cellColor('D1', 'FFFF00'); cellColor('E1', 'FFFF00'); cellColor('F1', 'FFFF00'); cellColor('G1', 'FFFF00'); cellColor('H1', 'FFFF00');

	$border_style_r = array('borders' => array('right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => '7f8c8d'),)));
	$border_style_b = array('borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM, 'color' => array('argb' => '7f8c8d'),)));
	$sheet = $phpexcel->getActiveSheet();


	$preGroup = array();
	$db = $mysqli->query("SELECT * FROM `deal` WHERE DATE(date_open) BETWEEN '".$dateBack."' AND '".date('Y-m-d')."' ORDER BY `date_open`"); // date_close
	if ($db) while ($deal = $db->fetch_assoc()) {
		if ($deal['type'] == "SELL" OR $deal['type'] == "BUY") {
			$date = explode(" ", $deal['date_open']);
			$date = $date[0];
			$preGroup[] = array("date_open" => $date, "id" => $deal['id'], "type" => $deal['type']);
		}
	}

	$preGroupTwo = array();
	foreach ($preGroup as $value) {
	    $preGroupTwo[$value['date_open']][] = $value['id'];
	}
	$preGroupTwo = array_map(function($v) { return '' . implode(',', $v) . ''; }, $preGroupTwo);


	$startRowDate = 2;
	$startRowSymbol = 2;
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

		$page->setCellValue("A".$startRowDate, $dateGroup); //записываем дату
		

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
						$preSymbol[] = array("symbol" => $symbol, "id" => $dealNum['id'], "type" => $dealNum['type']);
					}
					/*echo '<pre>';
					var_export($preSymbol);
					echo '</pre>';*/

					$preSymbolTwo = array();
					foreach ($preSymbol as $valueSy) {
					    $preSymbolTwo[][$valueSy['symbol']][$valueSy['type']] = $valueSy['id'];
					}
					///$preSymbolTwo = array_map(function($v) { return '' . implode(',', $v) . ''; }, $preSymbolTwo);
					/*echo '<pre>';
					var_export($preSymbolTwo);
					echo '</pre>';*/


					$colSym = 0;
					foreach ($preSymbolTwo as $isSymbol => $idDealSy) {
						$page->setCellValue("B".$startRowSymbol, $isSymbol); //записываем символ
						$startRowSymbol++;
						$colSym++;
					}
					echo '<pre>';
					var_export($preSymbolTwo);
					echo '</pre>';
					
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
	foreach(array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H') as $columnRow) {
		$phpexcel->getActiveSheet()->getColumnDimension($columnRow)->setWidth(16);
		$phpexcel->getActiveSheet()->getStyle($columnRow.'1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	}

	$page->setTitle("MAcompany Report");
	$objWriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
	$objWriter->save("test.xlsx");