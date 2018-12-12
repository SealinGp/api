
一个简单的wsdl模式下的soap服务端搭建 跟 soap客户端请求 的例子
文件作用:

HandleClass.php -------  Soap服务端处理类的设置

soap_server.php -------- soap服务端 wsdl文件+wsdl url的生成

soap.php ------ Soap服务运行端(API响应端)

SopClient.php ------ Soap客户端(API请求端)

思路：
1.搭建Soap响应处理类以及Soap服务端(HandleClass.php)

2.以Soap响应处理类为标准 生成wsdl文件(wsdl说明文档:soap_server.php->getWsdlUrl(),生成wsdl文件:HandleClass.wsdl),此时已经生成wsdl文件

3.启动服务端的运行(根据wsdl文件,可以启动服务端,soap_server.php->run())

4.搭建客户端进行对应的请求,看是否成功(SopClient.php)

请求地址为 'Soap服务端运行位置+?wsdl' (前提是Soap服务端运行位置需要跟wsdl文件位置一致)

注:若有错误,可虚心接收,作者也是新手phper,多多包涵,若有不理解,可向作者提问(会在看到的第一时间回复)
作者邮箱:sealingp@163.com