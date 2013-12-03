<?php

if (!defined('ROOT'))
  define('ROOT', dirname(__FILE__)."/..");

require_once ROOT."/lib/event_click_handler.php";
require_once ROOT."/lib/event_subscribe_handler.php";
require_once ROOT."/lib/event_scan_handler.php";
require_once ROOT."/lib/event_location_handler.php";
require_once ROOT."/lib/msg_handler.php";
require_once ROOT."/lib/wx_sdk_error.php";

class WxSdk {

  /**
   * Request protocol.
   * @var string $port
   */
  var $protocol = "https";

  /**
   * Request port.
   * @var string $port
   */
  var $port = 443;

  /**
   * Request host of weixin API domain.
   * @var string $host 
   */
  var $host = "api.weixin.qq.com";

  /**
   * Request method: "curl" or "socket"
   * @var string $method 
   */
  var $method = "curl";

  /**
   * If set TRUE, the request time and error messages are log in database.
   * @var boolean $isLog 
   */
  var $isLog = false;

  /**
   * App ID of Wechat platform
   * @var string $appid 
   */
  var $appid = "";
  /**
   * App Secret of Wechat platform
   * @var string $appsecret
   */
  var $appsecret = "";

  /**
   * Access token of Wechat API.
   * @var string $accessToken
   */
  var $accessToken = "";
  /**
   * The expire timestamp of access token.
   * @var int $accessTokenExpires
   */
  var $accessTokenExpires = 0;

  /**
   * Error code of API response.
   * @var int $errorCode
   */
  var $errorCode = 0;
  /**
   * Error message of API response.
   * @var string $errorMsg
   */
  var $errorMsg = "";
  /**
   * The push data from Wechat API.
   * @var WxPushData $wxPushData
   */
  var $wxPushData = null;

  var $handlers = null;

  /**
   * Default construct function of WxSdk
   * @param string $appid
   * @param string $appsecret
   */
  public function __construct($appid, $appsecret) {
    $this->appid = $appid;
    $this->appsecret = $appsecret;
    $this->handlers = array();
    $this->handlers['subscribe'] = new EventSubscribeHandler(null, $this);
    $this->handlers['click'] = new EventClickHandler(null, $this);
    $this->handlers['scan'] = new EventScanHandler(null, $this);
    $this->handlers['location'] = new EventLocationHandler(null, $this);
  }

  /**
   * Set handler object to replace the default event handler.
   * @param string $handlerType Handler type string.
   * @param WxPushHandler $handler Handler object.
   */
  public function setHandler($handlerType, $handler) {
    $this->handlers[$handlerType] = $handler;
  }

  /**
   * Validate the push message by checking the consistence of the signature.
   * @param boolean $verbose If set true, the echostr from request will output.
   * @return boolean
   */
  public function validatePushMessage($verbose = false) {
    $echoStr = $_GET["echostr"];
    if ($this->checkSignature()) {
      if ($verbose) echo $echoStr;
      return true;
    }
    else {
      if ($verbose) echo "failed in validation.";
      return false;
    }
  }

  /**
   * Response push message from Wechat API.
   */
  public function responsePushMessage() {
    $str = $GLOBALS["HTTP_RAW_POST_DATA"];
    if (empty($str))
      return;

    $wxPushData = new WxPushData($str);
    if (!empty($wxPushData->msgType)) {
      switch (strtolower($wxPushData->msgType)) {
        case "event":
          $this->responseEvent($wxPushData);
          break;
        case "text":
          $this->responseMsg($wxPushData);
          break;
      }
    }
  }

  /**
   * Get user information from Wechat API.
   * @param string $openid Openid of the user to push.
   * @return array User information array.
   */
  public function getUserInfo($openid) {
    if (!$this->refreshAccessToken()) {
      throw new Exception();
    }
    $url = "/cgi-bin/user/info";
    $query = "access_token=".$this->accessToken."&openid=".$openid;
    $data = $this->request($url, $query);
    return $data;
  }

  /**
   * Push message to user.
   * @param string $openid Openid of the user to push.
   * @param string $type Message type string, including 'text', 'pic', 'news'
   * @param array $data Message data array.
   * @return boolean
   */
  public function pushMessage($openid, $type, $data) {
    if (!$this->refreshAccessToken()) {
      throw new Exception();
    }
    $url = "/cgi-bin/message/custom/send";
    $query = "access_token=".$this->accessToken;

    if ($type == "text") {
      return $this->pushText($url, $query, $openid, $data);
    }
    elseif ($type == "image") {
      return $this->pushImage($url, $query, $openid, $data);
    }
    elseif ($type == "voice") {
      return $this->pushVoice($url, $query, $openid, $data);
    }
    elseif ($type == "news") {
      return $this->pushNews($url, $query, $openid, $data);
    }
  }

  /**
   * Push text to user.
   * @param string $url The URL address of Wechat API.
   * @param string $query Query string of API request.
   * @param string $openid Openid of the user to push.
   * @param array $data Text data, including $data['text']
   * @return boolean
   */
  private function pushText($url, $query, $openid, $data) {
    if (empty($data) || empty($data['text'])) {
      $errorCode = WxSdkError::ERRCODE_NO_TEXT_FOR_MSG;
      $errorMsg = WxSdkError::ERRMSG_NO_TEXT_FOR_MSG;
      return false;
    }
    $post = array(
      'touser' => $openid,
      'msgtype' => 'text',
      'text' => array('content' => $data['text'])
    );
    $postString = ch_json_encode($post);
    $rt = $this->request($url, $query, true, $postString);
    if ($rt['errcode'] == 0)
      return true;
    else {
      $this->parseError($rt);
      return false;
    }
  }

  /**
   * Push image to user.
   * @param string $url The URL address of Wechat API.
   * @param string $query Query string of API request.
   * @param string $openid Openid of the user to push.
   * @param array $data image resource data, including $data['image_media_id']
   * @return boolean
   */
  private function pushImage($url, $query, $openid, $data) {
    if (empty($data) || empty($data['image_media_id'])) {
      $errorCode = WxSdkError::ERRCODE_NO_IMAGE_FOR_MSG;
      $errorMsg = WxSdkError::ERRMSG_NO_IMAGE_FOR_MSG;
      return false;
    }
    $post = array(
      'touser' => $openid,
      'msgtype' => 'image',
      'image' => array('media_id' => $data['image_media_id'])
    );
    $postString = json_encode($post);
    $rt = $this->request($url, $query, true, $postString);
    if ($rt['errcode'] == 0)
      return true;
    else {
      $this->parseError($rt);
      return false;
    }
  }

  /**
   * Push voice to user.
   * @param string $url The URL address of Wechat API.
   * @param string $query Query string of API request.
   * @param string $openid Openid of the user to push.
   * @param array $data voice resource data, including $data['voice_media_id']
   * @return boolean
   */
  private function pushVoice($url, $query, $openid, $data) {
    if (empty($data) || empty($data['voice_media_id'])) {
      $errorCode = WxSdkError::ERRCODE_NO_VOICE_FOR_MSGp;
      $errorMsg = WxSdkError::ERRMSG_NO_VOICE_FOR_MSG;
      return false;
    }
    $post = array(
      'touser' => $openid,
      'msgtype' => 'voice',
      'image' => array('media_id' => $data['voice_media_id'])
    );
    $postString = json_encode($post);
    $rt = $this->request($url, $query, true, $postString);
    if ($rt['errcode'] == 0)
      return true;
    else {
      $this->parseError($rt);
      return false;
    }
  }

  /**
   * Push news to user.
   * @param string $url The URL address of Wechat API.
   * @param string $query Query string of API request.
   * @param string $openid Openid of the user to push.
   * @param array $data News data, including $data['articles']
   * @return boolean
   */
  private function pushNews($url, $query, $openid, $data) {
    if (empty($data) || empty($data['articles'])) {
      $errorCode = WxSdkError::ERRCODE_NO_TEXT_FOR_MSG;
      $errorMsg = WxSdkError::ERRMSG_NO_TEXT_FOR_MSG;
      return false;
    }
    $post = array(
      'touser' => $openid,
      'msgtype' => 'news',
      'news' => array('articles' => $data['articles'])
    );
    $postString = ch_json_encode($post);
    $rt = $this->request($url, $query, true, $postString);
    if ($rt['errcode'] == 0)
      return true;
    else {
      $this->parseError($rt);
      return false;
    }
  }

  /**
   * Upload resource file, with supported types of image, voice, video and thumb.
   * @param string $mediaType Media type string.
   * @param string $filename Filename of upload file.
   * @return string The media ID string is returned, the resource file is uploaded successfully.
   * Return FALSE, if upload is failed.
   */
  public function uploadResource($mediaType, $filename) {
    if (!$this->refreshAccessToken()) {
      throw new Exception();
    }

    $url = "http://file.api.weixin.qq.com/cgi-bin/media/upload";
    $query = "access_token=".$this->accessToken."&type=$mediaType";
    $postString = array('media' => "@$filename");
    $rt = $this->request($url, $query, true, $postString, false);
    if (!empty($rt) && !empty($rt['media_id'])) {
      return $rt['media_id'];
    }
    else {
      $this->parseError($rt);
      return FALSE;
    }
  }

  /**
   * Get the resource download url address.
   * @param string $mediaId Media ID, returned from API while uploading resource.
   * @return string
   */
  public function resourceUrl($mediaId) {
    if (!$this->refreshAccessToken()) {
      throw new Exception();
    }

    $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?";
    return "$url.access_token=".$this->accessToken."&media_id=$mediaId";
  }

  /**
   * Refresh access token if the old access token is expired.
   * @return boolean
   */
  public function refreshAccessToken() {
    if (empty($this->accessToken) 
        || empty($this->accessTokenExpires)
        || (time() > $this->accessTokenExpires))
      return $this->_refreshAccessToken();
    else
      return true;
  }

  /**
   * Implementation of refreshing access token by API.
   * @return boolean
   */
  private function _refreshAccessToken() {
    $url = "/cgi-bin/token";
    $queryString = "grant_type=client_credential&appid=".$this->appid
      ."&secret=".$this->appsecret;
    $rt = $this->request($url, $queryString);

    if (empty($rt) || empty($rt['access_token'])) {
      $this->parseError($rt);
      return false;
    }

    $this->accessToken = $rt['access_token'];
    $this->accessTokenExpires = time() + $rt['expires_in'] - 10;

    return true;
  }

  /**
   * Parse the error data.
   * @param array $data 
   * Error code is defined in $data['errcode'], and error message is defined in $data['errmsg'].
   */
  private function parseError($data) {
    if (empty($data) || empty($data['errcode'])) 
      return;

    $this->errorCode = $data['errcode'];
    $this->errorMsg = $data['errmsg'];
  }

  /**
   * Post an API request.
   * @param string $url API url address
   * @param string $queryString Query string by GET method.
   * @param boolean $post Tf set to TRUE, the request contains POST parameters.
   * @param string $postString Post querystring.
   * @param boolean $cache If set to TRUE, the response is cached. If a request 
   * contains POST parameters, the response will not be cached.
   * @return array 
   */
  public function request($url, $queryString, $post = false, $postString = '', $relativeUrl = true) {
    $start_time = ($this->isLog ? microtime(true) : 0);
    if ($this->method == 'curl') {
      if (is_callable('curl_init'))
        $rt = $this->requestByCurl($url, $queryString, $post, $postString, $relativeUrl);
      else
        $rt = $this->requestBySocket($url, $queryString, $post, $postString, $relativeUrl);
    }
    else
      $rt = $this->requestBySocket($url, $queryString, $post, $postString, $relativeUrl);
    $end_time = ($this->isLog ? microtime(true): 0);
    $period = $end_time - $start_time;
    $this->lastRequestDuration = $period;
    return $rt;
  }
  
  /**
   * Request by cUrl component.
   * @access private
   * @param string $url API URL address
   * @param string $query API URL address
   * @param boolean $post Tf set to TRUE, the request contains POST parameters.
   * @param string $postString Post querystring.
   * @return array 
   */
  private function requestByCurl($url, $queryString, $post = false, $postString = '', $relativeUrl = true) {
    $uri = ($relativeUrl? $this->protocol."://".$this->host."$url": $url);
    $uri .= "?$queryString";
    $c = curl_init($uri);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    if ($post) {
      curl_setopt($c, CURLOPT_POST, 1);
      curl_setopt($c, CURLOPT_POSTFIELDS, $postString);
    }
    $page = curl_exec($c);
    $rt = json_decode($page, true);
    return $rt;
  }
  
  /**
   * Request by socket functions.
   * @access private
   * @param string $url API url address
   * @param string $queryString Query string by GET method.
   * @param boolean $post Tf set to TRUE, the request contains POST parameters.
   * @param string $postString Post querystring.
   * @return array 
   */
  private function requestBySocket($url, $queryString, $post = false, $postString = '', $relativeUrl = true) {
    $uri = ($relativeUrl? $this->protocol."://".$this->host."$url": $url);
    $uri .= "?$queryString";
    if ($post) {
      return $this->postBySocket($url, $queryString, $postString);
    }
    else
      return $this->getBySocket($uri);
  }

  /**
   * Fetching HTTP response with GET method.
   * @access private
   * @param string $url API url address
   * @param string $queryString Query string by GET method.
   * @return array
   */
  private function getBySocket($uri) {
    $page = '';
    $fh = fopen($uri, 'r');
    if (empty($fh))
      return null; 

    while (!feof($fh)) {
      $page .= fread($fh, SOCKET_MAX_LEN);
    }
    fclose($fh);
    $rt = json_decode($page, true);
    return $rt;
  }

  /**
   * Fetching HTTP response with POST method.
   * @access private
   * @param string $url API url address
   * @param string $queryString Query string by GET method.
   * @param string $postString Query string by POST method.
   * @return array
   */
  private function postBySocket($url, $queryString, $postString) {
    $timeout = 2;
    $contentString = "$postString";
    $contentLength = strlen($contentString);
    $requestBody = "POST $url?$queryString HTTP/1.0
Host: $this->host
Content-type: application/x-www-form-urlencoded
Content-length: $contentLength

$contentString";

    $sh = fsockopen($this->host, $this->port, &$errno, &$errstr, $timeout);
    if (empty($sh))
      return null;
    fputs($sh, $requestBody);
    $response = '';
    while (!feof($sh)) {
      $response .= fread($sh, SOCKET_MAX_LEN);
    }
    fclose($sh);

    list($respHeaders, $respBody) = explode("\r\n\r\n", $response, 2);
    $respHeaderLines = explode("\r\n", $respHeaders);
    // first line of headers is the HTTP response code
    $httpRespLine = array_shift($respHeaderLines);
    if (preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})@', $httpRespLine, 
                   $matches)) {
        $respCode = $matches[1];
    }
    // put the rest of the headers in an array
    $respHeaderArray = array();
    foreach ($respHeaderLines as $headerLine) {
      list($header, $value) = explode(': ', $headerLine, 2);
      $respHeaderArray[$header] = $value;
    }
    $rt = json_decode($respBody, true);
    return $rt;
  }

  private function checkSignature() {
    $signature = $_GET["signature"];
    $timestamp = $_GET["timestamp"];
    $nonce = $_GET["nonce"];	

    $token = TOKEN;
    $tmpArr = array($token, $timestamp, $nonce);
    sort($tmpArr);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );

    if( $tmpStr == $signature ){
      return true;
    }else{
      return false;
    }
  }

  private function responseEvent($wxPushData) {
    $handler = null;
    switch (strtolower($wxPushData->event)) {
      case "click":
        $handler = $this->handlers['click'];
        break;
      case "subscribe":
      case "unsubscribe":
        $handler = $this->handlers['subscribe'];
        break;
      case "scan":
        $handler = $this->handlers['scan'];
        break;
      case "location":
        $handler = $this->handlers['location'];
        break;
    }
    if (!empty($handler)) {
      $handler->setPushData($wxPushData);
      $handler->response();
    }
  }

  private function responseMsg($wxPushData) {
    $handler = new MsgHandler($wxPushData, $this);
    if (!empty($handler))
      $handler->response();
  }
};

/**
 * 对数组和标量进行 urlencode 处理
 * 通常调用 wphp_json_encode()
 * 处理 json_encode 中文显示问题
 * @param array $data
 * @return string
 */
function wphp_urlencode($data) {
  if (is_array($data) || is_object($data)) {
    foreach ($data as $k => $v) {
      if (is_scalar($v)) {
        if (is_array($data)) {
          $data[$k] = urlencode($v);
        } else if (is_object($data)) {
          $data->$k = urlencode($v);
        }
      } else if (is_array($data)) {
        $data[$k] = wphp_urlencode($v); //递归调用该函数
      } else if (is_object($data)) {
        $data->$k = wphp_urlencode($v);
      }
    }
  }
  return $data;
}

/**
 * json 编码
 *
 * 解决中文经过 json_encode() 处理后显示不直观的情况
 * 如默认会将“中文”变成"\u4e2d\u6587"，不直观
 * 如无特殊需求，并不建议使用该函数，直接使用 json_encode 更好，省资源
 * json_encode() 的参数编码格式为 UTF-8 时方可正常工作
 *
 * @param array|object $data
 * @return array|object
 */
function ch_json_encode($data) {
  $ret = wphp_urlencode($data);
  $ret = json_encode($ret);
  return urldecode($ret);
}
