<?
//set_include_path(get_include_path() . PATH_SEPARATOR . './Classes/');

require_once("/home/macompany/public_html/components/db_ma.php");

include 'report/PHPExcel/IOFactory.php';
include 'report/fnc.php';

$excelFileName = "tempreport.xlsx";

$phpexcel = new PHPExcel();
$page = $phpexcel->setActiveSheetIndex(0);

$currow = 1;

function writeRow($page, $i, $data) {
	$alphabet = "ABCDEFGHIJKLMNOP";
	foreach ($data as $key => $value) {
		$page->setCellValue($alphabet[$key].$i, $value);
	}
}

writeRow($page, $currow, ["ID пользователя", "Имя фамилия (username)", "ID агента", "Дата регистрации", "Активная подписка", "Истекает"]);

$currow++;
writeRow($page, $currow, [10, 2, 3, 4, 5, 6]);

$objWriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
$objWriter->save($excelFileName);
