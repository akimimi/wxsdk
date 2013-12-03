<?php
require_once "config.php";

require_once "lib/wxsdk.php";
require_once "lib/wx_push_data.php";

class MyClickhandler extends EventClickHandler {
  public function __construct($data, &$sdk) {
    parent::__construct($data, $sdk);
  }

  public function response() {
    parent::response();

    if (!$this->wxPushData->valid)
      return;

    switch ($this->wxPushData->eventKey) {
      case "V1_CONTACTS":
      case "V2_PROFILE":
        $this->showContacts();
        break;
    }
  }

  public function showContacts() {
    $profile = $this->wxSdk->getUserInfo($this->wxPushData->fromUserName);

    $picurl = $profile['headimgurl'];
    $url = "http://wxapi.teeker.com/web/show_contacts.php?openid=".$this->wxPushData->fromUserName;
    echo "<xml>".PHP_EOL;
    echo "  <ToUserName><![CDATA[".$this->wxPushData->fromUserName."]]></ToUserName>".PHP_EOL;
    echo "  <FromUserName><![CDATA[".$this->wxPushData->toUserName."]]></FromUserName>".PHP_EOL;
    echo "  <CreateTime>".time()."</CreateTime>".PHP_EOL;
    echo "  <MsgType><![CDATA[news]]></MsgType>".PHP_EOL;
    echo "  <ArticleCount>1</ArticleCount>".PHP_EOL;
    echo "  <Articles>".PHP_EOL;
    echo "    <item>".PHP_EOL;
    echo "      <Title><![CDATA[查看".$profile['nickname']."的通讯录]]></Title>".PHP_EOL;
    echo "      <Description><![CDATA[说明文字]]></Description>".PHP_EOL;
    echo "      <PicUrl><![CDATA[$picurl]]></PicUrl>".PHP_EOL;
    echo "      <Url><![CDATA[$url]]></Url>".PHP_EOL;
    echo "    </item>".PHP_EOL;
    echo "  </Articles>".PHP_EOL;
    echo "</xml>".PHP_EOL;
  }
}

$wxsdk = new WxSdk(APPID, APPSECRET);
$wxsdk->setHandler('click', new MyClickhandler(null, $wxsdk));
$wxsdk->validatePushMessage();

file_put_contents("log.txt", '1'.PHP_EOL);
$wxsdk->responsePushMessage();

file_put_contents("log.txt", print_r($GLOBALS["HTTP_RAW_POST_DATA"], true).PHP_EOL);

