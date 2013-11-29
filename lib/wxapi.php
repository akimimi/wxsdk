<?php

require_once ROOT."/lib/event_click_handler.php";
require_once ROOT."/lib/event_subscribe_handler.php";
require_once ROOT."/lib/msg_handler.php";

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

  const ERRCODE_NO_TEXT_FOR_MSG = -10001;
  const ERRMSG_NO_TEXT_FOR_MSG = "no text for sending message";

  /**
   * Default construct function of WxSdk
   * @param string $appid
   * @param string $appsecret
   */
  public function __construct($appid, $appsecret) {
    $this->appid = $appid;
    $this->appsecret = $appsecret;
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
      return $this->pushTextMessage($url, $query, $openid, $data);
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
  private function pushTextMessage($url, $query, $openid, $data) {
    if (empty($data) || empty($data['text'])) {
      $errorCode = ERRCODE_NO_TEXT_FOR_MSG;
      $errorMsg = ERRMSG_NO_TEXT_FOR_MSG;
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
   * Push news to user.
   * @param string $url The URL address of Wechat API.
   * @param string $query Query string of API request.
   * @param string $openid Openid of the user to push.
   * @param array $data News data, including $data['articles']
   * @return boolean
   */
  private function pushNews($url, $query, $openid, $data) {
    if (empty($data) || empty($data['articles'])) {
      $errorCode = ERRCODE_NO_TEXT_FOR_MSG;
      $errorMsg = ERRMSG_NO_TEXT_FOR_MSG;
      return false;
    }
    $post = array(
      'touser' => $openid,
      'msgtype' => 'news',
      'news' => array('articles' => $data['articles'])
    );
    $postString = ch_json_encode($post);
    echo $postString.PHP_EOL;
    $rt = $this->request($url, $query, true, $postString);
    if ($rt['errcode'] == 0)
      return true;
    else {
      $this->parseError($rt);
      return false;
    }
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
    $this->accessTokenExpires = time() + $rt['expires_in'];

    file_put_contents("log.txt", $this->accessToken.PHP_EOL);
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

    $this->errorCode = $data['errorCode'];
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
  public function request($url, $queryString, $post = false, $postString = '') {
    $start_time = ($this->isLog ? microtime(true) : 0);
    if ($this->method == 'curl') {
      if (is_callable('curl_init'))
        $rt = $this->requestByCurl($url, $queryString, $post, $postString);
      else
        $rt = $this->requestBySocket($url, $queryString, $post, $postString);
    }
    else
      $rt = $this->requestBySocket($url, $queryString, $post, $postString);
    $end_time = ($this->isLog ? microtime(true): 0);
    $period = $end_time - $start_time;
    $this->lastRequestDuration = $period;
    return $rt;
  }
  
  /**
   * Request by cUrl component.
   * @access private
   * @param string $url API url address
   * @param string $queryString Query string by GET method.
   * @param boolean $post Tf set to TRUE, the request contains POST parameters.
   * @param string $postString Post querystring.
   * @return array 
   */
  private function requestByCurl($url, $queryString, $post = false, $postString = '') {
    $c = curl_init($this->protocol."://".$this->host."$url?$queryString");
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    if ($post) {
      curl_setopt($c, CURLOPT_POST, 1);
      curl_setopt($c, CURLOPT_POSTFIELDS, "$postString");
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
  private function requestBySocket($url, $queryString, $post = false, $postString = '') {
    if ($post) {
      return $this->postBySocket($url, $queryString, $postString);
    }
    else
      return $this->getBySocket($url, $queryString);
  }

  /**
   * Fetching HTTP response with GET method.
   * @access private
   * @param string $url API url address
   * @param string $queryString Query string by GET method.
   * @return array
   */
  private function getBySocket($url, $queryString) {
    $uri = $this->protocol."://".$this->host."$url?$queryString";
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
    switch ($wxPushData->event) {
      case "CLICK":
        $handler = new EventClickHandler($wxPushData, $this);
        break;
      case "subscribe":
      case "unsubscribe":
        $handler = new EventSubscribeHandler($wxPushData, $this);
        break;
    }
    if (!empty($handler))
      $handler->response();
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
