<?php

class WxPushData {
  var $valid = false;

  var $toUserName = null;

  var $fromUserName = null;

  var $createTime = null;

  var $msgType = null;

  var $content = null;

  var $event = null;

  var $eventKey = null;

  var $ticket = null;

  var $location = array(
    'latitude' => 0, 
    'longtitude' => 0, 
    'precision' => 0
  );

  public function __construct($xmlString = null) {
    if (!empty($xmlString))
      $this->parse($xmlString);
  }

  public function parse($xmlString) {
    $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
    if ($xml === FALSE) {
      $this->valid = false;
      return false;
    }

    $this->valid = true;

    $this->toUserName = (!empty($xml->ToUserName)? (string)$xml->ToUserName: null);

    $this->fromUserName = (!empty($xml->FromUserName)? (string)$xml->FromUserName: null);
    $this->createTime = (!empty($xml->CreateTime)? (int)$xml->CreateTime: 0);
    $this->msgType = (!empty($xml->MsgType)? (string)$xml->MsgType: null);
    $this->content = (!empty($xml->Content)? (string)$xml->Content: null);
    $this->event = (!empty($xml->Event)? (string)$xml->Event: null);
    $this->eventKey = (!empty($xml->EventKey)? (string)$xml->EventKey: null);
    $this->ticket = (!empty($xml->Ticket)? (string)$xml->Ticket: null);
    $this->location['latitude'] = (!empty($xml->Latitude)? (float)$this->Latitude: null);
    $this->location['longtitude'] = (!empty($xml->Lontitude)? (float)$this->Lontitude: null);
    $this->location['precision'] = (!empty($xml->Precision)? (float)$this->Precision: null);
  }
};
