<?php
if (!defined('ROOT'))
  define('ROOT', dirname(__FILE__)."/..");

require_once ROOT."/lib/event_location_handler.php";

class MyLocationHandler extends EventLocationHandler {
  public function __construct($data, &$sdk) {
    parent::__construct($data, $sdk);
  }

  public function response() {
    parent::response();

    if (!$this->wxPushData->valid)
      return;

    // Just log it.
    $log  = "====".date("Y-m-d H:i:s")."====".PHP_EOL;
    $log .= "openid:".$this->wxPushData->fromUserName.PHP_EOL;
    $log .= "latitude:".$this->wxPushData->location['latitude'].PHP_EOL;
    $log .= "longtitude:".$this->wxPushData->location['longtitude'].PHP_EOL;
    $log .= "precision:".$this->wxPushData->location['precision'].PHP_EOL;
    file_put_contents("location.log", $log, FILE_APPEND);
  }

