<?php
require_once "../config.php";
require_once ROOT."/lib/wxsdk.php";
require_once ROOT."/lib/wx_push_data.php";

$wxsdk = new WxSdk(APPID, APPSECRET);
$openid = "oyuqWt9uihlSsL5IFAh0YZnf6nLs";
$info = $wxsdk->getUserInfo($openid);

$filename = ROOT."/images/0.jpg";

$rt = $wxsdk->uploadResource('image', $filename);
print_r("media_id:$rt".PHP_EOL);
