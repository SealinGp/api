<?php
class AuthMethod{
    //status code
    private static $_restult = [
        'incorrectRequest' => ['status'=>'failed','msg'=>'incorrect request'],
        'empty' => ['status'=>'failed','msg'=>'empty authorzation'],
        'type' => ['status'=>'failed','msg'=>'error authorization type'],
        'paswordError' => ['status'=>'failed','msg'=>'error authorization'],
        'success' => ['status'=>'success','msg'=>'yes'],
    ];
    /*基本token验证
      @return false
       * */
    public function basicAuth() {
        $request_header = apache_request_headers();
        $auth = isset($request_header['Authorization'])?$request_header['Authorization']:0;
        if ($auth) {
            $auth_arr = explode(' ',$auth);
            //检查授权类型
            //Bearer xxxx(授权码),Basic xxxx(64加密的appid:appsecret),
            if ($auth_arr[0]!='Basic') {
                return json(['error'=>'error authorization type']);
            }
            $auth_arr2 = explode(':', base64_decode($auth_arr[1]));
            $appid = $auth_arr2[0];
            $appsecret = $auth_arr2[1];
            if ($appid!='zq'||$appsecret!='19941126zx') {
                return json(['error'=>'error authorization']);
            } else {
                return json(true);
            }
        } else {
            return json(['error'=>'empty authorzation']);
        }
    }
    public function BearToken()
    {
        if (!request()->isPost()) {
            return json((self::$_restult)['incorrectRequest']);
        }
        $request_header = apache_request_headers();
        $auth = isset($request_header['Authorization'])?$request_header['Authorization']:0;
        if (!$auth) {
            return json((self::$_restult)['empty']);
        }
        $auth = explode(' ',$auth);
        if ($auth[0]!='Bearer') {//验证类型
            return json((self::$_restult)['type']);
        }
        if ($auth[1]!='Token_zq_123') {//Token值
            return json((self::$_restult)['paswordError']);
        }
        return json((self::$_restult)['success']);
    }
    public function getmicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}