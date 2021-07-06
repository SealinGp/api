<?php
//SoapClient客户端 的请求方式
//非wsdl模式的SOAP数据对接

//uri 跟 location 必填参数(在非wsdl模式下)
//uri:域名IP
//location:服务端运行位置
//创建客户端, 配置相关参数 uri location
$ip = 'http://127.0.0.1';
$location = $_SERVER['HTTP_REFERER'].'run.php'; //当前url地址,不包含此文件的名字
$cli = new SoapClient(null, array('uri' => $ip, 'location' => $location,
    'trace' => true,'encoding'=>'utf-8'));
//---------------header元素标签的 预验证处理 说明
/*@param string $namespace 命名空间
 *@param function $functionName 指定header对象的传送位置(服务端哪个方法用来接收header信息的)
 *@param string | object  $data 传递的 参数 | {对象参数}(见下面)
 * @param bool $operate 是否必须处理(通过)header所对应的方法的验证 | header元素mustUnderstand属性的值
 * @param int $actor header元素actor属性的值
 * */
$namespace = 'http://127.0.0.1/API/';
$h = new SoapHeader($namespace, $functionName = 'headerReceived', $data = '123', $operate = false,$actor = SOAP_ACTOR_NEXT);
$res = $cli->__setSoapHeaders($h);
//传送cookie,服务端接收cookie 使用全局变量 $_COOKIE
$cli->__setCookie('cookie','aaaaa');
//--------------------------------------------------
try {
    //若预验证成功则 访问服务端的方法并传参 $client->服务端的方法(参数);
    echo $cli->say('HI');
} catch (Exception $e) {
//预认证失败后的  获取thrown 的异常信息
    echo $e->getMessage();
}
//传递{对象参数$soapstruct}变量--------------------------------------------------
//    include './SOAPStruct.php';
//    $data = new SOAPStruct('arg', 1, 1.12);
//    $datatype = SOAP_ENC_OBJECT;
//    $name = "SOAPStruct";
//    $soapstruct = new SoapVar($data, $datatype, $name, "http://soapinterop.org/xsd");
//    $a = $cli->getParam($soapstruct);
//    var_dump($a)
//传递{对象参数}变量$soapstruct--------------------------------------------------