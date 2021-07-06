<?php
//psr4 composer自动加载机制

include 'vendor/autoload.php';

/**
 * composer.json
 *  {
 *  "psr-4": {
        namespace\\ => dir/
 *  }
 * }
 *
 * dir路径以composer.json文件所在路径为准,相对路径
 */
$a = new Foo\index01();
var_dump($a);
exit;

