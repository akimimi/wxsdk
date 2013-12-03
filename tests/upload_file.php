<?php
require_once "config.php";
require_once "lib/wxsdk.php";
require_once "lib/wx_push_data.php";

$wxsdk = new WxSdk(APPID, APPSECRET);
$openid = "oyuqWt9uihlSsL5IFAh0YZnf6nLs";
$info = $wxsdk->getUserInfo($openid);

$filename = dirname(__FILE__)."/images/0.jpg";

$rt = $wxsdk->uploadResource('image', $filename);
print_r($rt);
