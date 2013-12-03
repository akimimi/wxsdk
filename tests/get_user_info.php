<?php
require_once "config.php";
require_once "lib/wxapi.php";
require_once "lib/wx_push_data.php";

$wxapi = new WxApi(APPID, APPSECRET);
$openid = "oyuqWt9uihlSsL5IFAh0YZnf6nLs";

$info = $wxapi->getUserInfo($openid);
print_r($info);
