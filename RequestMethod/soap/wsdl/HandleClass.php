<?php
namespace wsdl;
class HandleClass{
    //Soap响应处理类
    //api请求方式+参数(输入返回),可自己设置
    public function say(string $url):string
    {
        $say = $url;
        return $say;
    }
    
    public function say2(string $token,string $param):string
    {
       $say2 = $token.$param;
        return $say2;
    }
   

}