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
    $this->wxSdk->returnPushArticles($this->wxPushData->toUserName, 
      $this->wxPushData->fromUserName,
      array(
        array(
          'title' => $profile['nickname'],
          'description' => '描述',
          'picurl' => $picurl,
          'url' => $url
        ),
        array(
          'title' => '2'.$profile['nickname'],
          'description' => '描述',
          'picurl' => $picurl,
          'url' => $url
        )
      )
    );
  }
}
?>
