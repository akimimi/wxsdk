<?php

require_once "wx_push_handler.php";

class EventSubscribeHandler extends WxPushHandler {
  public function __construct($data, &$api) {
    parent::__construct($data, $api);
  }

  public function response() {
    if (!$this->wxPushData->valid)
      return;

    switch ($this->wxPushData->event) {
      case "subscribe":
        $this->connectClx();
        break;
    }
  }

  public function connectClx() {
    $picurl = "http://pic.teeker.com/designs/1391/1391_9b6c3692543c905e0fa5a2ab166f9fb9_500x500.png";
    $url = "http://wxapi.teeker.com/web/connect_clx.php?openid=".$this->wxPushData->fromUserName;
    echo "<xml>".PHP_EOL;
    echo "  <ToUserName><![CDATA[".$this->wxPushData->fromUserName."]]></ToUserName>".PHP_EOL;
    echo "  <FromUserName><![CDATA[".$this->wxPushData->toUserName."]]></FromUserName>".PHP_EOL;
    echo "  <CreateTime>".time()."</CreateTime>".PHP_EOL;
    echo "  <MsgType><![CDATA[news]]></MsgType>".PHP_EOL;
    echo "  <ArticleCount>1</ArticleCount>".PHP_EOL;
    echo "  <Articles>".PHP_EOL;
    echo "    <item>".PHP_EOL;
    echo "      <Title><![CDATA[连接常联系]]></Title>".PHP_EOL;
    echo "      <Description><![CDATA[与手机号码绑定，可以随时查看常联系好友动态]]></Description>".PHP_EOL;
    echo "      <PicUrl><![CDATA[$picurl]]></PicUrl>".PHP_EOL;
    echo "      <Url><![CDATA[$url]]></Url>".PHP_EOL;
    echo "    </item>".PHP_EOL;
    echo "  </Articles>".PHP_EOL;
    echo "</xml>".PHP_EOL;
  }
};

