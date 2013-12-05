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
    $content = $profile['nickname']."点击了".$this->wxPushData->eventKey;
    $this->wxSdk->returnPushText($this->wxPushData->toUserName, 
      $this->wxPushData->fromUserName, 
      $content);
  }
}
