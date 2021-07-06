<?php
header('content-type:application/json');
const dompdf = 'Dompdf';

define('ROOT', realpath(__DIR__));

define('Files',[
    'Dompdf' => ROOT.'\\src',
    'lib' => ROOT.'\\lib',
    'FontLib' => ROOT.'\\lib\\php-font-lib\\src\\FontLib',
    'Svg' => ROOT.'\\lib\\php-svg-lib\\src\\Svg',
]);


spl_autoload_register('load');
function load(string $lib): void
{
    $check = 0;
    $lib = explode('\\',$lib);
    foreach ($lib as $k => &$v) {
        if (in_array($v,array_keys(Files)) && $k == '0') {
            $v = Files[$v];
            $check = 1;
        }
    }
    $lib = implode('\\',$lib);
    
    if (false === strpos($lib, '\\')) return;
    if (!$check) $lib = ROOT.'\\'.$lib;
    $file = realpath(strtr($lib, '\\', '/') . '.php');
   
    if (false !== $file) require $file;

    unset($lib, $file);
}
