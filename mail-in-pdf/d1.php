<?php

	require_once("/home/macompany/public_html/vendor/autoload.php"); //mPDF
	$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A0-P']);
	
	$folder = imap_utf8_to_mutf7('Донор1');
	$mailbox = imap_open('{imap.yandex.ru:993/ssl}'.$folder, 'ma@macompany.ru', 'MaComp123');
	$count = imap_num_msg($mailbox);

	$mail = imap_qprint(imap_body($mailbox, 1));
	$mail = str_replace('http://email.autochartist.com/ssresearch/images/broker_logos/Alpari_AM_Logo.jpg', 'https://macompany.ru/mail-in-pdf/logo.jpg', $mail);
	$mail = str_replace(array('<td style="padding: 5px 10px; text-align: center; font-family: \'Open Sans\', sans-serif; font-size: 11px; color: #696A6C;" align="center">', '<td style="padding: 10px 10px; font-family: \'Open Sans\', sans-serif; font-size: 11px; color: #696A6C;" bgcolor="#F3F2F5">', '<td align="center" style="font-family: \'Open Sans\', sans-serif; font-size: 11px; color: #696A6C; text-align: center; padding: 5px 10px;">', '<td align="center" style="font-family: \'Open Sans\', sans-serif; font-size: 13px; color: #8A6D3B; background-color: #FCF8E3; padding: 10px; border: 1px solid #FAEBCC">'), '<td class="delite">', $mail);
	$mail = preg_replace('~<td class="delite">.+?</td>~is', '', $mail);
	//$mail = str_replace('<style>', '<style>body { width:480px; }  ', $mail);

	imap_close($mailbox);

	$mpdf->WriteHTML($mail);
	$mpdf->Output('/home/macompany/public_html/mail-in-pdf/d1.pdf');

	//echo $mail;