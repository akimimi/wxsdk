<?php
require_once "config.php";

require_once "lib/wxsdk.php";
require_once "lib/wx_push_data.php";
require_once "sample/my_click_handler.php";
require_once "sample/my_subscribe_handler.php";
require_once "sample/my_scan_handler.php";

file_put_contents("log.txt", "=====".date('Y-m-d H:i:s')."====".PHP_EOL, FILE_APPEND);
file_put_contents("log.txt", print_r($GLOBALS["HTTP_RAW_POST_DATA"], true).PHP_EOL, FILE_APPEND);

$wxsdk = new WxSdk(APPID, APPSECRET);
$wxsdk->setHandler('click', new MyClickhandler(null, $wxsdk));
$wxsdk->setHandler('subscribe', new MySubscribeHandler(null, $wxsdk));
$wxsdk->setHandler('scan', new MyScanHandler(null, $wxsdk));

$wxsdk->validatePushMessage();
$wxsdk->responsePushMessage();

file_put_contents("log.txt", "access_token:".$wxsdk->accessToken.PHP_EOL, FILE_APPEND);
