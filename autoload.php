<?php
header('content-type:application/json');
define('ROOT', realpath(substr(__DIR__, 0, -4)));
spl_autoload_register('load');
$psr4 = [
    'wsdl' => 'API\\RequestMethod\\soap\\wsdl',
    'API'  => 'api'
];
define('PSR4',$psr4);
function load(string $lib): void
{
    
    foreach (PSR4 as $k => $v) {
        false !== stripos($lib,$k) && $lib = strtr($lib,[$k=>$v]);
    }
    if (false === strpos($lib, '\\')) return;
    $file = realpath(ROOT . '/' . strtr($lib, '\\', '/') . '.php');
    if (false !== $file) require $file;

    unset($lib, $file);
}
