<?php
header('content-type:application/json;');

ini_set('soap.wsdl_cache_enabled', 0); //关闭wsdl缓存,上线改1
//$soap = new \SoapClient('http://127.0.0.1:8083/mockServiceSoapBinding?wsdl');
$soap = new \SoapClient('http://localhost/API/RequestMethod/soap/wsdl/RunServer.php?wsdl');
try {
//    $a = $soap->__soapCall('say',[123]);
    $a = $soap->__getFunctions();
//    $a = $soap->login('username','password');
    // $b = $soap->say();
    
    var_dump($a);exit;
} catch (\Throwable $e) {
    var_dump($e->getMessage());
}


/*  请求端请求方式
 * 1.找到请求地址$url = XXX?wsdl
 * 2.在浏览器上面直接访问该地址,查看接口的方法以及参数
 * <portType>: <opercation>操作名(接口方法名)
 * <message>: name= xxxRequest,<part>:name:输入参数名 type:参数类型
 * */