<?php
  $openid = $_GET['openid'];
  if (!empty($openid)) {
    include_once "../config.php";
    include_once "../lib/wxapi.php";
    $wxapi = new WxApi(APPID, APPSECRET);
    $info = $wxapi->getUserInfo($openid);
  }
?>
<!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>通讯录</title>
  <style>
  html {width:100%; height:100%}
  </style>
</head>
<body>
  <?php echo "<p>".$info['nickname']."的通讯录</p>";?>
</body>
</html>

