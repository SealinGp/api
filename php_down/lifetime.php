<?php
/**
 * Created by PhpStorm.
 * User: v_kenqzhang
 * Date: 2019/5/7
 * Time: 15:01
 * ref url:http://www.php-internals.com/book/?p=chapt02/02-01-php-life-cycle-and-zend-engine
 */
/**php执行的生命周期:
 * 一切从 SAPI 接口开始(Server Application Programming Interface 服务应用编程接口)
 * 1.模块初始化(MINIT):url请求某个页面之前
 * PHP_MINIT_FUNCTION(myphpextension)
 * {
 *   // 注册常量或者类等初始化操作
 *   return SUCCESS;
 * }
 *
 * 2.模块激活(RINIT):如注册常量,定义模块使用的class
 * PHP_RINIT_FUNCTION(myphpextension)
 * {
 *   // 例如记录请求开始时间
 *   // 随后在请求结束的时候记录结束时间。这样我们就能够记录下处理请求所花费的时间了
 *   return SUCCESS;
 * }
 * 3.PHP初始化运行环境:初始化变量名称,值内容,class,function,
 *
 * 4.请求结束停用模块(RSHUTDOWN)
 *
 * 5.请求结束关闭模块(MSHUTDOWN):web server退出/命令行脚本退出时执行
 */