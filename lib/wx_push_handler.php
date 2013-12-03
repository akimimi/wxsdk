<?php

abstract class WxPushHandler {
  var $wxPushData = null;
  var $wxSdk = null;

  public function __construct($data, &$sdk) {
    $this->wxPushData = $data;
    $this->wxSdk = $sdk;
  }

  public function setPushData($data) {
    $this->wxPushData = $data;
  }

  abstract public function response();
};
