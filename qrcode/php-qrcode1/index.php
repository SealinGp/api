<?php
include "./phpqrcode/qrlib.php";

//echo phpinfo();
$url = "http://www.baidu.com";

//todo
// 1.生成优惠劵
// 2.生成优惠劵链接
// 3.根据链接生成二维码图片
// 4.根据二维码图片可扫描跳转到获取优惠券页面
$outFile = "./test.png";
$size = 4; //生成的二维码的规格

QRcode::png($url,$outFile,QR_ECLEVEL_L,$size);
?>


<img src="./test.png">

