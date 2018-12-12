<?php
//socket server环境 cli下执行(已经配置好环境的情况下) eg: php Socket_server.php 回车
//$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP); //ipv4,type,tcp
$socket = @socket_create_listen(1983) ? : false;
if (
    !$socket ||
//    !socket_bind($socket, '127.0.0.1', 1983) ||// ,ip,port(1~65535)
    !@socket_listen($socket)
) exit('create server error');
$msglength = 8192;
echo "success!please enter msg...\n";

do {
      $accept = socket_accept($socket);
      $msg =   socket_read($accept,$msglength);
      echo "read success..($msg)\n";
      if ($msg) {
          socket_write($accept,$msg,strlen($msg));
          echo "ok,write back success!\n";
      } else {
          echo "this msg send error($msg)";
      }
}while(true);
