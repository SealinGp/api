<?php
/**
 * Created by PhpStorm.
 * User: sea
 * Date: 2018/8/10
 * Time: 6:50
 */
$start = microtime(true);
header('content-type:application/json;');
#sort int[]
$arr = [];
for ($i = 0; $i < 5000; $i++) {
    $arr[] = rand(1, 10000);
}
function change(&$a,&$b)
{
    $c = $a;
    $a = $b;
    $b = $c;
}
//1.insert sort(插入排序) small -> big
/**
5               3           // 将 3 插入只有一个元素 5 的有序表中
3 5             4           // 将 4 插入有两个元素 3 5 的有序表中
3 4 5           6           // 将 6 插入有两个元素 3 4 5 的有序表中
3 4 5 6         2           // 将 2 插入有两个元素 3 4 5 6 的有序表中
2 3 4 5 16
 *  1.left(j) < right(j) ?
 *  2.i=0,j=i+1
 *  3.(i)max: len-1,++, (j)max:0,--
 *总结:内循环在左边,递减
 * arr = [3,7,1];
 *
 * {3,7,1}
 * i=0=j-1, j=i+1=1
 * {3,7,1}
 *    ↓(i+1)
 * {3,7,1}
 * i=1=j-1, j=i+1=2
 * {3,1,7}    ==(j-1)>0? true=> {3,1,7}
 *                      j-1=0, j=1,(j--)>0? false
 *                      {1,3,7}
 *
 */
//把后面的 往前放(<)
function insert_sort(array $arr):array
{
    $len = count($arr);
    for ($i = 0;$i<$len-1;$i++) {
        for ($j = $i+1;$j>0;$j--) {//后
          if ($arr[$j]<$arr[$j-1]) {
              change($arr[$j],$arr[$j-1]);
          }
        }
    }
    return $arr;
}
/*$a   = insert_sort($arr);
$end = microtime(true);
var_dump(json_encode($a),$end-$start);*/


//2.冒泡排序(石头入水) 小->大
/**
  1. left(i) > right(j) ?
 *2. i=0,j=i+1,
 *3. (i)max:len-1,++,(j)max:len-1,++
 * 总结:内循环在右边,递增
 * {3,1,7}
 * i=0,j=1
 * {1,3,7}
 *
 * {1,3,7}
 * i=1,j=2
 * {}
 */
function bubble($arr):array
{
    $len = count($arr);
    for ($i=0;$i<$len-1;$i++) {
        for ($j=$i+1;$j<$len-1;$j++) {
            if ($arr[$i]>$arr[$j])
                change($arr[$i],$arr[$j]);
        }
    }
    return $arr;
}
$a   = bubble($arr);
$end = microtime(true);
var_dump(json_encode($a),$end-$start);


/**非重复已排序数组的 连续排序(相邻间隔1)
 * {1,2,3,4,7,8,10}
 * i=0,2-1=1 ?
 * i=1,3-2=1 ?
 * i=2,4-3=1 ?
 * i=3,7-4=1 ? [1,2,3,4]   [7,8,10] =>  i=0,8-7=1?
 *                         [7,8,10] =>  i=1,10-8=1?   [7,8]  [10]
 */
//{1,2,3,4,7}
function constant(array $arr):array
{
    //{1,2,3,4,7,8,10}
    $startIndex    = 0;      //截取起点
    $constantArr   = [];     //存放连续数组
    $amount        = 0;      //连续数组总数
    $len           = count($arr);
    //j=0,i=0,
    //j=0,i=1,
    //...
    //j=0,i=3
    //j=3,i=4
    //j=3,i=5
    for ($i = $startIndex;$i<$len-1;$i++) {
        //2-1 != 1?
        //3-2 != 1?
        //...
        //7-4 != 1?
        //8-7 != 1?
        //10-8 != 1?
        if ($arr[$i+1]-$arr[$i] != 1) {
            //$a[3] = [1,2,3,4]; //0=j,len = 4 = i+1  i-j+1  j=i+1
            //$a[5] = [7,8]; //4=j,  len = 2 = 4-5+1
            $constantArr[$startIndex] = array_slice($arr,$startIndex,abs($i-$startIndex)+1);
            $amount                  += count($constantArr[$startIndex]);
            //j=3
            $startIndex               = $i+1;
        }
    }
    if ($amount != $len) {
        $constantArr[$startIndex] = array_slice($arr,$startIndex,abs($len-1-$startIndex)+1);
        $amount                  += count($constantArr[$startIndex]);
    }
    return $constantArr;
}