<?php

require_once "wx_push_handler.php";

class MsgHandler extends WxPushHandler {
  public function __construct($data, &$api) {
    parent::__construct($data, $api);
  }

  private $code = "1234";

  public function response() {
    if (!$this->wxPushData->valid)
      return;

    if (strpos($this->wxPushData->content, "绑定") !== FALSE) {
      $this->sendVerifyCode();
    }

    if (strpos($this->wxPushData->content, $this->code) !== FALSE) {
      $this->registerSuccess();
    }
  }

  public function sendVerifyCode() {
    $profile = $this->wxApi->getUserInfo($this->wxPushData->fromUserName);

    $content = $profile['nickname']."请回复".$this->code;

    echo "<xml>".PHP_EOL;
    echo "  <ToUserName><![CDATA[".$this->wxPushData->fromUserName."]]></ToUserName>".PHP_EOL;
    echo "  <FromUserName><![CDATA[".$this->wxPushData->toUserName."]]></FromUserName>".PHP_EOL;
    echo "  <CreateTime>".time()."</CreateTime>".PHP_EOL;
    echo "  <MsgType><![CDATA[text]]></MsgType>".PHP_EOL;
    echo "  <Content><![CDATA[$content]]></Content>".PHP_EOL;
    echo "</xml>".PHP_EOL;
  }

  public function registerSuccess() {
    $profile = $this->wxApi->getUserInfo($this->wxPushData->fromUserName);

    $content = $profile['nickname'].", 您可以收到圈子动态了";

    echo "<xml>".PHP_EOL;
    echo "  <ToUserName><![CDATA[".$this->wxPushData->fromUserName."]]></ToUserName>".PHP_EOL;
    echo "  <FromUserName><![CDATA[".$this->wxPushData->toUserName."]]></FromUserName>".PHP_EOL;
    echo "  <CreateTime>".time()."</CreateTime>".PHP_EOL;
    echo "  <MsgType><![CDATA[text]]></MsgType>".PHP_EOL;
    echo "  <Content><![CDATA[$content]]></Content>".PHP_EOL;
    echo "</xml>".PHP_EOL;
  }
};

