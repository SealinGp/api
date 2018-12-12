<?php
/**
 * User: 46448
 * Date: 2018/4/12
 */
header('content-type:application/json');

//example 2 const
class a {
    private static $name = 'a';
    public function __toString()
    {
       return self::$name;
    }
}
$a = new a();
define('TEST',$a);
//const TEST = 'a';

echo $a."\n"; //a
echo TEST; //a
exit;

//example 1  static
function add()
{
    static $a = 1;
//    return ++$a;  //2, 3, 4
//    return $a+=1; //2, 3, 4
    return $a++; // 1, 2, 3
}
var_dump(add(),add(),add());// 2, 3, 4


