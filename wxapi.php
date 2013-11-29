<?php
require_once "config.php";

require_once "lib/wxapi.php";
require_once "lib/wx_push_data.php";

$wxapi = new WxApi(APPID, APPSECRET);
$wxapi->validPushMessage();
$wxapi->responsePushMessage();

// file_put_contents("log.txt", print_r($GLOBALS["HTTP_RAW_POST_DATA"], true).PHP_EOL);

