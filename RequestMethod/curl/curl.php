<?php
namespace API\RequestMethod\curl;
class curl {
    //curl set options
    private static $_conf = [
        //request config
        'CURLOPT_TIMEOUT' => 30,
        'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
        'CURLOPT_ENCODING' => '',                        //header accept-encoding value 'identity'/'deflate'/'gzip'
        'CURLOPT_COOKIEFILE' => '',                      //send cookie from file
        'CURLOPT_USERAGENT' => '',                       //simulation browser (browser version)
        'CURLOPT_SSL_VERIFYPEER' => false,               //cainfo =
        'CURLOPT_STDERR' => '',                          //error output to which resource
        'CURLOPT_REFERER' => '',                         //
        'CURLOPT_POST' => '',                            //form url encode post request
        'CURLOPT_POSTFIELDS' => '',                      //payload
        
        
        //response config
        'CURLOPT_FOLLOWLOCATION' => true,                //allow redirect
        'CURLOPT_MAXREDIRS' => 10,                       //allow max redirect number
        'CURLOPT_RETURNTRANSFER' => true,                //save payload as string
        'CURLOPT_BINARYTRANSFER' => false,               //Raw
        'CURLOPT_CRLF' => false,                         //\n -> enter
        'CURLOPT_HEADER' => false,
        'CURLOPT_COOKIEJAR' => '',                       //save cookie to file
        'CURLOPT_NOBODY' => '',                          //response no body
    ];
    
    //if reset conf when request finished
    private static $_resetConf = false;
  
    //content-type for header
    private static $_contentType = [
        'urlencode' => 'application/x-www-form-urlencoded;',
        'form' => 'content-type: multipart/form-data; boundary=----data',
        'json' => 'content-type:application/json;',
        'xml' => 'content-type:application/xml;',
    ];
    
    private static $_availableVariable = [
        '_resetConf'
    ];
    
    //http status
    private $httpStatus = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];
        
    
    
    /**http请求
     * @param string $method  post | get
     * @param string $url
     * @param string $contentType form | json | xml | urlencode | ''
     * @param array $payload  request body,if form,$payload = json_encode(array $formData)
     * @param array $header ['content-type:xxxx;','...']
     * @return array
     */
    public function http(string $method,string $url,string $payload,string $contentType,array $header = []):string
    {
        $param = [
            'method' => $method,
            'url' => $url,
            'payload' => $payload,
            'header' => $header,
            'contentType' => $contentType,
        ];
        $result = [
            'status' => 'failed',
            'data' => ''
        ];
        isset(self::$_contentType[$contentType]) &&
        array_push($param['header'],self::$_contentType[$contentType]);
        try {
            
            $result = !in_array($method,['post','get']) ?
                $result : $this->$method($param);
        } catch (\Throwable $err) {
            $result['data'] = $err->getMessage();
        }
        return $result;
    }
    
    /**set conf
     * @param array $conf
     */
    public function setConf(array $conf):void
    {
        foreach ($conf as $k => $v) {
            isset(self::$_conf[$k]) && self::$_conf[$k] = $v;
        }
    }
    
    /**set static variable
     * @param array $variableMap
     */
    public function setStaticVariable(array $variableMap)
    {
        if (!in_array($variableMap,self::$_availableVariable)) return;
        foreach ($variableMap as $k => $v) {
            isset(self::$$k) && self::$$k = $v;
        }
    }
    
    /**get request
     * @param array $param
     * @return string
     */
    private function get(array $param):string
    {
        $conf = [
            'method' => $param['method'],
            'url' => $param['url'],
            'payload' => '',
            'header' => $param['header']
        ];
        return self::conf($conf);
    }
    
    /**post request
     * @param array $param
     * @return string
     */
    private function post(array $param):string
    {
        $conf = [
            'method' => $param['method'],
            'url' => $param['url'],
            'payload' => $param['payload'],
            'header' => $param['header']
        ];
        ('form' == $param['contentType']) &&
        $conf['payload'] = self::setForm($param['payload']);
        return self::conf($conf);
    }
    
    private function setForm (string $payload):string
    {
        $payload = json_decode($payload,true);
        $payloadStr = '';
        foreach ($payload as $k => $v) {
            $payloadStr .= "------data\r\nContent-Disposition: ";
            $payloadStr .= "form-data; name=\"{$k}\"\r\n\r\n{$v}\r\n";
        }
        return $payloadStr;
    }
    
    
    /**curl request
     * @param array $conf
     * @return string
     */
    private static function conf (array $conf):string
    {
        $method = ['post'];
        in_array($conf['method'],$method,true) &&
        self::$_conf['CURLOPT_POSTFIELDS'] = $conf['payload'];
    
        $curl = curl_init();
        $conf['header'][] = 'Cache-Control: no-cache';
         curl_setopt_array($curl, [
             CURLOPT_URL => $conf['url'],
             CURLOPT_CUSTOMREQUEST => strtoupper($conf['method']),
             CURLOPT_HTTPHEADER => $conf['header'],
         ]);
         foreach (self::$_conf as $k => $v) {
             '' !== $v && curl_setopt($curl,constant($k),$v);
         }
         $result = curl_exec($curl);
         $err = curl_error($curl);
         curl_close($curl);
         $result = '' !== $err ? : $result;
         self::$_resetConf && self::resetConf();
         return $result;
    }
    
    //reset conf
    private static function resetConf()
    {
        self::$_conf = [
            //request config
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
            'CURLOPT_ENCODING' => '',
            'CURLOPT_COOKIEFILE' => '',
            'CURLOPT_USERAGENT' => '',
            'CURLOPT_SSL_VERIFYPEER' => false,
            'CURLOPT_STDERR' => '',
            'CURLOPT_REFERER' => '',
            'CURLOPT_POST' => '',
            
            //response config
            'CURLOPT_MAXREDIRS' => 10,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_BINARYTRANSFER' => false,
            'CURLOPT_CRLF' => false,
            'CURLOPT_HEADER' => false,
            'CURLOPT_COOKIEJAR' => '',
        ];
    }
    
    
    
    
  
 
}