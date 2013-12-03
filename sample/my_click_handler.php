<?php
if (!defined('ROOT'))
  define('ROOT', dirname(__FILE__)."/..");

require_once ROOT."/lib/event_click_handler.php";

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
    echo "      <Title><![CDATA[".$profile['nickname']."]]></Title>".PHP_EOL;
    echo "      <Description><![CDATA[描述]]></Description>".PHP_EOL;
    echo "      <PicUrl><![CDATA[$picurl]]></PicUrl>".PHP_EOL;
    echo "      <Url><![CDATA[$url]]></Url>".PHP_EOL;
    echo "    </item>".PHP_EOL;
    echo "  </Articles>".PHP_EOL;
    echo "</xml>".PHP_EOL;
  }
}
?>
