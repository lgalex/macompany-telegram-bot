<?php

	function cellColor($cells,$color){
	    global $phpexcel;

	    $phpexcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
	        'type' => PHPExcel_Style_Fill::FILL_SOLID,
	        'startcolor' => array(
	            'rgb' => $color
	        )
	    ));
	}

	$style = array(
	    'alignment' => array(
	        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
	        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
	    )
	);

	function datePrePandBack($date) {
	    $createDate = DateTime::createFromFormat('Y-m-d', $date);
	    $dateToPay = $createDate->format('d.m.Y');
	    return $dateToPay;
    }

	?>

	<style type="text/css">
		th {
			border-right: 1px solid #000;
			padding: 4px;
		}

		tr,td {
			padding: 6px;
			border-bottom: 1px solid #000;
		}
	</style>