<?php
if (!defined('ROOT'))
  define('ROOT', dirname(__FILE__)."/..");

require_once ROOT."/lib/event_scan_handler.php";

class MyScanHandler extends EventScanHandler {
  public function __construct($data, &$sdk) {
    parent::__construct($data, $sdk);
  }

  public function response() {
    parent::response();

    if (!$this->wxPushData->valid)
      return;

    switch ($this->wxPushData->event) {
      case "scan":
        $this->showEventKey();
        break;
    }
  }

  public function showContacts() {
    $profile = $this->wxSdk->getUserInfo($this->wxPushData->fromUserName);
    echo "<xml>".PHP_EOL;
    echo "  <ToUserName><![CDATA[".$this->wxPushData->fromUserName."]]></ToUserName>".PHP_EOL;
    echo "  <FromUserName><![CDATA[".$this->wxPushData->toUserName."]]></FromUserName>".PHP_EOL;
    echo "  <CreateTime>".time()."</CreateTime>".PHP_EOL;
    echo "  <MsgType><![CDATA[text]]></MsgType>".PHP_EOL;
    echo "  <Content><![CDATA[".$profile['nickname']."点击了".$this->wxPushData->eventKey."]]></Content>".PHP_EOL;
    echo "</xml>".PHP_EOL;
  }
}
