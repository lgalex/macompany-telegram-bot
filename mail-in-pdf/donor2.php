<?php
require_once("/home/macompany/public_html/components/bot_api_ma.php");
require_once("/home/macompany/public_html/components/db_ma.php");
require_once("/home/macompany/public_html/components/config_ma.php");
$botAPI = new BotAPI();

	require_once("/home/macompany/public_html/vendor/autoload.php"); //mPDF
	$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-P']);

	$folder = imap_utf8_to_mutf7('Донор2');
	$mailbox = imap_open('{imap.yandex.ru:993/ssl}'.$folder, 'ma@macompany.ru', 'MaComp123');
	$count = imap_num_msg($mailbox);

	if ($count >= 1) {
		$mail = imap_qprint(imap_body($mailbox, 1));
		$mail = str_replace(array('<table style="width: 100%;;">'), '<table class="delite">', $mail);
		$mail = str_replace(array('<tr bgcolor="#0F7F12">'), '<tr class="delite">', $mail);
		$mail = str_replace(array('<td bgcolor="#777777"><table width="94%" border="0" align="center" cellpadding="0" cellspacing="0">', '<td style="font-family: Arial, Helvetica, sans-serif; font-size:11px; text-align: center; line-height:16px">'), '<td class="delite">', $mail);
		$mail = preg_replace('~<table class="delite">.+?</table>~is', '', $mail);
		$mail = preg_replace('~<tr class="delite">.+?</tr>~is', '', $mail);
		$mail = preg_replace('~<td class="delite">.+?</td>~is', '', $mail);
		$mail = str_replace('<td bgcolor="#777777">&nbsp;</td>', '', $mail);

		for ($i = 1; $i <= $count; $i++) {
			imap_delete($mailbox, $i); //помечаем на удаление
			imap_expunge($mailbox); //удаляем все письма
		}

		imap_close($mailbox);

		$mpdf->WriteHTML($mail);
		$mpdf->Output('/home/macompany/public_html/mail-in-pdf/donor_2/new_report_2.pdf'); //сохраняем pdf

		$res = $mysqli->query("SELECT * FROM `users` WHERE `service` > '0' ");
		if ($res) while ($result = $res->fetch_assoc()) {
			if(strtotime($result['sdate']) < strtotime(date('Y-m-d H:i:s'))){
				/*if($result['notification_send'] != 1){
					$mysqli->query("UPDATE `users` SET notification_send='1' WHERE id='".$result['id']."'");
					$botAPI->sendMessage($result["id"], 'Уважаемый пользователь, срок Вашей подписки истёк, Вы можете продлить ее через основное меню, нажав «Подписки → Выбрав одну из трех подписок → Приобрести»');
				}*/
			}else{
				$pdf = fopen('/home/macompany/public_html/mail-in-pdf/donor_2/new_report_2.pdf', 'r+');
				rewind($pdf);
				$file_data = stream_get_contents($pdf);
				$botAPI->sendExcel($result['id'], "Новый отчёт.pdf", $file_data);
			}
		}
		unlink('/home/macompany/public_html/mail-in-pdf/donor_2/new_report_2.pdf'); //удаляем файл
	}else{
		exit("Новых отчётов в папке \"Донор 2\" еще не было...");
	}

	?>