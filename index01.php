<?php
header("Content-type:application/json;charset=utf-8");
//include '../functions.php';
include 'autoload.php';
$xml = new \API\ext\xml('./1.xml');
$arr = $xml->to_arr('');

var_dump($arr);exit;
