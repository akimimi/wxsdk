<?php
require_once "config.php";
require_once "lib/wxsdk.php";
require_once "lib/wx_push_data.php";

$wxsdk = new WxSdk(APPID, APPSECRET);
$openid = "oyuqWt9uihlSsL5IFAh0YZnf6nLs";
// $openid = "oyuqWtxUURNYL0K6CMwsUkK04jbs";
// $openid = "oyuqWtwtOlXl5kozurXajuo2RdYA";
$info = $wxsdk->getUserInfo($openid);

// $data = array('articles' => array());
// 
// $data['articles'][] = array(
//     'title' => $info['nickname']."，您的朋友有新的动态",
//     'description' => '很多很多的动态哦,很多很多的动态哦,很多很多的动态哦,很多很多的动态哦.',
//     'url' => 'http://wxsdk.teeker.com/web/show_contacts.php?openid='.$openid,
//     'picurl' => $info['headimgurl']
// );
// $rt = $wxsdk->pushMessage($openid, 'news', $data);
// $data = array('text' => "新的消息很多，<a href=\"http://teeker.com\">查看</a>");
// $rt = $wxsdk->pushMessage($openid, 'text', $data);

$mediaId = $wxsdk->uploadResource('image', 'images/0.jpg');
if ($mediaId !== FALSE) {
  $wxsdk->pushMessage($openid, 'image', array('image_media_id' => $mediaId));
  $rt = true;
}
else {
  $rt = false;
}

if (!$rt) {
  echo "[error]".$wxsdk->errorCode.":".$wxsdk->errorMsg.PHP_EOL;
}
