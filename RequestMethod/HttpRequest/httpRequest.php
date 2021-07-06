<?php
//$h = new HttpRequest();
include "Request/autoload.php";
//include 'vendor/rmccue/requests/library/Requests.php';
$url = 'http://www.zq.com/admin/goods/BearToken';
$headers = [
    'Authorization'=>'Bearer Token_zq_123',
    'Cache-Control' => 'no-cache',

];
$body = ['bodyparam'=>'111',];
$options = [
                'cookies' => ['PHPSESSID'=>'u366c6krl21lni01bqh1mhcdo7']
];
$resutl = Requests::post($url,$headers,$body,$options);

echo '<pre>';
var_dump($resutl->body);



