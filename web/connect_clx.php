<?php
  $openid = $_GET['openid'];
  if (!empty($openid)) {
    include_once "../config.php";
    include_once "../lib/wxapi.php";
    $wxapi = new WxApi(APPID, APPSECRET);
    $info = $wxapi->getUserInfo($openid);
  }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>连接常联系</title>
  <style>
  html {width:100%; height:100%}
  .wrap {width:320px;margin:0 auto;height:100%;word-break: break-all; word-wrap: break-word;padding-bottom:50px;}
  .avatar {display:block;width:100%;margin:0 auto;text-align:center}
  .avatar img {height:80px;width:80px}
  .nickname {display:block;font-size:12px;width:100%;margin:0 auto;text-align:center}
  .input {display:block;font-size:20px;width:100%;margin:0 auto;text-align:center}
  .input input {font-size:20px}
  </style>
</head>
<body>
  <div class='wrap'>
    <div class='avatar'><img src="/images/0.jpg"/></div>
    <div class='nickname'><p><?php echo $info['nickname']?></p></div>
    <form action="/web/show_contacts.php?openid=<?php echo $openid?>" method="post">
      <div class='input'>
        <input type="text" name="mobile" size='15' placeholder="请输入手机号码"/>
      </div>
      <div class='input'>
        <input type="text" name="verify" size='15' placeholder="请输入验证码"/>
      </div>
      <div class='input'>
        <input type="submit" value="开始"/>
      </div>
    </form>
  </div>
</body>
</html>
