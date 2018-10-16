<?php
/*
 * Здесь ничего менять не нужно
 */
require_once("components/bot_api_ma.php");
require_once("components/MySimplePayMerchant.class.php");

$sp = new MySimplePayMerchant();
$sp->process_result_request();
