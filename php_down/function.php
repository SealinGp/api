<?php
header('content-type:application/json');

//exapmle 1
$age = 23;
function t() {
    global $age;
    $age = 3;
    var_dump($age);
}

var_dump($age); //23

t(); //3

var_dump($age); //3