<?php

require_once ROOT."/lib/wx_push_handler.php";

class EventScanHandler extends WxPushHandler {
  public function __construct($data, &$api) {
    parent::__construct($data, $api);
  }

  public function response() {
    return;
  }
};
