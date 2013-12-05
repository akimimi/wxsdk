<?php

require_once ROOT."/lib/wx_push_handler.php";

class MessageHandler extends WxPushHandler {
  public function __construct($data, &$sdk) {
    parent::__construct($data, $sdk);
  }

  public function response() {
    return;
  }
};

