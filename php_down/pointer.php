<?php
// Date: 2018/4/12
//Time: 16:20
header('content-type:application/json');
//指针

//example4:
$arr = ['a','b','c','d'];
next($arr);
var_dump(current($arr)); //b
exit;

//example3: & 符号 并不能代表指针
$arr = ['a','b','c','d'];
foreach ($arr as &$v) { //该foreach 会导致 $v = &$arr[3];
    var_dump($arr);
}
//var_dump(current($arr));// 'a'  php version >= 7 取消循环使指针移动
exit;

//example2:
$arr = ['a','b','c','d'];
foreach ($arr as $k => $v) {
    $arr[$k] = $v;
}
var_dump(current($arr)); //  'a'
exit;

//exapmle1:
$arr = ['a','b','c','d'];
foreach ($arr as $k) {

}
var_dump(current($arr)); // 'a'
exit;