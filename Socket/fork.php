<?php
//创建子进程,仅 linux下可用
$pid = pcntl_fork();
switch ($pid) {
    case -1:
        echo 'failed';
        break;
    case 0:
        echo 'i\'m child';
        break;
    default:
        echo 'i\'m parent';
}