<?php
require_once "config.php";
require_once "lib/wxapi.php";
require_once "lib/wx_push_data.php";

$wxapi = new WxApi(APPID, APPSECRET);
$openid = "oyuqWt9uihlSsL5IFAh0YZnf6nLs";
// $openid = "oyuqWtxUURNYL0K6CMwsUkK04jbs";
// $openid = "oyuqWtwtOlXl5kozurXajuo2RdYA";
$info = $wxapi->getUserInfo($openid);

$data = array('articles' => array());

$data['articles'][] = array(
    'title' => $info['nickname']."，您的朋友有新的动态",
    'description' => '很多很多的动态哦,很多很多的动态哦,很多很多的动态哦,很多很多的动态哦.',
    'url' => 'http://wxapi.teeker.com/web/show_contacts.php?openid='.$openid,
    'picurl' => $info['headimgurl']
);
$wxapi->pushMessage($openid, 'news', $data);
