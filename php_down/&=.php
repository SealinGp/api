<?php
header('content-type:application/json');
//写时复制 & 引用赋值 demo

//string-------------------------------------
//写时复制(cow -- copy on write)
/*$a = 3;
$b = $a;
$b = 5;
var_dump($a); //3 结构体共用后,写时复制,结构分裂
exit;*/
//引用赋值
/*$a = 3;
$b = &$a;
$a = 5;
var_dump($b);//5  结构体共用($a,$b均对 结构体的值3 有主导权)
exit;*/

//array----------------------------------------- test demo -----------------------------------
//写时复制
/*$a = [1,2,3];
$b = $a;  // (&$a)
$a[0] = 4;
var_dump($b[0]);// ? (?)
exit;*/

//引用赋值 & 写时复制
$arr = array('a', 'b', 'c', 'd');

$tmp = $arr;

$x = &$arr[1];

$arr[1] = 999;

var_dump($tmp[1],$x); //'b'  999
//var_dump($tmp,$arr);
exit;
?>