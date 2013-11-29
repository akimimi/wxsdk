<?php

abstract class WxPushHandler {
  var $wxPushData = null;
  var $wxApi = null;

  public function __construct($data, &$api) {
    $this->wxPushData = $data;
    $this->wxApi = $api;
  }

  abstract public function response();
};
