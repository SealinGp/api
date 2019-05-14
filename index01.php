<?php
header("Content-type:application/json;charset=utf-8");
include '../functions.php';
$a = '{
	"Hs-CRP": [
     {
          "name":"1" ,
          "model":"0x0001",
          "val" : ""
      },
     {
          "name":"2" ,
          "mdoel":"0x0002",
          "val" :{
                     "reg":50,
                     "speed":15
                  }
      }
    ]
}';
$a = json_decode($a,true);
var_dump($a);exit;