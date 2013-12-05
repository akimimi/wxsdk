<?php
if (!defined('ROOT'))
  define('ROOT', dirname(__FILE__)."/..");

require_once ROOT."/lib/event_subscribe_handler.php";

class MySubscribeHandler extends EventSubscribeHandler {
  public function __construct($data, &$api) {
    parent::__construct($data, $api);
  }

  public function response() {
    parent::response();

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
    $this->wxSdk->returnPushArticles($this->wxPushData->toUserName,
      $this->wxPushData->fromUserName,
      array(
        array(
          'title' => '连接常联系',
          'description' => '与手机号码绑定，可以随时查看常联系好友动态',
          'picurl' => $picurl,
          'url' => $url
      ))
    );
  }
};
?>
