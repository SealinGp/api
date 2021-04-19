<?php
include 'autoload.php';
$time1 = new DateTime('now');

/*//连接
//ebay导入/导出效率计算
$sftp = new \API\ext\sftp();
$conn = $sftp::connect([
    'host' => '129.226.68.117',
    'port' => '22',
    'user' => 'ubuntu',
    'pass' => 'Zhangqiang2607'
]);
$sftp::setSuffixDir('/home/ubuntu/xmltest','r');
$sftp::downAll('./xmltest');

//解析
$xml   = new \API\ext\xml();
$files = $sftp::listFile('./xmltest','l',false,true);
$pdo = new PDO('mysql:dbname=testdb;host=127.0.0.1','root','19941126zx');
foreach ($files as $file) {
    $xml->set_file($file);
    $xmlArr = $xml->to_arr('');
    if (!empty($xmlArr['itemInfo'])) {
        $xmlArr['itemInfo'] = json_encode($xmlArr['itemInfo']);
    }
    $fields = [];
    $values = [];
    foreach ($xmlArr as $field => $value) {
        $fields[] = '`'.$field.'`';
        $values[] = '\''.$value.'\'';
    }
    $fields = implode(',',$fields);
    $values = implode(',',$values);
    
    //入库
    $statement = 'INSERT INTO `sku` ('.$fields.') VALUES ('.$values.')';
    $i = $pdo->exec($statement);
    if ($i === false) {
        var_dump($pdo->errorInfo());
    }
}*/

function Constant1(array $arr):array
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


function faSort(array $arr) {
    if (count($arr) <= 1) {
        return $arr;
    }
    $mid      = $arr[0];
    $leftArr  = [];
    $rightArr = [];
    $len = count($arr);
    
    for ($i = 1; $i < $len; $i++) {
        if ($arr[$i] < $mid) {
            $leftArr[] = $arr[$i];
        } else {
            $rightArr[] = $arr[$i];
        }
    }
    return array_merge(faSort($leftArr),[$mid],faSort($rightArr));
}

//连续排序算法 2w个邮编
$youbian = [];
while (count($youbian) < 20000) {
    $r = rand(0,200000);
    if (!in_array($r,$youbian)) {
        $youbian[] = $r;
    }
}
$youbian = faSort($youbian);
$youbian = Constant1($youbian);
echo json_encode($youbian,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;



$time2 = new DateTime('now');
$dur = $time2->diff($time1);
if ($dur === false) {
    return;
}
echo $dur->format('%s.%f seconds').PHP_EOL;