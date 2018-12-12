<?php

class SoapServerClass
{
//SoapServer SOAP服务端 用来接收对应客户端请求的数据
  //服务端接收header  预认证例子
    public function headerReceived($password) {
        if ($password != '123') {
            throw new SoapFault('Server','access2 denied');
        }
    }
    //服务端接收cookie例子
    public  function say($say){
        //服务端接收cookie
        $cookieVal =  isset($_COOKIE['cookie'])?$_COOKIE['cookie']:'not set';
        return json_encode(['success'=>$say,'cookie'=>$cookieVal]);
    }
    //服务端多输入参数例子
    public  function say2($say='',$say2=''){
        return json_encode(['success'=>$say,'error'=>$say2]);
    }
    //获取参数
    public function getParam($param)
    {
        return $param;
    }
    /*搭建soap服务端
     * */
    public static function run()
    {
        //soap服务端创建 非wsdl模式
        $server = new \SoapServer(null, array('uri' => 'http://127.0.0.1'));
        //设置 接收数据处理的类, class文件 | 对象
//        $res = $srv->setClass('Soapserverclass');
        $object = new self;
        $server->setObject($object);
        session_start();
        $server->setPersistence(SOAP_PERSISTENCE_SESSION);
        $server->handle();
    }
}
//SoapServerClass::run();
