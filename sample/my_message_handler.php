<?php
if (!defined('ROOT'))
  define('ROOT', dirname(__FILE__)."/..");

require_once ROOT."/lib/message_handler.php";

class MyMessageHandler extends MessageHandler {
  public function __construct($data, &$sdk) {
    parent::__construct($data, $sdk);
  }

  private $code = "1234";

  public function response() {
    parent::response();

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
    $profile = $this->wxSdk->getUserInfo($this->wxPushData->fromUserName);

    $content = $profile['nickname']."请回复".$this->code;

    $this->wxSdk->returnPushText($this->wxPushData->toUserName, $this->wxPushData->fromUserName, $content);
  }

  public function registerSuccess() {
    $profile = $this->wxSdk->getUserInfo($this->wxPushData->fromUserName);

    $content = $profile['nickname'].", 您可以收到圈子动态了";

    $this->wxSdk->returnPushText($this->wxPushData->toUserName, $this->wxPushData->fromUserName, $content);
  }
};


