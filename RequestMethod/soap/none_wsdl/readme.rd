一个简单的 非wsdl模式下的soap服务端搭建 跟 soap客户端请求 的例子
文件作用:
run.php --------   启动soap服务端
SopClient.php ------ Soap客户端(API请求端)
SoapServerClass.php -------   Soap服务端的搭建以及响应类(API响应端)
SoapStruct.php -----  对象(用于soap客户端请求时传递header头时给(对象参数))



思路：
1.搭建Soap响应类以及Soap服务端(HandleClass.php)
2.启动服务端的运行(根据wsdl文件,可以启动服务端,SoapServerClass.php)
3.搭建客户端进行对应的请求,看是否成功(SoapClient.php)

注:若有错误,可虚心接收,作者也是新手phper,多多包涵,若有不理解,可向作者提问(会在看到的第一时间回复)
作者邮箱:sealingp@163.com