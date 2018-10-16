<?php
require_once("/home/macompany/public_html/components/bot_api_ma.php");
require_once("/home/macompany/public_html/components/db_ma.php");
require_once("/home/macompany/public_html/components/config_ma.php");
$botAPI = new BotAPI();

	require_once("/home/macompany/public_html/vendor/autoload.php"); //mPDF
	$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4-P']);
	
	$folder = imap_utf8_to_mutf7('Донор1');
	$mailbox = imap_open('{imap.yandex.ru:993/ssl}'.$folder, 'ma@macompany.ru', 'MaComp123');
	$count = imap_num_msg($mailbox);

	if ($count >= 1) {
		$mail = imap_qprint(imap_body($mailbox, 1));
		$mail = str_replace('http://email.autochartist.com/ssresearch/images/broker_logos/Alpari_AM_Logo.jpg', 'https://macompany.ru/mail-in-pdf/logo.jpg', $mail);
		$mail = str_replace(array('<td style="padding: 5px 10px; text-align: center; font-family: \'Open Sans\', sans-serif; font-size: 11px; color: #696A6C;" align="center">', '<td style="padding: 10px 10px; font-family: \'Open Sans\', sans-serif; font-size: 11px; color: #696A6C;" bgcolor="#F3F2F5">', '<td align="center" style="font-family: \'Open Sans\', sans-serif; font-size: 11px; color: #696A6C; text-align: center; padding: 5px 10px;">', '<td align="center" style="font-family: \'Open Sans\', sans-serif; font-size: 13px; color: #8A6D3B; background-color: #FCF8E3; padding: 10px; border: 1px solid #FAEBCC">'), '<td class="delite">', $mail);
		$mail = preg_replace('~<td class="delite">.+?</td>~is', '', $mail);

		for ($i = 1; $i <= $count; $i++) {
			imap_delete($mailbox, $i); //помечаем на удаление
			imap_expunge($mailbox); //удаляем все письма
		}

		imap_close($mailbox);

		$mpdf->WriteHTML($mail);
		$mpdf->Output('/home/macompany/public_html/mail-in-pdf/donor_1/new_report_1.pdf'); //сохраняем pdf


		$res = $mysqli->query("SELECT * FROM `users` WHERE `service` > '0' ");
		if ($res) while ($result = $res->fetch_assoc()) {
			if(strtotime($result['sdate']) < strtotime(date('Y-m-d H:i:s'))){
				/*if($result['notification_send'] != 1){
					$mysqli->query("UPDATE `users` SET notification_send='1' WHERE id='".$result['id']."'");
					$botAPI->sendMessage($result["id"], 'Уважаемый пользователь, срок Вашей подписки истёк, Вы можете продлить ее через основное меню, нажав «Подписки → Выбрав одну из трех подписок → Приобрести»');
				}*/
			}else{
				$pdf = fopen('/home/macompany/public_html/mail-in-pdf/donor_1/new_report_1.pdf', 'r+');
				rewind($pdf);
				$file_data = stream_get_contents($pdf);
				$botAPI->sendExcel($result['id'], "Новый отчёт.pdf", $file_data);
			}
		}
		unlink('/home/macompany/public_html/mail-in-pdf/donor_1/new_report_1.pdf'); //удаляем файл
	}else{
		exit("Новых отчётов в папке \"Донор 1\" еще не было...");
	}

	?>

