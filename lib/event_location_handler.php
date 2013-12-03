<?php

require_once "wx_push_handler.php";

class EventLocationHandler extends WxPushHandler {
  public function __construct($data, &$sdk) {
    parent::__construct($data, $sdk);
  }

  public function response() {
    return;
  }
};

